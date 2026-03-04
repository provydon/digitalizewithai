<?php

namespace App\Http\Controllers;

use App\Ai\Agents\ChartSuggestionAgent;
use App\Ai\Agents\DataInsightAgent;
use App\Ai\Agents\DataInsightStreamingAgent;
use App\Models\Data;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DataViewController extends Controller
{
    public function show(Data $data): Response|JsonResponse
    {
        if ($data->user_id !== auth()->id()) {
            abort(404);
        }

        return Inertia::render('Data/Show', [
            'id' => $data->id,
        ]);
    }

    /** Single data record (JSON, same session auth as dashboard). Only if owned by current user. */
    public function dataShow(Data $data): JsonResponse
    {
        if ($data->user_id !== auth()->id()) {
            abort(404);
        }

        $digitalData = $data->digital_data;
        if (is_array($digitalData) && ($digitalData['type'] ?? '') === 'doc') {
            $pageCount = (int) ($digitalData['doc_page_count'] ?? 1);
            if ($pageCount > 1) {
                $digitalData = array_diff_key($digitalData, array_flip(['content', 'doc_pages']));
            }
        }

        return response()->json([
            'id' => $data->id,
            'name' => $data->name,
            'raw_data' => $data->raw_data,
            'digital_data' => $digitalData,
            'created_at' => $data->created_at?->toIso8601String(),
            'updated_at' => $data->updated_at?->toIso8601String(),
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

    /** Full doc content (e.g. for export). Only for type doc. */
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
        if ($content === '' && ! empty($digital['doc_pages'])) {
            $content = implode("\n\n", $digital['doc_pages']);
        }
        return response()->json(['content' => $content]);
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

        $agent = new DataInsightAgent;
        $response = $agent->prompt(
            "Here is the user's data:\n\n---\n{$context}\n---\n\nUser question or request:\n{$question}"
        );

        return response()->json([
            'answer' => $response['answer'] ?? '',
        ]);
    }

    /**
     * Stream AI response for this data record. Expects JSON: { "question": "..." }.
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

        $digitalData = $data->digital_data;
        $context = $this->buildDataContext($digitalData);
        if ($context === '') {
            return response()->json(['message' => 'No data content to analyze.'], 422);
        }

        $prompt = "Here is the user's data:\n\n---\n{$context}\n---\n\nUser question or request:\n{$question}";
        $agent = new DataInsightStreamingAgent;

        return $agent->stream($prompt);
    }

    /**
     * Ask AI to suggest chart type and columns for this table data. Returns JSON chart config.
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

        $decoded = json_decode($digitalData['content'] ?? '', true);
        if (! is_array($decoded)) {
            return response()->json(['message' => 'Invalid table data.'], 422);
        }

        $headers = $decoded['headers'] ?? [];
        $rows = $decoded['rows'] ?? [];
        if (count($headers) < 2 || count($rows) < 1) {
            return response()->json(['message' => 'Table needs at least 2 columns and 1 row.'], 422);
        }

        $context = 'Column headers (0-based index in brackets): '.implode(', ', array_map(fn ($h, $i) => "{$i}: {$h}", $headers, array_keys($headers)));
        $sample = array_slice($rows, 0, 15);
        foreach ($sample as $row) {
            $context .= "\n".implode(' | ', array_map(fn ($c) => (string) $c, $row));
        }

        $userRequest = $request->input('request');
        $userRequest = is_string($userRequest) ? trim($userRequest) : '';
        $promptSuffix = $userRequest !== ''
            ? "User wants this specific chart: \"{$userRequest}\". Suggest chart type and column indices that best match this request."
            : 'Suggest the best chart type and which column indices to use for labels and values.';

        $agent = new ChartSuggestionAgent;
        $response = $agent->prompt("Table data:\n---\n{$context}\n---\n{$promptSuffix}");

        $chartType = $response['chartType'] ?? 'bar';
        $chartType = in_array($chartType, ['bar', 'line', 'pie'], true) ? $chartType : 'bar';
        $labelCol = (int) ($response['labelColumn'] ?? 0);
        $valueCol = (int) ($response['valueColumn'] ?? 1);
        $maxCol = count($headers) - 1;
        $labelCol = max(0, min($labelCol, $maxCol));
        $valueCol = max(0, min($valueCol, $maxCol));
        if ($labelCol === $valueCol) {
            $valueCol = $labelCol === 0 ? 1 : 0;
        }

        $title = isset($response['title']) ? trim((string) $response['title']) : '';

        return response()->json([
            'chartType' => $chartType,
            'labelColumn' => $labelCol,
            'valueColumn' => $valueCol,
            'title' => $title === '' ? null : $title,
        ]);
    }

    private function buildDataContext(?array $digitalData): string
    {
        if (! $digitalData || ! isset($digitalData['type'], $digitalData['content'])) {
            return '';
        }
        $type = $digitalData['type'];
        $content = $digitalData['content'];
        if ($type === 'table') {
            $decoded = json_decode($content, true);
            if (! is_array($decoded)) {
                return $content;
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
}
