<?php

namespace App\Services;

use App\Ai\Agents\DigitalizeAgent;
use App\Ai\Agents\DigitalizeAgentNova;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Files\Document;
use Laravel\Ai\Files\Image;

/**
 * Runs AI extraction for digitalize (image/video → structured doc or table).
 * Used by DigitalizeController (sync) and DigitalizeFileJob (async).
 */
class DigitalizeExtractionService
{
    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    /**
     * Extract content from file binary. Returns digital_data, resolved_name, ai_provider, ai_model.
     *
     * @return array{digital_data: array<string, mixed>, resolved_name: string, ai_provider: string|null, ai_model: string|null}
     */
    public function extract(
        string $decoded,
        string $mimeType,
        ?string $requestProvider,
        ?string $requestModel,
        ?string $nameFromRequest
    ): array {
        $base64 = base64_encode($decoded);
        $isImage = in_array($mimeType, self::IMAGE_MIMES, true);

        Log::info('[digitalize] extraction: start', [
            'mime' => $mimeType,
            'size_bytes' => strlen($decoded),
            'input_type' => $isImage ? 'image' : 'video',
        ]);

        $attachments = $this->attachmentsForDigitalize($isImage, $decoded, $base64, $mimeType);
        Log::info('[digitalize] extraction: attachments prepared', [
            'attachment_count' => count($attachments),
        ]);

        $agent = $this->agentForProvider($requestProvider);
        $response = $this->runExtraction($agent, $attachments, $requestProvider, $requestModel);

        $effectiveProvider = $requestProvider ?: config('ai.default');
        if ($effectiveProvider === 'nova') {
            $response = $this->normalizeNovaResponse($response);
            Log::info('[digitalize] extraction: Nova response normalized');
        }

        $digitalData = $this->buildDigitalDataFromResponse($response);
        $resolvedName = $this->resolveName($response, $nameFromRequest);
        [$aiProvider, $aiModel] = $this->resolveProviderAndModelForStorage($requestProvider, $requestModel);

        Log::info('[digitalize] extraction: complete', [
            'type' => $digitalData['type'] ?? null,
            'resolved_name' => $resolvedName,
        ]);

        return [
            'digital_data' => $digitalData,
            'resolved_name' => $resolvedName,
            'ai_provider' => $aiProvider,
            'ai_model' => $aiModel,
        ];
    }

    /**
     * Extract content from multiple files as a single doc/table. Used for multi-file upload (one Data record).
     *
     * @param  array<int, array{decoded: string, mime_type: string, name_from_request: string|null}>  $filePayloads
     * @return array{digital_data: array<string, mixed>, resolved_name: string, ai_provider: string|null, ai_model: string|null}
     */
    public function extractFromMultipleFiles(
        array $filePayloads,
        ?string $requestProvider,
        ?string $requestModel
    ): array {
        $attachments = [];
        $firstName = null;
        foreach ($filePayloads as $payload) {
            $decoded = $payload['decoded'] ?? '';
            $mimeType = $payload['mime_type'] ?? 'application/octet-stream';
            $nameFromRequest = $payload['name_from_request'] ?? null;
            if ($firstName === null && $nameFromRequest !== null && $nameFromRequest !== '') {
                $firstName = $nameFromRequest;
            }
            $base64 = base64_encode($decoded);
            $isImage = in_array($mimeType, self::IMAGE_MIMES, true);
            $parts = $this->attachmentsForDigitalize($isImage, $decoded, $base64, $mimeType);
            foreach ($parts as $p) {
                $attachments[] = $p;
            }
        }
        if ($attachments === []) {
            Log::warning('[digitalize] extractFromMultipleFiles: no attachments built');
            [$aiProvider, $aiModel] = $this->resolveProviderAndModelForStorage($requestProvider, $requestModel);

            return [
                'digital_data' => ['type' => 'doc', 'content' => '', 'suggested_prompts' => [], 'insights' => []],
                'resolved_name' => $firstName ?? 'document',
                'ai_provider' => $aiProvider,
                'ai_model' => $aiModel,
            ];
        }
        Log::info('[digitalize] extraction: multi-file', ['file_count' => count($filePayloads), 'attachment_count' => count($attachments)]);
        $agent = $this->agentForProvider($requestProvider);
        $response = $this->runExtraction($agent, $attachments, $requestProvider, $requestModel);
        $effectiveProvider = $requestProvider ?: config('ai.default');
        if ($effectiveProvider === 'nova') {
            $response = $this->normalizeNovaResponse($response);
        }
        $digitalData = $this->buildDigitalDataFromResponse($response);
        $resolvedName = $this->resolveName($response, $firstName);
        [$aiProvider, $aiModel] = $this->resolveProviderAndModelForStorage($requestProvider, $requestModel);

        return [
            'digital_data' => $digitalData,
            'resolved_name' => $resolvedName,
            'ai_provider' => $aiProvider,
            'ai_model' => $aiModel,
        ];
    }

    /**
     * Run extraction from pre-built frame data (base64 + mime). Used by first-frame and batch jobs.
     * Returns raw AI response (type, content, suggested_name, etc.).
     *
     * @param  array<int, array{base64: string, mime: string}>  $framesData
     * @return array<string, mixed>
     */
    public function extractFromAttachments(
        array $framesData,
        ?string $requestProvider,
        ?string $requestModel,
        string $promptContext = ''
    ): array {
        $attachments = array_map(
            fn (array $f) => Image::fromBase64($f['base64'], $f['mime']),
            $framesData
        );
        $agent = $this->agentForProvider($requestProvider);
        $prompt = 'Extract all content from this image or these images (e.g. handwritten or printed text, tables). '
            .($promptContext !== '' ? $promptContext.' ' : '')
            .'Return structured JSON with type (doc or table) and content as described in your instructions. '
            .(count($attachments) > 1 ? 'Do not repeat or duplicate content that appears in more than one image.' : '');
        $response = $agent->prompt($prompt, attachments: $attachments, provider: $requestProvider, model: $requestModel);
        $out = $this->responseToArray($response);
        $effectiveProvider = $requestProvider ?: config('ai.default');
        if ($effectiveProvider === 'nova') {
            $out = $this->normalizeNovaResponse($out);
        }

        return $out;
    }

    /**
     * Build full result (digital_data, resolved_name, ai_provider, ai_model) from raw AI response. For first-frame job.
     *
     * @return array{digital_data: array<string, mixed>, resolved_name: string, ai_provider: string|null, ai_model: string|null}
     */
    public function buildResultFromResponse(
        array $response,
        ?string $nameFromRequest,
        ?string $requestProvider,
        ?string $requestModel
    ): array {
        $digitalData = $this->buildDigitalDataFromResponse($response);
        $resolvedName = $this->resolveName($response, $nameFromRequest);
        [$aiProvider, $aiModel] = $this->resolveProviderAndModelForStorage($requestProvider, $requestModel);

        return [
            'digital_data' => $digitalData,
            'resolved_name' => $resolvedName,
            'ai_provider' => $aiProvider,
            'ai_model' => $aiModel,
        ];
    }

    /**
     * Merge a batch response into existing digital_data (append rows or doc content). Returns updated digital_data.
     *
     * @param  array<string, mixed>  $currentDigitalData
     * @param  array<string, mixed>  $batchResponse
     * @return array<string, mixed>
     */
    public function mergeBatchResponseIntoDigitalData(array $currentDigitalData, array $batchResponse): array
    {
        $type = $currentDigitalData['type'] ?? 'doc';
        if ($type === 'table') {
            $content = $currentDigitalData['content'] ?? '{}';
            $decoded = is_string($content) ? json_decode($content, true) : $content;
            $headers = $decoded['headers'] ?? [];
            $rows = $decoded['rows'] ?? [];
            $batchContent = $batchResponse['content'] ?? '';
            $batchDecoded = is_string($batchContent) ? json_decode($batchContent, true) : [];
            $batchRows = is_array($batchDecoded) ? ($batchDecoded['rows'] ?? []) : [];
            foreach ($batchRows as $row) {
                $rows[] = is_array($row) ? array_values($row) : [];
            }
            $currentDigitalData['content'] = json_encode(self::sanitizeTableForJson(['headers' => $headers, 'rows' => $rows]));
            $currentDigitalData['table_row_count'] = count($rows);

            return $currentDigitalData;
        }
        $docPages = $currentDigitalData['doc_pages'] ?? [];
        if (! is_array($docPages)) {
            $docPages = $currentDigitalData['content'] ? [(string) $currentDigitalData['content']] : [];
        }
        $batchPages = $batchResponse['doc_pages'] ?? null;
        if (is_array($batchPages) && $batchPages !== []) {
            $docPages = array_merge($docPages, $batchPages);
        } else {
            $c = $batchResponse['content'] ?? '';
            if (is_string($c) && $c !== '') {
                $docPages[] = $c;
            }
        }
        $currentDigitalData['doc_page_count'] = count($docPages) ?: 1;
        $currentDigitalData['doc_pages'] = $docPages;
        $currentDigitalData['content'] = implode("\n\n", $docPages);

        return $currentDigitalData;
    }

    private function attachmentsForDigitalize(bool $isImage, string $decoded, string $base64, string $mimeType): array
    {
        if ($isImage) {
            Log::info('[digitalize] attachmentsForDigitalize: single image');

            return [Image::fromBase64($base64, $mimeType)];
        }

        Log::info('[digitalize] attachmentsForDigitalize: video, extracting frames');
        $extractor = new VideoFrameExtractor;
        $frames = $extractor->extractFramesPerSecond($decoded, $mimeType);
        if ($frames === []) {
            Log::warning('[digitalize] attachmentsForDigitalize: no frames extracted, falling back to video as document');

            return [Document::fromBase64($base64, $mimeType)];
        }

        Log::info('[digitalize] attachmentsForDigitalize: using video frames as images', ['frame_count' => count($frames)]);

        return array_map(
            fn (array $f) => Image::fromBase64($f['base64'], $f['mime']),
            $frames
        );
    }

    private function agentForProvider(?string $provider): DigitalizeAgent|DigitalizeAgentNova
    {
        $effective = $provider ?: config('ai.default');

        return $effective === 'nova' ? new DigitalizeAgentNova : new DigitalizeAgent;
    }

    private function runExtraction(DigitalizeAgent|DigitalizeAgentNova $agent, array $attachments, ?string $requestProvider, ?string $requestModel): array
    {
        $batchSize = (int) config('video_extract.batch_size', 20);
        $useBatches = $batchSize > 0 && count($attachments) > $batchSize;

        if (! $useBatches) {
            Log::info('[digitalize] AI extraction: single request', ['attachment_count' => count($attachments), 'provider' => $requestProvider]);
            $prompt = $this->digitalizePrompt($attachments);
            $response = $agent->prompt($prompt, attachments: $attachments, provider: $requestProvider, model: $requestModel);
            $out = $this->responseToArray($response);
            Log::info('[digitalize] AI extraction: single response received', ['type' => $out['type'] ?? null]);

            return $out;
        }

        $batches = array_chunk($attachments, $batchSize);
        $totalBatches = count($batches);
        Log::info('[digitalize] AI extraction: batched', ['total_attachments' => count($attachments), 'batch_size' => $batchSize, 'batch_count' => $totalBatches]);

        $responses = [];
        foreach ($batches as $i => $batch) {
            $start = $i * $batchSize + 1;
            $end = $i * $batchSize + count($batch);
            Log::info('[digitalize] AI extraction: batch request', ['batch_index' => $i + 1, 'batch_total' => $totalBatches, 'frame_range' => "{$start}-{$end}"]);
            $prompt = 'Extract all content from these images (frames '.$start.'–'.$end.' of a video, one frame per second). Return structured JSON with type (doc or table) and content as described in your instructions. Do not repeat or duplicate content that appears in more than one image.';
            $response = $agent->prompt($prompt, attachments: $batch, provider: $requestProvider, model: $requestModel);
            $parsed = $this->responseToArray($response);
            $responses[] = $parsed;
            Log::info('[digitalize] AI extraction: batch response received', ['batch_index' => $i + 1, 'type' => $parsed['type'] ?? null]);
        }

        Log::info('[digitalize] AI extraction: merging batched responses');

        return $this->mergeResponses($responses);
    }

    private function mergeResponses(array $responses): array
    {
        if ($responses === []) {
            return ['type' => 'doc', 'content' => '', 'suggested_prompts' => [], 'insights' => [], 'suggested_name' => ''];
        }
        if (count($responses) === 1) {
            return $responses[0];
        }

        $first = $responses[0];
        $type = $first['type'] ?? 'doc';

        $allPrompts = [];
        $allInsights = [];
        foreach ($responses as $r) {
            foreach ((array) ($r['suggested_prompts'] ?? []) as $p) {
                if (is_string($p) && $p !== '') {
                    $allPrompts[$p] = true;
                }
            }
            foreach ((array) ($r['insights'] ?? []) as $i) {
                if (is_string($i) && $i !== '') {
                    $allInsights[$i] = true;
                }
            }
        }

        if ($type === 'table') {
            $headers = $first['content'] ?? '{}';
            $decoded = is_string($headers) ? json_decode($headers, true) : $headers;
            $mergedHeaders = $decoded['headers'] ?? [];
            $mergedRows = [];
            foreach ($responses as $r) {
                $content = $r['content'] ?? '';
                $data = is_string($content) ? json_decode($content, true) : $content;
                $rows = $data['rows'] ?? [];
                if (is_array($rows)) {
                    foreach ($rows as $row) {
                        $mergedRows[] = is_array($row) ? array_values($row) : [];
                    }
                }
            }
            $content = json_encode(['headers' => $mergedHeaders, 'rows' => $mergedRows]);

            return array_merge($first, ['content' => $content, 'table_row_count' => count($mergedRows), 'suggested_prompts' => array_keys($allPrompts), 'insights' => array_keys($allInsights)]);
        }

        $docParts = [];
        foreach ($responses as $r) {
            $pages = $r['doc_pages'] ?? null;
            if (is_array($pages) && $pages !== []) {
                $docParts = array_merge($docParts, $pages);
            } else {
                $c = $r['content'] ?? '';
                if (is_string($c) && $c !== '') {
                    $docParts[] = $c;
                }
            }
        }
        $docPageCount = count($docParts) ?: 1;
        $content = implode("\n\n", $docParts);

        return array_merge($first, ['content' => $content, 'doc_page_count' => $docPageCount, 'doc_pages' => $docParts, 'suggested_prompts' => array_keys($allPrompts), 'insights' => array_keys($allInsights)]);
    }

    private function digitalizePrompt(array $attachments): string
    {
        $isMultipleFrames = count($attachments) > 1;
        $base = 'Extract all content from this image or video (e.g. handwritten or printed text, tables). Return structured JSON with type (doc or table) and content as described in your instructions.';
        if ($isMultipleFrames) {
            $base .= ' These images are one frame per second from a video—extract from all frames but do not repeat or duplicate content that appears in more than one image.';
        }

        return $base;
    }

    private function responseToArray(array|object $response): array
    {
        if (is_array($response)) {
            return $response;
        }

        return method_exists($response, 'toArray') ? $response->toArray() : (array) $response;
    }

    private function normalizeNovaResponse(array $response): array
    {
        $content = $response['content'] ?? '';
        if (! is_string($content) || $content === '') {
            return $response;
        }
        $stripped = preg_replace('/^\s*```(?:json)?\s*/i', '', $content);
        $stripped = preg_replace('/\s*```\s*$/', '', trim($stripped));
        $decoded = json_decode($stripped, true);
        if (! is_array($decoded) || ! isset($decoded['type'], $decoded['content'])) {
            return $response;
        }

        return array_merge($response, $decoded);
    }

    private function buildDigitalDataFromResponse(array $response): array
    {
        $type = $response['type'] ?? 'doc';
        $content = $response['content'] ?? '';
        if ($type === 'table' && is_array($content)) {
            $content = json_encode(self::sanitizeTableForJson($content));
        }
        $digitalData = [
            'type' => $type,
            'content' => is_string($content) ? $content : (string) json_encode($content),
        ];
        if ($type === 'table' && isset($response['table_row_count'])) {
            $digitalData['table_row_count'] = (int) $response['table_row_count'];
        }
        if ($type === 'doc') {
            $docPages = $response['doc_pages'] ?? null;
            $docPageCount = isset($response['doc_page_count']) ? (int) $response['doc_page_count'] : 1;
            $digitalData['doc_page_count'] = $docPageCount;
            if (is_array($docPages) && $docPageCount > 1) {
                $digitalData['doc_pages'] = array_values($docPages);
                $digitalData['content'] = implode("\n\n", $digitalData['doc_pages']);
            }
        }
        $digitalData['suggested_prompts'] = is_array($response['suggested_prompts'] ?? null)
            ? array_values(array_filter(array_map('strval', $response['suggested_prompts'])))
            : [];
        $digitalData['insights'] = is_array($response['insights'] ?? null)
            ? array_values(array_filter(array_map('strval', $response['insights'])))
            : [];

        return $digitalData;
    }

    /**
     * Strip control characters from table structure so stored JSON is valid.
     *
     * @param  array<string, mixed>  $table  Must have 'headers' and 'rows' keys
     * @return array<string, mixed>
     */
    private static function sanitizeTableForJson(array $table): array
    {
        $headers = $table['headers'] ?? [];
        $rows = $table['rows'] ?? [];
        $sanitize = fn (string $s): string => preg_replace('/[\x00-\x1F]/', ' ', $s);
        $headers = array_map(fn ($h) => $sanitize((string) $h), $headers);
        $rows = array_map(fn (array $row): array => array_map(fn ($cell) => $sanitize((string) $cell), $row), $rows);

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function resolveName(array $response, ?string $nameFromRequest): string
    {
        $suggestedName = isset($response['suggested_name']) && is_scalar($response['suggested_name'])
            ? trim((string) $response['suggested_name'])
            : '';
        if ($suggestedName !== '') {
            $suggestedName = str_replace(["\r", "\n", '/', '\\'], [' ', ' ', ' ', ' '], $suggestedName);
            $suggestedName = trim(preg_replace('/\s+/', ' ', $suggestedName));
        }
        if ($suggestedName !== '' && mb_strlen($suggestedName) <= 255) {
            $resolvedName = $suggestedName;
        } elseif ($nameFromRequest !== null && $nameFromRequest !== '') {
            $resolvedName = trim((string) $nameFromRequest);
        } else {
            $resolvedName = 'document';
        }
        $resolvedName = mb_strlen($resolvedName) > 255 ? mb_substr($resolvedName, 0, 255) : $resolvedName;

        return $resolvedName !== '' ? $resolvedName : 'document';
    }

    private function resolveProviderAndModelForStorage(?string $requestProvider, ?string $requestModel): array
    {
        $provider = $requestProvider ?: config('ai.default');
        if (! is_string($provider) || $provider === '') {
            return [null, null];
        }
        if ($requestModel !== null && $requestModel !== '') {
            return [$provider, $requestModel];
        }
        $providerConfig = config('ai.providers.'.$provider, []);
        $model = $providerConfig['models']['text']['default'] ?? $providerConfig['deployment'] ?? null;

        return [$provider, is_string($model) ? $model : null];
    }
}
