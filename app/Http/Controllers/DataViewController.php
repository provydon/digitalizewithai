<?php

namespace App\Http\Controllers;

use App\Ai\Agents\ChartSuggestionAgent;
use App\Ai\Agents\DataInsightAgent;
use App\Ai\Agents\DataInsightAgenticAgent;
use App\Ai\Agents\DataInsightStreamingAgent;
use App\Models\Data;
use App\Models\SavedDataChart;
use App\Models\SavedDataChat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Ai\Files\Image;

class DataViewController extends Controller
{
    public function show(Request $request, Data $data): Response|JsonResponse
    {
        if ($data->user_id !== auth()->id()) {
            abort(404);
        }

        $from = $request->query('from', 'dashboard');
        $from = in_array($from, ['dashboard', 'data'], true) ? $from : 'dashboard';

        return Inertia::render('Data/Show', [
            'id' => $data->id,
            'from' => $from,
        ]);
    }

    /** Single data record (JSON, same session auth as dashboard). Only if owned by current user. */
    public function dataShow(Data $data): JsonResponse
    {
        if ($data->user_id !== auth()->id()) {
            abort(404);
        }

        $digitalData = $data->digital_data;
        if (is_array($digitalData)) {
            if (($digitalData['type'] ?? '') === 'doc') {
                $pageCount = (int) ($digitalData['doc_page_count'] ?? 1);
                if ($pageCount > 1) {
                    $digitalData = array_diff_key($digitalData, array_flip(['content', 'doc_pages']));
                }
            }
            if (($digitalData['type'] ?? '') === 'table' && isset($digitalData['content'])) {
                $digitalData['content'] = $this->normalizeTableContent($data, $digitalData['content']);
            }
        }

        $dd = $data->digital_data;
        $processing = is_array($dd) && ($dd['status'] ?? null) === 'processing';
        $failed = is_array($dd) && ($dd['status'] ?? null) === 'failed';
        $status = $data->status ?? ($failed ? 'failed' : ($processing ? 'processing' : 'ready'));

        $raw = $data->raw_data;
        $hasOriginalFile = is_array($raw) && (isset($raw['path']) || isset($raw['s3_key']));

        return response()->json([
            'id' => $data->id,
            'name' => $data->name,
            'folder_id' => $data->folder_id,
            'status' => $status,
            'raw_data' => $data->raw_data,
            'digital_data' => $digitalData,
            'ai_provider' => $data->ai_provider,
            'ai_model' => $data->ai_model,
            'created_at' => $data->created_at?->toIso8601String(),
            'updated_at' => $data->updated_at?->toIso8601String(),
            'has_original_file' => $hasOriginalFile,
            'extraction_duration_seconds' => $data->extraction_duration_seconds,
            'extraction_started_at' => $data->extraction_started_at?->toIso8601String(),
            'extraction_failure_message' => $data->extraction_failure_message,
        ]);
    }

    /**
     * Ensure table content is valid JSON so the frontend can parse it.
     * - Strips control characters (e.g. tab/newline in cell values) that break JSON.
     * - Optionally fixes trailing extra "}" from merge bugs.
     * - If still invalid, rebuilds from data_table_rows so the UI always gets valid content.
     *
     * @param  array<string, mixed>|string  $content
     */
    private function normalizeTableContent(Data $data, array|string $content): string
    {
        if (is_array($content)) {
            return json_encode($content);
        }
        $s = trim($content);
        if ($s === '' || $s === '{}') {
            return '{"headers":[],"rows":[]}';
        }
        $s = preg_replace('/[\x00-\x1F]/', ' ', $s);
        if (json_decode($s) !== null) {
            return $s;
        }
        while (str_ends_with($s, '}') && strlen($s) > 1) {
            $s = substr($s, 0, -1);
            if (json_decode($s) !== null) {
                return $s;
            }
        }

        $rebuilt = $this->rebuildTableContentFromRows($data);
        if ($rebuilt !== null) {
            return $rebuilt;
        }

        return $content;
    }

    /**
     * Build valid table JSON from data_table_rows when stored content is corrupted.
     *
     * @return string|null Valid JSON string or null if no rows / not a table
     */
    private function rebuildTableContentFromRows(Data $data): ?string
    {
        $digital = $data->digital_data;
        if (! is_array($digital) || ($digital['type'] ?? '') !== 'table') {
            return null;
        }
        $rows = $data->tableRows()->orderBy('row_index')->get();
        if ($rows->isEmpty()) {
            return null;
        }
        $headers = [];
        $raw = is_string($digital['content']) ? $digital['content'] : json_encode($digital['content'] ?? '{}');
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && ! empty($decoded['headers'])) {
            $headers = $decoded['headers'];
        }
        if ($headers === []) {
            $firstCells = $rows->first()->cells ?? [];
            $count = is_array($firstCells) ? count($firstCells) : 0;
            $headers = $count > 0 ? array_map(fn ($i) => (string) ($i + 1), range(0, $count - 1)) : [];
        }
        $rowsArray = $rows->pluck('cells')->map(fn ($c) => array_values((array) $c))->all();

        return json_encode(['headers' => $headers, 'rows' => $rowsArray]);
    }

    /** Stream or download the original uploaded file (from local disk or S3). */
    public function originalFile(Request $request, Data $data)
    {
        if ($data->user_id !== auth()->id()) {
            abort(404);
        }

        $raw = $data->raw_data;
        if (! is_array($raw)) {
            abort(404);
        }

        $files = $raw['files'] ?? null;
        if (is_array($files) && $files !== []) {
            $first = $files[0];
            $path = $first['s3_key'] ?? $first['path'] ?? null;
            $disk = ! empty($first['s3_key']) ? 's3' : ($first['disk'] ?? $raw['disk'] ?? config('filesystems.default'));
            $mime = $first['mime_type'] ?? 'application/octet-stream';
            $name = $first['name_from_request'] ?? 'original';
        } else {
            $path = $raw['s3_key'] ?? $raw['path'] ?? null;
            $disk = ! empty($raw['s3_key']) ? 's3' : ($raw['disk'] ?? config('filesystems.default'));
            $mime = $raw['mime_type'] ?? 'application/octet-stream';
            $name = $raw['name_from_request'] ?? 'original';
        }

        if (! $path) {
            abort(404);
        }
        if (! Storage::disk($disk)->exists($path)) {
            abort(404);
        }
        $ext = pathinfo($path, PATHINFO_EXTENSION) ?: '';
        if ($ext && ! str_contains($name, '.')) {
            $name .= '.'.$ext;
        }
        $disposition = $request->query('download') ? 'attachment' : 'inline';

        $stream = Storage::disk($disk)->readStream($path);
        if ($stream === false) {
            abort(500);
        }

        return response()->stream(function () use ($stream) {
            try {
                fpassthru($stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition.'; filename="'.addslashes($name).'"',
        ]);
    }

    /** One page of doc content (backend-powered pagination). GET ?page=1 */
    public function docPage(Request $request, Data $data): JsonResponse
    {
        if ($data->user_id !== auth()->id()) {
            abort(404);
        }
        $digital = $data->digital_data;
        if (! is_array($digital) || ($digital['type'] ?? '') !== 'doc') {
            abort(404);
        }
        $pageCount = (int) ($digital['doc_page_count'] ?? 1);
        $docPages = $digital['doc_pages'] ?? null;
        $page = max(1, min($pageCount, (int) $request->input('page', 1)));
        $content = '';
        if (is_array($docPages) && isset($docPages[$page - 1])) {
            $content = (string) $docPages[$page - 1];
        } else {
            $content = (string) ($digital['content'] ?? '');
        }

        return response()->json([
            'page' => $page,
            'total_pages' => $pageCount,
            'content' => $content,
        ]);
    }

    /** Full doc content (e.g. for export). For multi-page docs also returns pages[] for sectioned scroll. */
    public function docContent(Data $data): JsonResponse
    {
        if ($data->user_id !== auth()->id()) {
            abort(404);
        }
        $digital = $data->digital_data;
        if (! is_array($digital) || ($digital['type'] ?? '') !== 'doc') {
            abort(404);
        }
        $content = $digital['content'] ?? '';
        $docPages = $digital['doc_pages'] ?? null;
        if ($content === '' && is_array($docPages) && $docPages !== []) {
            $content = implode("\n\n", $docPages);
        }
        $payload = ['content' => $content];
        if (is_array($docPages) && $docPages !== []) {
            $payload['pages'] = $docPages;
        }

        return response()->json($payload);
    }

    /** Update data record (name and/or folder_id). PATCH body: { "name": "..." } and/or { "folder_id": null|id }. */
    public function update(Request $request, Data $data): JsonResponse
    {
        if ($data->user_id !== auth()->id()) {
            abort(404);
        }
        $updates = [];
        if (array_key_exists('name', $request->all())) {
            $name = $request->input('name');
            if (! is_string($name) || trim($name) === '') {
                return response()->json(['message' => 'name must be non-empty when provided.'], 422);
            }
            $updates['name'] = trim($name);
        }
        if ($request->has('folder_id')) {
            $folderId = $request->input('folder_id');
            if ($folderId !== null) {
                $folder = \App\Models\Folder::where('id', $folderId)->forUser(auth()->id())->first();
                if (! $folder) {
                    return response()->json(['message' => 'Folder not found.'], 404);
                }
            }
            $updates['folder_id'] = $folderId;
        }
        if ($updates !== []) {
            $data->update($updates);
        }

        $payload = ['name' => $data->name];
        if (array_key_exists('folder_id', $updates)) {
            $payload['folder_id'] = $data->folder_id;
        }

        return response()->json($payload);
    }

    /**
     * Update doc content (inline edit).
     * PATCH body: { "content": "..." } for full-doc edit (splits back into existing page count);
     * or { "page": 1, "content": "..." } to replace a single page (legacy).
     */
    public function updateDocContent(Request $request, Data $data): JsonResponse
    {
        if ($data->user_id !== auth()->id()) {
            abort(404);
        }
        $digital = $data->digital_data;
        if (! is_array($digital) || ($digital['type'] ?? '') !== 'doc') {
            abort(404);
        }
        $content = $request->input('content');
        if (! is_string($content)) {
            return response()->json(['message' => 'content string is required.'], 422);
        }
        $page = $request->input('page');
        $pageCount = (int) ($digital['doc_page_count'] ?? 1);

        if ($page !== null && $pageCount > 1) {
            $pageNum = max(1, min($pageCount, (int) $page));
            $docPages = $digital['doc_pages'] ?? [];
            if (! is_array($docPages)) {
                $docPages = [];
            }
            while (count($docPages) < $pageCount) {
                $docPages[] = '';
            }
            $docPages[$pageNum - 1] = $content;
            $digital['doc_pages'] = $docPages;
            $digital['content'] = implode("\n\n", $docPages);
        } else {
            if ($pageCount > 1) {
                $docPages = $this->splitContentIntoPages($content, $pageCount);
                $digital['doc_pages'] = $docPages;
                $digital['content'] = $content;
            } else {
                $digital['content'] = $content;
                $digital['doc_pages'] = [$content];
            }
        }
        $data->update(['digital_data' => $digital]);

        return response()->json(['content' => $content]);
    }

    /**
     * Split a single content string into N roughly equal segments (preserves page segment count).
     * Prefers splitting at double newline (paragraph) boundaries when possible.
     */
    private function splitContentIntoPages(string $content, int $pageCount): array
    {
        if ($pageCount <= 1) {
            return [$content];
        }
        $len = strlen($content);
        $targetChunk = (int) ceil($len / $pageCount);
        $pages = [];
        $offset = 0;
        for ($i = 0; $i < $pageCount; $i++) {
            $isLast = $i === $pageCount - 1;
            $end = $isLast ? $len : min($offset + $targetChunk, $len);
            if (! $isLast && $end < $len) {
                $nextNewline = strpos($content, "\n\n", $end);
                if ($nextNewline !== false && $nextNewline - $offset <= $targetChunk + 500) {
                    $end = $nextNewline + 2;
                } else {
                    $prevNewline = strrpos(substr($content, $offset, $end - $offset), "\n\n");
                    if ($prevNewline !== false) {
                        $end = $offset + $prevNewline + 2;
                    }
                }
            }
            $pages[] = substr($content, $offset, $end - $offset);
            $offset = $end;
        }

        return $pages;
    }

    /**
     * Ask AI about this data record (question, insights, chart suggestions). Expects JSON: { "question": "..." }.
     */
    public function ask(Request $request, Data $data): JsonResponse
    {
        if ($data->user_id !== auth()->id()) {
            abort(404);
        }

        $question = $request->input('question');
        if (! is_string($question) || trim($question) === '') {
            return response()->json(['message' => 'Question is required.'], 422);
        }

        $digitalData = $data->digital_data;
        $context = $this->buildDataContext($digitalData);
        if ($context === '') {
            return response()->json(['message' => 'No data content to analyze.'], 422);
        }

        $agent = app(DataInsightAgent::class);
        $response = $agent->prompt(
            "Here is the user's data:\n\n---\n{$context}\n---\n\nUser question or request:\n{$question}"
        );
        $responseData = $this->normalizeStructuredResponse($response);
        $answer = $this->sanitizeAssistantAnswer((string) ($responseData['answer'] ?? ''));

        return response()->json([
            'answer' => $answer,
        ]);
    }

    /**
     * Stream AI response for this data record. Expects JSON: { "question": "..." } or multipart: question + optional attachment.
     * For table data, uses an agentic agent with tools (add/update/delete rows) and streams the final answer.
     */
    public function askStream(Request $request, Data $data)
    {
        if ($data->user_id !== auth()->id()) {
            abort(404);
        }

        $question = $request->input('question');
        if (! is_string($question) || trim($question) === '') {
            return response()->json(['message' => 'Question is required.'], 422);
        }

        $attachmentBlock = '';
        $imageAttachments = [];
        $files = $request->file('attachments');
        if (is_array($files)) {
            foreach ($files as $file) {
                if (! $file->isValid()) {
                    continue;
                }
                $mime = $file->getMimeType();
                $name = $file->getClientOriginalName();
                if (str_starts_with($mime ?? '', 'image/')) {
                    $imageAttachments[] = Image::fromBase64(base64_encode($file->get()), $mime ?? 'image/jpeg');
                } elseif (str_starts_with($mime ?? '', 'text/') || $mime === 'application/json') {
                    $content = file_get_contents($file->getRealPath());
                    $attachmentBlock .= "\n\nUser attached a file ({$name}):\n---\n".trim($content)."\n---";
                } else {
                    $attachmentBlock .= "\n\n[User attached a file: {$name}]";
                }
            }
        }
        if ($attachmentBlock === '' && $request->hasFile('attachment')) {
            $file = $request->file('attachment');
            if ($file->isValid()) {
                $mime = $file->getMimeType();
                $name = $file->getClientOriginalName();
                if (str_starts_with($mime ?? '', 'image/')) {
                    $imageAttachments[] = Image::fromBase64(base64_encode($file->get()), $mime ?? 'image/jpeg');
                } elseif (str_starts_with($mime ?? '', 'text/') || $mime === 'application/json') {
                    $content = file_get_contents($file->getRealPath());
                    $attachmentBlock = "\n\nUser also attached a file ({$name}):\n---\n".trim($content)."\n---";
                } else {
                    $attachmentBlock = "\n\n[User attached a file: {$name}]";
                }
            }
        }

        $digitalData = $data->digital_data;
        $context = $this->buildDataContext($digitalData);
        if ($context === '') {
            return response()->json(['message' => 'No data content to analyze.'], 422);
        }

        $prompt = "The user's message (their question and any attachments) is the primary context—answer based on it first. When they attach image(s) and ask about them (e.g. \"is this the book cover?\", \"what's in this image?\"), answer from the attached image(s). Only use the \"uploaded data\" block below as additional or secondary context when relevant. Return only the final answer for the user. Do not show counting, step-by-step analysis, reasoning, or thought process unless the user explicitly asks for it.\n\nUser question or request: {$question}{$attachmentBlock}\n\n---\nUploaded data for this page (secondary context):\n---\n{$context}";

        $dataType = $digitalData['type'] ?? '';
        $isTable = is_array($digitalData) && $dataType === 'table';
        $isDoc = is_array($digitalData) && $dataType === 'doc';
        $useAgentic = $isTable || $isDoc;

        if ($useAgentic) {
            $agent = app()->makeWith(DataInsightAgenticAgent::class, ['data' => $data]);
            $response = $agent->prompt($prompt, $imageAttachments, $data->ai_provider, $data->ai_model);
            $text = $this->sanitizeAssistantAnswer((string) $response);
            $dataUpdated = true;
            // Only include view_data_url when the agent likely changed data (user may want to see it).
            // Omit by default so we don't show "View data" on every reply.
            $viewDataUrl = null;

            return response()->stream(function () use ($text, $dataUpdated, $viewDataUrl) {
                $chunk = json_encode(array_filter([
                    'content' => $text,
                    'data_updated' => $dataUpdated,
                    'view_data_url' => $viewDataUrl,
                ], fn ($v) => $v !== null));
                echo "data: {$chunk}\n\n";
                echo "data: [DONE]\n\n";
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
            ]);
        }

        $agent = app(DataInsightStreamingAgent::class);

        return $agent->stream($prompt, [], $data->ai_provider, $data->ai_model);
    }

    /**
     * Ask AI to suggest chart type, columns, aggregation, and axis names for this table data. Returns JSON chart config.
     * Optional body: { "request": "e.g. bar chart of sales by region" }.
     */
    public function chartSuggestion(Request $request, Data $data): JsonResponse
    {
        if ($data->user_id !== auth()->id()) {
            abort(404);
        }

        $digitalData = $data->digital_data;
        if (! $digitalData || ($digitalData['type'] ?? '') !== 'table') {
            return response()->json(['message' => 'Table data is required for chart suggestion.'], 422);
        }

        $content = $digitalData['content'] ?? '';
        $decoded = is_array($content) ? $content : (json_decode(is_string($content) ? $content : '{}', true) ?: []);
        if (! is_array($decoded)) {
            return response()->json(['message' => 'Invalid table data.'], 422);
        }

        $headers = $decoded['headers'] ?? [];
        $rows = $decoded['rows'] ?? [];
        if (count($headers) < 2 || count($rows) < 1) {
            return response()->json(['message' => 'Table needs at least 2 columns and 1 row.'], 422);
        }
        $chartRows = $this->normalizeRowsForCharting($headers, $rows);
        $excludedSignatures = $this->excludedChartSignatures($request->input('excludeCharts'));
        $shapeSuggestions = $this->inferChartCandidatesFromData($headers, $chartRows);
        $shapeSuggestion = $this->firstUnseenChartCandidate($shapeSuggestions, $excludedSignatures);

        $context = 'Column headers (0-based index in brackets): '.implode(', ', array_map(fn ($h, $i) => "{$i}: {$h}", $headers, array_keys($headers)));
        $sample = array_slice($chartRows, 0, 15);
        foreach ($sample as $row) {
            $context .= "\n".implode(' | ', array_map(fn ($c) => (string) $c, $row));
        }
        if ($shapeSuggestions !== []) {
            $context .= "\n\nRecommended business/logistics chart candidates based on the data shape:\n".json_encode($shapeSuggestions);
        }

        $userRequest = $request->input('request');
        $userRequest = is_string($userRequest) ? trim($userRequest) : '';
        $provider = $data->ai_provider;
        $promptSuffix = $userRequest !== ''
            ? "User wants this specific chart: \"{$userRequest}\". Suggest chart type, column indices, aggregation, and axis names that best match this request."
            : 'Suggest the best chart type, which column indices to use for labels and values, whether aggregation is needed, and clear axis names for the chart. The result must make business or logistics sense and should avoid identifiers or row numbers.';
        if ($excludedSignatures !== []) {
            $context .= "\n\nAlready shown chart configurations (do not repeat these): ".json_encode(array_values($excludedSignatures));
            $promptSuffix .= ' Return a materially different chart from the previously shown chart configurations.';
        }
        if ($provider === 'nova') {
            $promptSuffix .= ' IMPORTANT FOR NOVA: reply with a single JSON object only, with no prose, and prefer the recommended chart candidates when they match the table shape.';
        }

        $agent = app(ChartSuggestionAgent::class);
        $response = $agent->prompt(
            "Table data:\n---\n{$context}\n---\n{$promptSuffix}",
            provider: $provider,
            model: $data->ai_model,
        );
        $responseData = $this->normalizeStructuredResponse($response);
        if ($userRequest === '' && $shapeSuggestion !== null && (! $this->hasUsableChartSuggestion($responseData) || ! $this->isReasonableChartSuggestion($responseData, $headers, $chartRows) || $this->isExcludedChartSuggestion($responseData, $excludedSignatures))) {
            Log::info('[charts] using shape-based default chart suggestion', [
                'data_id' => $data->id,
                'provider' => $provider,
                'ai_response' => $responseData,
                'shape_suggestion' => $shapeSuggestion,
            ]);
            $responseData = $shapeSuggestion;
        }
        if ($userRequest !== '' && $this->hasUsableChartSuggestion($responseData) && ! $this->isReasonableChartSuggestion($responseData, $headers, $chartRows)) {
            $fallback = $this->inferChartSuggestionFromRequest($headers, $userRequest, $chartRows);
            if ($fallback !== null) {
                $existingTitle = isset($responseData['title']) ? trim((string) $responseData['title']) : '';
                if ($existingTitle !== '') {
                    $fallback['title'] = $existingTitle;
                }
                $responseData = $fallback;
            }
        }
        if (! $this->hasUsableChartSuggestion($responseData)) {
            Log::warning('[charts] AI returned no usable chart suggestion', [
                'data_id' => $data->id,
                'request' => $userRequest !== '' ? $userRequest : null,
                'response' => $responseData,
                'raw_response' => $this->truncateForLog($this->rawStructuredResponse($response)),
            ]);
            $fallback = $this->inferChartSuggestionFromRequest($headers, $userRequest, $chartRows);
            if ($fallback !== null) {
                Log::info('[charts] using heuristic chart fallback', [
                    'data_id' => $data->id,
                    'request' => $userRequest,
                    'fallback' => $fallback,
                ]);
                $responseData = $fallback;
            } elseif ($shapeSuggestion !== null) {
                $responseData = $shapeSuggestion;
            } else {
                return response()->json([
                    'message' => 'AI could not generate a usable chart for that request. Try a simpler prompt or be more specific about the fields you want to compare.',
                ], 422);
            }
        }

        $chartType = $responseData['chartType'] ?? 'bar';
        $chartType = in_array($chartType, ['bar', 'line', 'pie'], true) ? $chartType : 'bar';
        $labelCol = (int) ($responseData['labelColumn'] ?? 0);
        $valueCol = (int) ($responseData['valueColumn'] ?? 1);
        $aggregation = (string) ($responseData['aggregation'] ?? 'none');
        $aggregation = in_array($aggregation, ['none', 'sum', 'count'], true) ? $aggregation : 'none';
        $maxCol = count($headers) - 1;
        $labelCol = max(0, min($labelCol, $maxCol));
        $valueCol = max(0, min($valueCol, $maxCol));
        if ($aggregation !== 'count' && $labelCol === $valueCol) {
            $valueCol = $labelCol === 0 ? 1 : 0;
        }

        $title = isset($responseData['title']) ? trim((string) $responseData['title']) : '';
        $xAxisName = isset($responseData['xAxisName']) ? trim((string) $responseData['xAxisName']) : '';
        $yAxisName = isset($responseData['yAxisName']) ? trim((string) $responseData['yAxisName']) : '';
        if ($xAxisName === '') {
            $xAxisName = (string) ($headers[$labelCol] ?? 'Category');
        }
        if ($yAxisName === '') {
            $yAxisName = (string) ($headers[$valueCol] ?? 'Value');
        }

        return response()->json([
            'chartType' => $chartType,
            'labelColumn' => $labelCol,
            'valueColumn' => $valueCol,
            'aggregation' => $aggregation,
            'xAxisName' => $xAxisName,
            'yAxisName' => $yAxisName,
            'title' => $title === '' ? null : $title,
        ]);
    }

    /**
     * Normalize agent structured output into an associative array.
     *
     * @return array<string, mixed>
     */
    private function normalizeStructuredResponse(mixed $response): array
    {
        if (is_array($response)) {
            return $response;
        }

        if (is_object($response) && isset($response->text) && is_string($response->text)) {
            return $this->decodeStructuredJson($response->text);
        }

        if (is_string($response)) {
            return $this->decodeStructuredJson($response);
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeStructuredJson(string $text): array
    {
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $trimmed = trim($text);
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/is', $trimmed, $matches) === 1) {
            $decoded = json_decode($matches[1], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (preg_match('/(\{.*\})/s', $trimmed, $matches) === 1) {
            $decoded = json_decode($matches[1], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $responseData
     */
    private function hasUsableChartSuggestion(array $responseData): bool
    {
        return isset($responseData['chartType'], $responseData['labelColumn'], $responseData['valueColumn']);
    }

    /**
     * @param  array<string, mixed>  $responseData
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function isReasonableChartSuggestion(array $responseData, array $headers, array $rows): bool
    {
        if (! $this->hasUsableChartSuggestion($responseData)) {
            return false;
        }

        $maxCol = count($headers) - 1;
        $labelCol = (int) $responseData['labelColumn'];
        $valueCol = (int) $responseData['valueColumn'];
        $aggregation = (string) ($responseData['aggregation'] ?? 'none');
        if ($labelCol < 0 || $valueCol < 0 || $labelCol > $maxCol || $valueCol > $maxCol) {
            return false;
        }
        if ($this->isLowSignalHeader((string) ($headers[$labelCol] ?? ''))) {
            return false;
        }
        if ($aggregation !== 'count' && $labelCol === $valueCol) {
            return false;
        }
        if ($aggregation === 'count') {
            return true;
        }

        return $this->numericScoreForColumn($rows, $valueCol) >= 0.4;
    }

    private function rawStructuredResponse(mixed $response): string
    {
        if (is_string($response)) {
            return $response;
        }

        if (is_array($response)) {
            return (string) json_encode($response);
        }

        if (is_object($response) && isset($response->text) && is_string($response->text)) {
            return $response->text;
        }

        return is_object($response) ? get_class($response) : '';
    }

    private function truncateForLog(string $text, int $limit = 3000): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        return strlen($text) > $limit ? substr($text, 0, $limit).'…' : $text;
    }

    /**
     * @param  array<int, string>  $headers
     * @return array<string, mixed>|null
     */
    private function inferChartSuggestionFromRequest(array $headers, string $userRequest, array $rows = []): ?array
    {
        $request = strtolower(trim($userRequest));
        if ($request === '') {
            return null;
        }

        $labelCol = null;
        if (preg_match('/item|items|order|orders|product|products|dish|dishes|meal|meals/', $request) === 1) {
            $labelCol = $this->findHeaderIndexByKeywords($headers, ['order', 'item', 'product', 'dish', 'meal']);
        }
        if ($labelCol === null && preg_match('/customer|customers|client|clients|buyer|buyers/', $request) === 1) {
            $labelCol = $this->findHeaderIndexByKeywords($headers, ['customer', 'client', 'buyer']);
        }
        if ($labelCol === null && preg_match('/date|day|month|time|daily|monthly|weekly|trend/', $request) === 1) {
            $labelCol = $this->findHeaderIndexByKeywords($headers, ['date', 'day', 'month', 'time']);
        }
        if ($labelCol === null) {
            $labelCol = $this->findHeaderIndexByKeywords($headers, ['customer', 'client', 'buyer']);
        }
        if ($labelCol === null) {
            $labelCol = $this->findHeaderIndexByKeywords($headers, ['order', 'item', 'product', 'dish', 'meal']);
        }
        if ($labelCol === null) {
            $labelCol = $this->findHeaderIndexByKeywords($headers, ['date', 'day', 'month', 'time']);
        }
        if ($labelCol === null) {
            $labelCol = $this->bestMatchingHeaderIndex($headers, $request);
        }
        if ($labelCol === null) {
            $labelCol = 0;
        }

        $valueCol = null;
        if (preg_match('/money|spent|spend|sales|revenue|amount|total|price|cost/', $request) === 1) {
            $valueCol = $this->findPreferredMoneyColumnIndex($headers, $rows);
        }
        if ($valueCol === null) {
            $valueCol = $this->findPreferredMoneyColumnIndex($headers, $rows);
        }
        if ($valueCol === null) {
            $valueCol = $labelCol === 0 ? 1 : 0;
        }

        $aggregation = 'none';
        if (preg_match('/count|how many|number of/', $request) === 1) {
            $aggregation = 'count';
        } elseif (preg_match('/total|sum|spent|spend|sales|revenue|amount|per | by /', $request) === 1) {
            $aggregation = 'sum';
        }

        $chartType = 'bar';
        if (preg_match('/pie|share|distribution|composition/', $request) === 1) {
            $chartType = 'pie';
        } elseif (preg_match('/line|trend|over time|daily|monthly/', $request) === 1) {
            $chartType = 'line';
        }

        $xAxisName = (string) ($headers[$labelCol] ?? 'Category');
        $yAxisName = $aggregation === 'count'
            ? 'Count'
            : (preg_match('/money|spent|spend|cost/', $request) === 1
                ? 'Money spent'
                : (string) ($headers[$valueCol] ?? 'Value'));
        $title = ucwords(trim($userRequest));

        return [
            'chartType' => $chartType,
            'labelColumn' => $labelCol,
            'valueColumn' => $valueCol,
            'aggregation' => $aggregation,
            'xAxisName' => $xAxisName,
            'yAxisName' => $yAxisName,
            'title' => $title,
        ];
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function inferChartCandidatesFromData(array $headers, array $rows): array
    {
        $dateCol = $this->findHeaderIndexByKeywords($headers, ['date', 'day', 'month', 'time', 'year']);
        $numericCol = $this->findPreferredMoneyColumnIndex($headers, $rows);
        if ($numericCol === null) {
            $numericCol = $this->bestNumericColumnIndex($headers, $rows);
        }
        $customerCol = $this->findHeaderIndexByKeywords($headers, ['customer', 'client', 'buyer']);
        $platformCol = $this->findHeaderIndexByKeywords($headers, ['platform', 'channel', 'source', 'carrier', 'vendor', 'region']);
        $itemCol = $this->findHeaderIndexByKeywords($headers, ['order', 'item', 'product', 'sku', 'inventory', 'category']);
        $genericCategoryCol = $this->bestCategoryColumnIndex($headers, $rows, [$dateCol, $numericCol, $customerCol, $platformCol, $itemCol]);

        $candidates = [];
        if ($dateCol !== null && $numericCol !== null) {
            $candidates[] = [
                'chartType' => 'line',
                'labelColumn' => $dateCol,
                'valueColumn' => $numericCol,
                'aggregation' => 'sum',
                'xAxisName' => (string) ($headers[$dateCol] ?? 'Date'),
                'yAxisName' => (string) ($headers[$numericCol] ?? 'Value'),
                'title' => sprintf('%s by %s', (string) ($headers[$numericCol] ?? 'Value'), (string) ($headers[$dateCol] ?? 'Date')),
            ];
        }
        foreach ([$customerCol, $platformCol, $itemCol, $genericCategoryCol] as $categoryCol) {
            if ($categoryCol === null || $this->isLowSignalHeader((string) ($headers[$categoryCol] ?? ''))) {
                continue;
            }
            if ($numericCol !== null) {
                $candidates[] = [
                    'chartType' => 'bar',
                    'labelColumn' => $categoryCol,
                    'valueColumn' => $numericCol,
                    'aggregation' => 'sum',
                    'xAxisName' => (string) ($headers[$categoryCol] ?? 'Category'),
                    'yAxisName' => (string) ($headers[$numericCol] ?? 'Value'),
                    'title' => sprintf('%s by %s', (string) ($headers[$numericCol] ?? 'Value'), (string) ($headers[$categoryCol] ?? 'Category')),
                ];
            }
            $candidates[] = [
                'chartType' => 'bar',
                'labelColumn' => $categoryCol,
                'valueColumn' => $categoryCol,
                'aggregation' => 'count',
                'xAxisName' => (string) ($headers[$categoryCol] ?? 'Category'),
                'yAxisName' => 'Count',
                'title' => sprintf('Count by %s', (string) ($headers[$categoryCol] ?? 'Category')),
            ];
        }

        return $this->uniqueChartCandidates($candidates);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function normalizeRowsForCharting(array $headers, array $rows): array
    {
        $fillDownCols = [];
        foreach ($headers as $index => $header) {
            $normalized = strtolower((string) $header);
            if (preg_match('/date|day|month|time|customer|client|buyer|platform|channel|region|sl#|s\/n|serial/', $normalized) === 1) {
                $fillDownCols[] = $index;
            }
        }

        $normalizedRows = [];
        $lastValues = [];
        foreach ($rows as $row) {
            $row = array_values((array) $row);
            if ($this->isLikelyChartSummaryRow($row)) {
                continue;
            }
            foreach ($fillDownCols as $index) {
                $cell = trim((string) ($row[$index] ?? ''));
                if ($cell !== '') {
                    $lastValues[$index] = $cell;

                    continue;
                }
                if (($this->rowHasMeaningfulContent($row, $index)) && array_key_exists($index, $lastValues)) {
                    $row[$index] = $lastValues[$index];
                }
            }
            $normalizedRows[] = $row;
        }

        return $normalizedRows !== [] ? $normalizedRows : $rows;
    }

    /**
     * @param  array<int, string>  $headers
     */
    private function findHeaderIndexByKeywords(array $headers, array $keywords): ?int
    {
        foreach ($keywords as $keyword) {
            foreach ($headers as $index => $header) {
                $normalized = strtolower((string) $header);
                if (str_contains($normalized, strtolower($keyword))) {
                    return $index;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $headers
     */
    private function bestMatchingHeaderIndex(array $headers, string $request): ?int
    {
        $tokens = preg_split('/[^a-z0-9]+/', $request, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $bestScore = 0;
        $bestIndex = null;
        foreach ($headers as $index => $header) {
            $headerTokens = preg_split('/[^a-z0-9]+/', strtolower((string) $header), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $score = count(array_intersect($tokens, $headerTokens));
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestIndex = $index;
            }
        }

        return $bestIndex;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function bestNumericColumnIndex(array $headers, array $rows): ?int
    {
        $bestIndex = null;
        $bestScore = 0.0;
        foreach ($headers as $index => $_header) {
            $score = $this->numericScoreForColumn($rows, $index);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestIndex = $index;
            }
        }

        return $bestScore >= 0.4 ? $bestIndex : null;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function findPreferredMoneyColumnIndex(array $headers, array $rows): ?int
    {
        $candidates = ['total', 'amount', 'revenue', 'sales', 'value', 'price', 'unit price', 'unit', 'cost'];
        $bestIndex = null;
        $bestScore = 0.0;
        foreach ($candidates as $keyword) {
            foreach ($headers as $index => $header) {
                if (! str_contains(strtolower((string) $header), $keyword)) {
                    continue;
                }
                $score = $this->numericScoreForColumn($rows, $index);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestIndex = $index;
                }
            }
            if ($bestIndex !== null && $bestScore >= 0.4) {
                return $bestIndex;
            }
        }

        return $bestIndex;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<int, int|null>  $exclude
     */
    private function bestCategoryColumnIndex(array $headers, array $rows, array $exclude = []): ?int
    {
        $excluded = array_filter($exclude, fn ($index) => is_int($index));
        foreach ($headers as $index => $_header) {
            if (in_array($index, $excluded, true)) {
                continue;
            }
            if ($this->isLowSignalHeader((string) ($headers[$index] ?? ''))) {
                continue;
            }
            if ($this->numericScoreForColumn($rows, $index) < 0.4) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @return array<string, true>
     */
    private function excludedChartSignatures(mixed $excludeCharts): array
    {
        if (! is_array($excludeCharts)) {
            return [];
        }

        $signatures = [];
        foreach ($excludeCharts as $chart) {
            if (is_array($chart) && $this->hasUsableChartSuggestion($chart)) {
                $signatures[$this->chartSuggestionSignature($chart)] = true;
            }
        }

        return $signatures;
    }

    /**
     * @param  array<string, mixed>  $chart
     */
    private function chartSuggestionSignature(array $chart): string
    {
        return implode('|', [
            (string) ($chart['chartType'] ?? 'bar'),
            (string) ($chart['aggregation'] ?? 'none'),
            (string) ($chart['labelColumn'] ?? ''),
            (string) ($chart['valueColumn'] ?? ''),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $candidates
     * @param  array<string, true>  $excludedSignatures
     * @return array<string, mixed>|null
     */
    private function firstUnseenChartCandidate(array $candidates, array $excludedSignatures): ?array
    {
        foreach ($candidates as $candidate) {
            if (! isset($excludedSignatures[$this->chartSuggestionSignature($candidate)])) {
                return $candidate;
            }
        }

        return $candidates[0] ?? null;
    }

    /**
     * @param  array<string, mixed>  $chart
     * @param  array<string, true>  $excludedSignatures
     */
    private function isExcludedChartSuggestion(array $chart, array $excludedSignatures): bool
    {
        return isset($excludedSignatures[$this->chartSuggestionSignature($chart)]);
    }

    private function isLowSignalHeader(string $header): bool
    {
        $normalized = strtolower(trim($header));
        if ($normalized === '') {
            return true;
        }

        return preg_match('/\b(id|identifier|serial|sl#|s\/n|ref|reference|row|index|code|no)\b/', $normalized) === 1;
    }

    /**
     * @param  list<array<string, mixed>>  $candidates
     * @return list<array<string, mixed>>
     */
    private function uniqueChartCandidates(array $candidates): array
    {
        $unique = [];
        foreach ($candidates as $candidate) {
            if (! $this->hasUsableChartSuggestion($candidate)) {
                continue;
            }
            $signature = $this->chartSuggestionSignature($candidate);
            $unique[$signature] = $candidate;
        }

        return array_values($unique);
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function rowHasMeaningfulContent(array $row, int $exceptIndex): bool
    {
        foreach ($row as $index => $value) {
            if ($index === $exceptIndex) {
                continue;
            }
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function isLikelyChartSummaryRow(array $row): bool
    {
        $joined = strtolower(trim(implode(' | ', array_map(fn ($value) => trim((string) $value), $row))));
        if ($joined === '') {
            return true;
        }

        return preg_match('/(?:^|\b)(total|pos|t\.?f|tf|glovo|chowdeck|chowder)\s*[:=]/', $joined) === 1;
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function numericScoreForColumn(array $rows, int $index): float
    {
        $sample = array_slice($rows, 0, 50);
        $numeric = 0;
        foreach ($sample as $row) {
            $value = $row[$index] ?? null;
            if (trim((string) $value) === '') {
                continue;
            }
            if ($this->parseNumericValue($value) !== null) {
                $numeric++;
            }
        }

        return count($sample) > 0 ? $numeric / count($sample) : 0.0;
    }

    private function parseNumericValue(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if (! preg_match('/-?\d[\d,]*(?:\.\d+)?/', $raw, $matches)) {
            return null;
        }

        $normalized = str_replace(',', '', $matches[0]);

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function sanitizeAssistantAnswer(string $text): string
    {
        $clean = trim((string) preg_replace('/<think\b[^>]*>.*?<\/think>/is', '', $text));
        if ($clean === '') {
            return '';
        }

        if (preg_match('/(?:^|\n)\s*(?:final answer|answer)\s*:\s*(.+)$/is', $clean, $matches)) {
            return trim($matches[1]);
        }

        $clean = preg_replace('/(?:^|\n)\s*(?:analysis|reasoning|thought process|thinking)\s*:\s*/i', "\n", $clean);

        return trim($clean ?? $text);
    }

    private function buildDataContext(?array $digitalData): string
    {
        if (! $digitalData || ! isset($digitalData['type'], $digitalData['content'])) {
            return '';
        }
        $type = $digitalData['type'];
        $content = $digitalData['content'];
        if ($type === 'table') {
            $decoded = is_array($content) ? $content : (json_decode(is_string($content) ? $content : '{}', true) ?: []);
            if (! is_array($decoded)) {
                return is_string($content) ? $content : '';
            }
            $headers = $decoded['headers'] ?? [];
            $rows = $decoded['rows'] ?? [];
            $lines = ['Columns: '.implode(', ', $headers)];
            foreach ($rows as $row) {
                $lines[] = implode(' | ', array_map(fn ($c) => (string) $c, $row));
            }

            return implode("\n", $lines);
        }

        return $content;
    }

    /** List saved chats for this data record. */
    public function savedChatsIndex(Data $data): JsonResponse
    {
        if ($data->user_id !== auth()->id()) {
            abort(404);
        }

        $chats = $data->savedChats()
            ->orderByDesc('updated_at')
            ->get(['id', 'name', 'messages', 'created_at', 'updated_at']);

        return response()->json([
            'chats' => $chats->map(fn (SavedDataChat $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'messages' => $c->messages,
                'created_at' => $c->created_at?->toIso8601String(),
                'updated_at' => $c->updated_at?->toIso8601String(),
            ]),
        ]);
    }

    /** Save current chat. POST body: { "name": "optional title", "messages": [...] }. */
    public function savedChatStore(Request $request, Data $data): JsonResponse
    {
        if ($data->user_id !== auth()->id()) {
            abort(404);
        }

        $messages = $request->input('messages');
        if (! is_array($messages) || count($messages) === 0) {
            return response()->json(['message' => 'At least one message is required.'], 422);
        }

        $name = $request->input('name');
        $name = is_string($name) ? trim($name) : null;
        if ($name === '') {
            $name = null;
        }

        $chat = $data->savedChats()->create([
            'user_id' => auth()->id(),
            'name' => $name,
            'messages' => $messages,
        ]);

        return response()->json([
            'id' => $chat->id,
            'name' => $chat->name,
            'messages' => $chat->messages,
            'created_at' => $chat->created_at?->toIso8601String(),
            'updated_at' => $chat->updated_at?->toIso8601String(),
        ], 201);
    }

    /** Update a saved chat. PATCH body: { "name": "optional", "messages": [...] }. */
    public function savedChatUpdate(Request $request, Data $data, SavedDataChat $saved_chat): JsonResponse
    {
        if ($data->user_id !== auth()->id() || $saved_chat->data_id !== $data->id || $saved_chat->user_id !== auth()->id()) {
            abort(404);
        }

        $updates = [];
        $messages = $request->input('messages');
        if (is_array($messages) && count($messages) > 0) {
            $updates['messages'] = $messages;
        }
        $name = $request->input('name');
        if (array_key_exists('name', $request->all())) {
            $updates['name'] = is_string($name) ? trim($name) : null;
            if ($updates['name'] === '') {
                $updates['name'] = null;
            }
        }
        if ($updates !== []) {
            $saved_chat->update($updates);
        }

        return response()->json([
            'id' => $saved_chat->id,
            'name' => $saved_chat->name,
            'messages' => $saved_chat->messages,
            'created_at' => $saved_chat->created_at?->toIso8601String(),
            'updated_at' => $saved_chat->updated_at?->toIso8601String(),
        ]);
    }

    /** Delete a saved chat. */
    public function savedChatDestroy(Data $data, SavedDataChat $saved_chat): JsonResponse
    {
        if ($data->user_id !== auth()->id() || $saved_chat->data_id !== $data->id || $saved_chat->user_id !== auth()->id()) {
            abort(404);
        }

        $saved_chat->delete();

        return response()->json(['deleted' => true]);
    }

    /** List saved charts for this data record. */
    public function savedChartsIndex(Data $data): JsonResponse
    {
        if ($data->user_id !== auth()->id()) {
            abort(404);
        }

        $charts = $data->savedCharts()
            ->orderByDesc('updated_at')
            ->get(['id', 'name', 'chart_config', 'created_at', 'updated_at']);

        return response()->json([
            'charts' => $charts->map(fn (SavedDataChart $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'chart_config' => $c->chart_config,
                'created_at' => $c->created_at?->toIso8601String(),
                'updated_at' => $c->updated_at?->toIso8601String(),
            ]),
        ]);
    }

    /** Save current chart. POST body: { "name": "optional title", "chart_config": { chartType, labelColumn, valueColumn, xAxisName, yAxisName, title } }. */
    public function savedChartStore(Request $request, Data $data): JsonResponse
    {
        if ($data->user_id !== auth()->id()) {
            abort(404);
        }

        $config = $request->input('chart_config');
        if (! is_array($config)) {
            return response()->json(['message' => 'chart_config object is required.'], 422);
        }

        $name = $request->input('name');
        $name = is_string($name) ? trim($name) : null;
        if ($name === '') {
            $name = null;
        }

        $chart = $data->savedCharts()->create([
            'user_id' => auth()->id(),
            'name' => $name,
            'chart_config' => $config,
        ]);

        return response()->json([
            'id' => $chart->id,
            'name' => $chart->name,
            'chart_config' => $chart->chart_config,
            'created_at' => $chart->created_at?->toIso8601String(),
            'updated_at' => $chart->updated_at?->toIso8601String(),
        ], 201);
    }

    /** Delete a saved chart. */
    public function savedChartDestroy(Data $data, SavedDataChart $saved_chart): JsonResponse
    {
        if ($data->user_id !== auth()->id() || $saved_chart->data_id !== $data->id || $saved_chart->user_id !== auth()->id()) {
            abort(404);
        }

        $saved_chart->delete();

        return response()->json(['deleted' => true]);
    }
}
