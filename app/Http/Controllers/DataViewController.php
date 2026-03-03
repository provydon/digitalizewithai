<?php

namespace App\Http\Controllers;

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

        return response()->json([
            'id' => $data->id,
            'name' => $data->name,
            'raw_data' => $data->raw_data,
            'digital_data' => $data->digital_data,
            'created_at' => $data->created_at?->toIso8601String(),
            'updated_at' => $data->updated_at?->toIso8601String(),
        ]);
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
