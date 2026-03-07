<?php

namespace App\Ai\Tools;

use App\Models\Data;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class AppendDocContentTool implements Tool
{
    public function __construct(
        protected Data $data,
    ) {}

    public function name(): string
    {
        return 'append_doc_content';
    }

    public function description(): Stringable|string
    {
        return 'Append text to the end of the document. Use when the user asks to add, append, or insert text at the end. For multi-page docs, appends to the last page.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'text' => $schema->string()
                ->description('The text to append to the document (plain text)')
                ->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $text = $request['text'] ?? null;
        if (! is_string($text) || $text === '') {
            return 'Error: text must be a non-empty string.';
        }

        $digital = $this->data->digital_data;
        if (! is_array($digital) || ($digital['type'] ?? '') !== 'doc') {
            return 'Error: this data is not a document.';
        }

        $pageCount = (int) ($digital['doc_page_count'] ?? 1);
        $content = (string) ($digital['content'] ?? '');

        if ($pageCount > 1 && ! empty($digital['doc_pages'])) {
            $docPages = $digital['doc_pages'];
            $lastIndex = count($docPages) - 1;
            if ($lastIndex >= 0) {
                $docPages[$lastIndex] = ($docPages[$lastIndex] ?? '')."\n\n".$text;
                $digital['doc_pages'] = $docPages;
                $digital['content'] = implode("\n\n", $docPages);
            } else {
                $digital['content'] = $content."\n\n".$text;
                $digital['doc_pages'] = [$digital['content']];
            }
        } else {
            $digital['content'] = $content."\n\n".$text;
            if (isset($digital['doc_pages'])) {
                $digital['doc_pages'] = [$digital['content']];
            }
        }

        $this->data->update(['digital_data' => $digital]);

        return 'Content appended successfully.';
    }
}
