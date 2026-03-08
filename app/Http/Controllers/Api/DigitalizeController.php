<?php

namespace App\Http\Controllers\Api;

use App\Ai\Agents\DigitalizeAgent;
use App\Ai\Agents\DigitalizeAgentNova;
use App\Http\Controllers\Controller;
use App\Jobs\DigitalizeOrchestratorJob;
use App\Jobs\StoreOriginalFileToS3Job;
use App\Models\Data;
use App\Services\VideoFrameExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Files\Document;
use Laravel\Ai\Files\Image;

class DigitalizeController extends Controller
{
    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    private const VIDEO_MIMES = ['video/mp4', 'video/quicktime', 'video/webm'];

    /**
     * Accept file (image or video) via multipart/form-data or base64 JSON, store to S3/local, send to AI, save structured result to Data.
     *
     * Multipart: file (file), name (optional).
     * JSON body: { "file": "base64string" or "data:image/png;base64,...", "name": "optional", "mime_type": "optional if in data URL" }
     */
    public function store(Request $request): JsonResponse
    {
        $allowedMimes = array_merge(self::IMAGE_MIMES, self::VIDEO_MIMES);
        $mimeRule = 'in:'.implode(',', $allowedMimes);

        $digitalizeProviders = array_keys(config('ai.digitalize_providers', []));
        if ($request->hasFile('file')) {
            $request->validate([
                'file' => ['required', 'file', 'mimetypes:'.implode(',', $allowedMimes), 'max:20480'],
                'name' => 'nullable|string|max:255',
                'ai_provider' => 'nullable|string|in:'.implode(',', $digitalizeProviders),
                'ai_model' => 'nullable|string|max:255',
            ]);
            $uploaded = $request->file('file');
            $mimeType = $uploaded->getMimeType();
            if (! in_array($mimeType, $allowedMimes, true)) {
                return response()->json(['message' => 'Allowed mime types: '.implode(', ', $allowedMimes)], 422);
            }
            $decoded = $uploaded->get();
            $base64 = base64_encode($decoded);
            $nameFromRequest = $request->input('name') ?: pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME);
        } else {
            $request->validate([
                'file' => 'required|string',
                'name' => 'nullable|string|max:255',
                'mime_type' => 'nullable|string|'.$mimeRule,
                'ai_provider' => 'nullable|string|in:'.implode(',', $digitalizeProviders),
                'ai_model' => 'nullable|string|max:255',
            ]);

            $fileInput = $request->input('file');
            $mimeType = $request->input('mime_type');
            $base64 = $fileInput;

            if (str_starts_with($fileInput, 'data:')) {
                if (! preg_match('/^data:([^;]+);base64,(.+)$/', $fileInput, $m)) {
                    return response()->json(['message' => 'Invalid data URL for file.'], 422);
                }
                $mimeType = $m[1];
                $base64 = $m[2];
            }

            if (! $mimeType) {
                return response()->json(['message' => 'mime_type is required when file is not a data URL.'], 422);
            }

            if (! in_array($mimeType, $allowedMimes, true)) {
                return response()->json(['message' => 'Allowed mime types: '.implode(', ', $allowedMimes)], 422);
            }

            $decoded = base64_decode($base64, true);
            if ($decoded === false) {
                return response()->json(['message' => 'Invalid base64 in file.'], 422);
            }
            $nameFromRequest = $request->input('name');
        }

        $isImage = in_array($mimeType, self::IMAGE_MIMES, true);
        $requestProvider = $request->input('ai_provider');
        $requestModel = $request->input('ai_model') ?: null;

        Log::info('[digitalize] store: start (async)', [
            'mime' => $mimeType,
            'size_bytes' => strlen($decoded),
            'input_type' => $isImage ? 'image' : 'video',
        ]);

        $disk = config('filesystems.default');
        $path = null;

        try {
            DB::beginTransaction();

            $ext = $this->mimeToExt($mimeType);
            $path = 'digitalize/'.Str::uuid().'.'.$ext;
            Storage::disk($disk)->put($path, $decoded);

            $initialName = $nameFromRequest !== null && $nameFromRequest !== '' ? pathinfo(trim($nameFromRequest), PATHINFO_FILENAME) : 'Processing…';
            $rawData = [
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $mimeType,
                'name_from_request' => $nameFromRequest,
                'ai_provider' => $requestProvider,
                'ai_model' => $requestModel,
            ];

            $pendingDigitalData = [
                'type' => 'pending',
                'status' => 'processing',
            ];

            $data = Data::create([
                'user_id' => $request->user()->id,
                'name' => $initialName,
                'status' => 'processing',
                'raw_data' => $rawData,
                'digital_data' => $pendingDigitalData,
                'ai_provider' => null,
                'ai_model' => null,
            ]);

            DB::commit();

            DigitalizeOrchestratorJob::dispatch($data->id);
            StoreOriginalFileToS3Job::dispatch($data->id);

            Log::info('[digitalize] store: job dispatched', ['data_id' => $data->id]);

            return response()->json([
                'id' => $data->id,
                'name' => $data->name,
                'status' => 'processing',
                'digital_data' => $data->digital_data,
            ], 202);
        } catch (\Throwable $e) {
            Log::error('[digitalize] store: failed', [
                'error' => $e->getMessage(),
                'path_stored' => $path,
            ]);
            DB::rollBack();
            if ($path !== null) {
                try {
                    Storage::disk($disk)->delete($path);
                } catch (\Throwable) {
                    // ignore cleanup failure
                }
            }
            throw $e;
        }
    }

    /**
     * Accept multiple files at once; store all and create a single Data record.
     * All files are processed together as one extraction (one doc or table).
     */
    public function storeBatch(Request $request): JsonResponse
    {
        $allowedMimes = array_merge(self::IMAGE_MIMES, self::VIDEO_MIMES);
        $digitalizeProviders = array_keys(config('ai.digitalize_providers', []));

        $request->validate([
            'files' => 'required|array',
            'files.*' => ['required', 'file', 'mimetypes:'.implode(',', $allowedMimes), 'max:20480'],
            'ai_provider' => 'nullable|string|in:'.implode(',', $digitalizeProviders),
            'ai_model' => 'nullable|string|max:255',
        ]);

        $uploadedFiles = $request->file('files');
        if (! is_array($uploadedFiles) || count($uploadedFiles) < 2) {
            return response()->json(['message' => 'Use storeBatch only when uploading 2 or more files. Use the single-file upload for one file.'], 422);
        }

        $disk = config('filesystems.default');
        $requestProvider = $request->input('ai_provider');
        $requestModel = $request->input('ai_model') ?: null;
        $storedPaths = [];
        $firstName = null;

        try {
            DB::beginTransaction();

            foreach ($uploadedFiles as $index => $uploaded) {
                $mimeType = $uploaded->getMimeType();
                if (! in_array($mimeType, $allowedMimes, true)) {
                    throw new \InvalidArgumentException('Allowed mime types: '.implode(', ', $allowedMimes));
                }
                $decoded = $uploaded->get();
                $ext = $this->mimeToExt($mimeType);
                $path = 'digitalize/'.Str::uuid().'.'.$ext;
                Storage::disk($disk)->put($path, $decoded);
                $nameFromRequest = pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME);
                if ($firstName === null && $nameFromRequest !== '') {
                    $firstName = $nameFromRequest;
                }
                $storedPaths[] = [
                    'disk' => $disk,
                    'path' => $path,
                    'mime_type' => $mimeType,
                    'name_from_request' => $nameFromRequest,
                ];
            }

            $initialName = $firstName !== null && $firstName !== '' ? $firstName : 'Processing…';
            $rawData = [
                'disk' => $disk,
                'files' => $storedPaths,
                'ai_provider' => $requestProvider,
                'ai_model' => $requestModel,
            ];
            $pendingDigitalData = [
                'type' => 'pending',
                'status' => 'processing',
            ];

            $data = Data::create([
                'user_id' => $request->user()->id,
                'name' => $initialName,
                'status' => 'processing',
                'raw_data' => $rawData,
                'digital_data' => $pendingDigitalData,
                'ai_provider' => null,
                'ai_model' => null,
            ]);

            DB::commit();

            DigitalizeOrchestratorJob::dispatch($data->id);
            StoreOriginalFileToS3Job::dispatch($data->id);

            Log::info('[digitalize] storeBatch: jobs dispatched', ['data_id' => $data->id, 'file_count' => count($storedPaths)]);

            return response()->json([
                'id' => $data->id,
                'name' => $data->name,
                'status' => 'processing',
                'digital_data' => $data->digital_data,
            ], 202);
        } catch (\Throwable $e) {
            Log::error('[digitalize] storeBatch: failed', ['error' => $e->getMessage()]);
            DB::rollBack();
            foreach ($storedPaths as $entry) {
                try {
                    Storage::disk($entry['disk'])->delete($entry['path']);
                } catch (\Throwable) {
                    // ignore
                }
            }
            throw $e;
        }
    }

    /**
     * Append rows to an existing table from an uploaded photo or video.
     * Same allowed types as store(). Extracted data must be type "table"; rows are appended to match existing headers.
     */
    public function appendToTable(Request $request, Data $data): JsonResponse
    {
        if ($data->user_id !== $request->user()->id) {
            abort(404);
        }
        $digital = $data->digital_data;
        if (! is_array($digital) || ($digital['type'] ?? null) !== 'table') {
            return response()->json(['message' => 'This record is not a table. Appending is only supported for tables.'], 422);
        }

        $allowedMimes = array_merge(self::IMAGE_MIMES, self::VIDEO_MIMES);
        $digitalizeProviders = array_keys(config('ai.digitalize_providers', []));
        $request->validate([
            'file' => ['required', 'file', 'mimetypes:'.implode(',', $allowedMimes), 'max:20480'],
            'ai_provider' => 'nullable|string|in:'.implode(',', $digitalizeProviders),
            'ai_model' => 'nullable|string|max:255',
        ]);
        $uploaded = $request->file('file');
        $mimeType = $uploaded->getMimeType();
        if (! in_array($mimeType, $allowedMimes, true)) {
            return response()->json(['message' => 'Allowed: images (JPEG, PNG, GIF, WebP) or video (MP4, WebM).'], 422);
        }

        $decoded = $uploaded->get();
        $base64 = base64_encode($decoded);
        $isImage = in_array($mimeType, self::IMAGE_MIMES, true);

        Log::info('[digitalize] appendToTable: start', [
            'data_id' => $data->id,
            'mime' => $mimeType,
            'size_bytes' => strlen($decoded),
            'input_type' => $isImage ? 'image' : 'video',
        ]);

        $attachments = $this->attachmentsForDigitalize($isImage, $decoded, $base64, $mimeType);
        Log::info('[digitalize] appendToTable: attachments prepared', [
            'attachment_count' => count($attachments),
        ]);

        $requestProvider = $request->input('ai_provider');
        $requestModel = $request->input('ai_model') ?: null;
        $agent = $this->digitalizeAgentForProvider($requestProvider);
        $response = $this->runDigitalizeExtraction($agent, $attachments, $requestProvider, $requestModel);

        $effectiveProvider = $requestProvider ?: config('ai.default');
        if ($effectiveProvider === 'nova') {
            $response = $this->normalizeDigitalizeResponse($response);
            Log::info('[digitalize] appendToTable: Nova response normalized');
        }

        $type = $response['type'] ?? 'doc';
        Log::info('[digitalize] appendToTable: AI response', ['type' => $type]);
        if ($type !== 'table') {
            return response()->json(['message' => 'The upload did not contain table data. Use a photo or video of a table to add rows.'], 422);
        }

        $content = $response['content'] ?? '';
        $decoded = is_string($content) ? json_decode($content, true) : $content;
        if (! is_array($decoded)) {
            return response()->json(['message' => 'Could not parse extracted table data.'], 422);
        }
        $newRows = $decoded['rows'] ?? [];
        if (! is_array($newRows)) {
            $newRows = [];
        }

        $existingContent = $digital['content'] ?? '{}';
        $existingDecoded = is_array($existingContent) ? $existingContent : (json_decode(is_string($existingContent) ? $existingContent : '{}', true) ?: []);
        $existingHeaders = $existingDecoded['headers'] ?? [];
        $headerCount = count($existingHeaders);
        if ($headerCount === 0) {
            return response()->json(['message' => 'This table has no columns. Add columns first or add rows manually.'], 422);
        }

        if ($data->tableRows()->count() === 0) {
            $data->syncTableRowsFromDigitalData();
        }
        $maxIndex = (int) $data->tableRows()->max('row_index');

        $added = 0;
        foreach ($newRows as $row) {
            $cells = is_array($row) ? array_values($row) : [];
            $cells = array_slice($cells, 0, $headerCount);
            while (count($cells) < $headerCount) {
                $cells[] = '';
            }
            $searchContent = implode(' ', array_map(fn ($v) => (string) $v, $cells));
            $data->tableRows()->create([
                'row_index' => ++$maxIndex,
                'search_content' => $searchContent,
                'cells' => $cells,
            ]);
            $added++;
        }

        $data->rebuildDigitalDataRowsFromTableRows();

        Log::info('[digitalize] appendToTable: complete', [
            'data_id' => $data->id,
            'rows_added' => $added,
        ]);

        return response()->json([
            'added' => $added,
            'message' => $added === 1 ? '1 row added.' : "{$added} rows added.",
        ], 201);
    }

    /**
     * Append content to an existing document from an uploaded photo or video.
     * Extracted content is merged as new page(s). AI is instructed not to duplicate content.
     */
    public function appendToDoc(Request $request, Data $data): JsonResponse
    {
        if ($data->user_id !== $request->user()->id) {
            abort(404);
        }
        $digital = $data->digital_data;
        if (! is_array($digital) || ($digital['type'] ?? null) !== 'doc') {
            return response()->json(['message' => 'This record is not a document. Appending is only supported for documents.'], 422);
        }

        $allowedMimes = array_merge(self::IMAGE_MIMES, self::VIDEO_MIMES);
        $digitalizeProviders = array_keys(config('ai.digitalize_providers', []));
        $request->validate([
            'file' => ['required', 'file', 'mimetypes:'.implode(',', $allowedMimes), 'max:20480'],
            'ai_provider' => 'nullable|string|in:'.implode(',', $digitalizeProviders),
            'ai_model' => 'nullable|string|max:255',
        ]);
        $uploaded = $request->file('file');
        $mimeType = $uploaded->getMimeType();
        if (! in_array($mimeType, $allowedMimes, true)) {
            return response()->json(['message' => 'Allowed: images (JPEG, PNG, GIF, WebP) or video (MP4, WebM).'], 422);
        }

        $decoded = $uploaded->get();
        $base64 = base64_encode($decoded);
        $isImage = in_array($mimeType, self::IMAGE_MIMES, true);

        Log::info('[digitalize] appendToDoc: start', [
            'data_id' => $data->id,
            'mime' => $mimeType,
            'size_bytes' => strlen($decoded),
            'input_type' => $isImage ? 'image' : 'video',
        ]);

        $attachments = $this->attachmentsForDigitalize($isImage, $decoded, $base64, $mimeType);
        $requestProvider = $request->input('ai_provider');
        $requestModel = $request->input('ai_model') ?: null;
        $agent = $this->digitalizeAgentForProvider($requestProvider);

        $promptSuffix = 'This content will be appended to an existing document. Extract only new content; do not repeat or duplicate content that appears in more than one image, and omit any text that is likely already in the document (e.g. repeated headers or titles).';
        $response = $this->runDigitalizeExtraction($agent, $attachments, $requestProvider, $requestModel, $promptSuffix);

        $effectiveProvider = $requestProvider ?: config('ai.default');
        if ($effectiveProvider === 'nova') {
            $response = $this->normalizeDigitalizeResponse($response);
        }

        $type = $response['type'] ?? 'doc';
        if ($type !== 'doc') {
            return response()->json(['message' => 'The upload did not contain document content. Use a photo or video of text to append to the document.'], 422);
        }

        $existingPages = $digital['doc_pages'] ?? null;
        if (is_array($existingPages) && $existingPages !== []) {
            $existingParts = $existingPages;
        } else {
            $content = (string) ($digital['content'] ?? '');
            $existingParts = $content !== '' ? [$content] : [];
        }

        $newPages = $response['doc_pages'] ?? null;
        if (is_array($newPages) && $newPages !== []) {
            $newParts = $newPages;
        } else {
            $c = $response['content'] ?? '';
            $newParts = is_string($c) && $c !== '' ? [$c] : [];
        }

        if ($newParts === []) {
            return response()->json(['message' => 'No new content was extracted from the upload.'], 422);
        }

        $merged = array_merge($existingParts, $newParts);
        $digital['doc_page_count'] = count($merged);
        $digital['doc_pages'] = $merged;
        $digital['content'] = implode("\n\n", $merged);
        $data->update(['digital_data' => $digital]);

        $added = count($newParts);
        Log::info('[digitalize] appendToDoc: complete', [
            'data_id' => $data->id,
            'pages_added' => $added,
            'total_pages' => count($merged),
        ]);

        return response()->json([
            'added' => $added,
            'message' => $added === 1 ? '1 page added to document.' : "{$added} pages added to document.",
        ], 201);
    }

    /**
     * Return available AI providers and models for digitalize (frontend selector).
     */
    public function digitalizeOptions(): JsonResponse
    {
        $config = config('ai.digitalize_providers', []);
        $defaultProvider = config('ai.default');
        $options = [];
        foreach ($config as $id => $entry) {
            $name = is_array($entry) ? ($entry['name'] ?? $id) : (string) $entry;
            $item = ['id' => $id, 'name' => $name, 'models' => []];
            if (is_array($entry) && ! empty($entry['models'])) {
                foreach ($entry['models'] as $modelId => $modelName) {
                    $item['models'][] = ['id' => $modelId, 'name' => $modelName];
                }
            }
            $options[] = $item;
        }

        return response()->json([
            'providers' => $options,
            'default_provider' => $defaultProvider,
        ]);
    }

    /**
     * List all Data records for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $items = Data::query()
            ->forUser($request->user()->id)
            ->latest()
            ->get()
            ->map(fn (Data $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'type' => $d->digital_data['type'] ?? null,
                'created_at' => $d->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $items]);
    }

    /**
     * Get a single Data record by id (must belong to the authenticated user).
     */
    public function show(Request $request, Data $data): JsonResponse
    {
        if ($data->user_id !== $request->user()->id) {
            abort(404);
        }

        return response()->json([
            'id' => $data->id,
            'name' => $data->name,
            'raw_data' => $data->raw_data,
            'digital_data' => $data->digital_data,
            'created_at' => $data->created_at,
            'updated_at' => $data->updated_at,
        ]);
    }

    /**
     * Run digitalize extraction: one AI call, or batched calls when frame count exceeds batch_size.
     *
     * @param  array<int, \Laravel\Ai\Files\Image|\Laravel\Ai\Files\Document>  $attachments
     * @param  string|null  $promptSuffix  Optional instruction appended to the prompt (e.g. for append-to-doc).
     * @return array<string, mixed>
     */
    private function runDigitalizeExtraction(DigitalizeAgent|DigitalizeAgentNova $agent, array $attachments, ?string $requestProvider, ?string $requestModel, ?string $promptSuffix = null): array
    {
        $batchSize = (int) config('video_extract.batch_size', 20);
        $useBatches = $batchSize > 0 && count($attachments) > $batchSize;

        if (! $useBatches) {
            Log::info('[digitalize] AI extraction: single request', [
                'attachment_count' => count($attachments),
                'provider' => $requestProvider,
            ]);
            $prompt = $this->digitalizePrompt($attachments).($promptSuffix !== null ? ' '.$promptSuffix : '');
            $response = $agent->prompt(
                $prompt,
                attachments: $attachments,
                provider: $requestProvider,
                model: $requestModel,
            );
            $out = $this->digitalizeResponseToArray($response);
            Log::info('[digitalize] AI extraction: single response received', [
                'type' => $out['type'] ?? null,
            ]);

            return $out;
        }

        $batches = array_chunk($attachments, $batchSize);
        $totalBatches = count($batches);
        Log::info('[digitalize] AI extraction: batched', [
            'total_attachments' => count($attachments),
            'batch_size' => $batchSize,
            'batch_count' => $totalBatches,
            'provider' => $requestProvider,
        ]);

        $responses = [];
        foreach ($batches as $i => $batch) {
            $start = $i * $batchSize + 1;
            $end = $i * $batchSize + count($batch);
            Log::info('[digitalize] AI extraction: batch request', [
                'batch_index' => $i + 1,
                'batch_total' => $totalBatches,
                'frame_range' => "{$start}-{$end}",
                'images_in_batch' => count($batch),
            ]);
            $batchPromptSuffix = $promptSuffix !== null ? ' '.$promptSuffix : '';
            $prompt = 'Extract all content from these images (frames '.$start.'–'.$end.' of a video, one frame per second). Return structured JSON with type (doc or table) and content as described in your instructions. Do not repeat or duplicate content that appears in more than one image.'.$batchPromptSuffix;
            $response = $agent->prompt(
                $prompt,
                attachments: $batch,
                provider: $requestProvider,
                model: $requestModel,
            );
            $parsed = $this->digitalizeResponseToArray($response);
            $responses[] = $parsed;
            Log::info('[digitalize] AI extraction: batch response received', [
                'batch_index' => $i + 1,
                'type' => $parsed['type'] ?? null,
            ]);
        }

        Log::info('[digitalize] AI extraction: merging batched responses');

        return $this->mergeDigitalizeResponses($responses);
    }

    /**
     * Merge multiple digitalize responses (from batched video frames) into one.
     *
     * @param  array<int, array<string, mixed>>  $responses
     * @return array<string, mixed>
     */
    private function mergeDigitalizeResponses(array $responses): array
    {
        if ($responses === []) {
            Log::info('[digitalize] merge: no responses, returning empty');

            return ['type' => 'doc', 'content' => '', 'suggested_prompts' => [], 'insights' => [], 'suggested_name' => ''];
        }
        if (count($responses) === 1) {
            return $responses[0];
        }

        $first = $responses[0];
        $type = $first['type'] ?? 'doc';
        Log::info('[digitalize] merge: merging responses', [
            'response_count' => count($responses),
            'merged_type' => $type,
        ]);

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
            Log::info('[digitalize] merge: table merged', ['total_rows' => count($mergedRows), 'header_count' => count($mergedHeaders)]);

            return array_merge($first, [
                'content' => $content,
                'table_row_count' => count($mergedRows),
                'suggested_prompts' => array_keys($allPrompts),
                'insights' => array_keys($allInsights),
            ]);
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
        Log::info('[digitalize] merge: doc merged', ['doc_page_count' => $docPageCount]);

        return array_merge($first, [
            'content' => $content,
            'doc_page_count' => $docPageCount,
            'doc_pages' => $docParts,
            'suggested_prompts' => array_keys($allPrompts),
            'insights' => array_keys($allInsights),
        ]);
    }

    /**
     * Build prompt for digitalize; when multiple images (video frames), remind AI not to repeat content.
     *
     * @param  array<int, \Laravel\Ai\Files\Image|\Laravel\Ai\Files\Document>  $attachments
     */
    private function digitalizePrompt(array $attachments): string
    {
        $isMultipleFrames = count($attachments) > 1;
        $base = 'Extract all content from this image or video (e.g. handwritten or printed text, tables). Return structured JSON with type (doc or table) and content as described in your instructions.';
        if ($isMultipleFrames) {
            $base .= ' These images are one frame per second from a video—extract from all frames but do not repeat or duplicate content that appears in more than one image.';
        }

        return $base;
    }

    /**
     * Build attachments for AI: one image/document, or for video, one Image per second (via ffmpeg).
     *
     * @return array<int, \Laravel\Ai\Files\Image|\Laravel\Ai\Files\Document>
     */
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

    /**
     * Ensure the agent response is an array (Nova returns StructuredAgentResponse).
     *
     * @param  array<string, mixed>|object  $response
     * @return array<string, mixed>
     */
    private function digitalizeResponseToArray(array|object $response): array
    {
        if (is_array($response)) {
            return $response;
        }

        return method_exists($response, 'toArray') ? $response->toArray() : (array) $response;
    }

    /**
     * Unwrap response when Nova puts the full JSON inside the content field (e.g. "```json\n{...}\n```").
     * Only called when ai.default is nova so other providers are never affected.
     *
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function normalizeDigitalizeResponse(array $response): array
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

    /**
     * Resolve the digitalize agent: Nova-specific agent when provider is Nova, else default agent.
     *
     * @param  string|null  $provider  Requested provider key (e.g. nova, openai) or null for config default
     */
    private function digitalizeAgentForProvider(?string $provider): DigitalizeAgent|DigitalizeAgentNova
    {
        $effective = $provider ?: config('ai.default');

        return $effective === 'nova'
            ? new DigitalizeAgentNova
            : new DigitalizeAgent;
    }

    /**
     * Provider and model for storage: from request or config default for the chosen provider.
     *
     * @return array{0: string|null, 1: string|null} [provider, model]
     */
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
        $model = $providerConfig['models']['text']['default']
            ?? $providerConfig['deployment']
            ?? null;

        return [$provider, is_string($model) ? $model : null];
    }

    /**
     * Default AI provider and model used for extraction (from config).
     *
     * @return array{0: string|null, 1: string|null} [provider, model]
     */
    private function defaultAiProviderAndModel(): array
    {
        return $this->resolveProviderAndModelForStorage(null, null);
    }

    private function mimeToExt(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/webm' => 'webm',
            default => 'bin',
        };
    }
}
