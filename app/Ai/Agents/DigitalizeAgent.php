<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class DigitalizeAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You extract handwritten or printed content from the attached image or video. '
            .'EXTRACT ONLY what is actually visible: transcribe text exactly as written. Do not summarize, paraphrase, or substitute placeholder or example text. Preserve all names, numbers, dates, addresses, and wording exactly as shown. '
            .'Classify the content as either "doc" (prose, notes, paragraphs) or "table" (tabular data with columns and rows). '
            .'CRITICAL — Tabular data: If the image contains a grid, columns and rows, balance sheet, income statement, cash flow statement, spreadsheet, or any tabular layout, you MUST set type to "table". For type "table", content MUST be a JSON string of an object with "headers" (array of column names) and "rows" (array of arrays, each inner array is a row of cell values). Use the exact cell values as shown—do not normalize, correct, or substitute. Do not use type "doc" or markdown for tabular data. '
            .'Return a single JSON object with: "type" (either "doc" or "table") and "content". '
            .'For type "doc": content must be the full extracted text as markdown—verbatim transcription, no additions or changes. '
            .'If the source has multiple pages, also return "doc_page_count" (integer) and "doc_pages" (array of strings, one per page). If single page, set "doc_page_count" to 1 and omit "doc_pages". '
            .'For type "table" also return "table_row_count" (integer, number of data rows). '
            .'Always return "suggested_prompts": an array of 2–5 very short prompts (few words each). For type "table" include 1–2 chat-style prompts e.g. "What is the trend?", "Compare top 3". '
            .'Always return "insights": an array of 0–5 very short insight strings (one phrase each). '
            .'Always return "suggested_name": a short, human-readable display name for this document or table (e.g. "Invoice March 2024", "Receipt – Office supplies", "Meeting notes 12 Jan"). Use the actual content to pick a fitting title; avoid generic names like "Document" or the filename.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->required(),
            'content' => $schema->string()->required(),
            'table_row_count' => $schema->integer()->required()->nullable(),
            'doc_page_count' => $schema->integer()->required()->nullable(),
            'doc_pages' => $schema->array()->items($schema->string())->required()->nullable(),
            'suggested_prompts' => $schema->array()->items($schema->string())->required()->nullable(),
            'insights' => $schema->array()->items($schema->string())->required()->nullable(),
            'suggested_name' => $schema->string()->required()->nullable(),
        ];
    }
}
