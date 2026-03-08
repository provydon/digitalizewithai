<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Web search tool for the data insight agent. Use when the user asks about facts
 * not in the provided data (e.g. current age, biography, recent events).
 * Requires SERPER_API_KEY to be set (see https://serper.dev).
 */
class WebSearchTool implements Tool
{
    private const SERPER_URL = 'https://google.serper.dev/search';

    public function name(): string
    {
        return 'web_search';
    }

    public function description(): Stringable|string
    {
        return 'Search the web and return results. You MUST call this when the user asks about facts not in the data—e.g. a person\'s age, birth date, biography, or recent events. Use a short query like "Name age" or "Name birthday". Then answer the user using these results. Do not tell the user to search Google or Wikipedia; use this tool to get the answer.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Short search query (e.g. "person name age", "company revenue 2024")')
                ->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $apiKey = config('services.serper.api_key');
        if (! $apiKey || $apiKey === '') {
            return 'Web search is not configured (missing SERPER_API_KEY). Answer from the provided data only.';
        }

        $query = trim((string) ($request['query'] ?? ''));
        if ($query === '') {
            return 'Error: search query cannot be empty.';
        }

        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post(self::SERPER_URL, [
                'q' => $query,
                'num' => 8,
            ]);

            if (! $response->successful()) {
                Log::warning('[WebSearchTool] Serper API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return 'Web search failed. Answer from the provided data only.';
            }

            $body = $response->json();
            $organic = $body['organic'] ?? [];
            $knowledgeGraph = $body['knowledgeGraph'] ?? null;

            $parts = [];

            if (is_array($knowledgeGraph) && ! empty($knowledgeGraph)) {
                $title = $knowledgeGraph['title'] ?? null;
                $desc = $knowledgeGraph['description'] ?? null;
                if ($title || $desc) {
                    $parts[] = 'Knowledge panel: '.trim(($title ? $title.'. ' : '').($desc ?? ''));
                }
            }

            foreach (array_slice($organic, 0, 6) as $item) {
                $title = $item['title'] ?? '';
                $snippet = $item['snippet'] ?? '';
                if ($title || $snippet) {
                    $parts[] = '- '.trim($title.($snippet ? ': '.$snippet : ''));
                }
            }

            if ($parts === []) {
                return 'No web results found for that query.';
            }

            return 'Web search results for "'.str_replace('"', "'", $query)."\":\n\n".implode("\n", $parts);
        } catch (\Throwable $e) {
            Log::warning('[WebSearchTool] Exception', ['message' => $e->getMessage()]);

            return 'Web search failed. Answer from the provided data only.';
        }
    }
}
