<?php

namespace App\Ai\Tools;

use App\Models\Data;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ReplaceDocContentTool implements Tool
{
    public function __construct(
        protected Data $data,
    ) {}

    public function name(): string
    {
        return 'replace_doc_content';
    }

    public function description(): Stringable|string
    {
        return 'Replace the entire document content (or one page of a multi-page document) with new text. Use when the user asks to replace, rewrite, or set the document content. For multi-page docs, use page (1-based) to replace a single page; omit page to replace the whole document.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'content' => $schema->string()
                ->description('The new full document or page content (plain text)')
                ->required(),
            'page' => $schema->string()
                ->description('Optional: 1-based page number for multi-page docs; omit or leave empty for single-page or to replace entire doc'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $content = $request['content'] ?? null;
        if (! is_string($content)) {
            return 'Error: content must be a string.';
        }

        $digital = $this->data->digital_data;
        if (! is_array($digital) || ($digital['type'] ?? '') !== 'doc') {
            return 'Error: this data is not a document.';
        }

        $pageCount = (int) ($digital['doc_page_count'] ?? 1);
        $pageRaw = $request['page'] ?? null;
        $pageNum = ($pageRaw !== null && $pageRaw !== '') ? max(1, min($pageCount, (int) $pageRaw)) : null;

        if ($pageNum !== null && $pageCount > 1) {
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
            $digital['content'] = $content;
            if (isset($digital['doc_pages'])) {
                $digital['doc_pages'] = [$content];
            }
        }

        $this->data->update(['digital_data' => $digital]);
        return 'Document content replaced successfully.';
    }
}
