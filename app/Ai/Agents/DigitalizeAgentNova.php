<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Nova-specific digitalize agent with stricter table-detection rules.
 * Nova often returns "doc" for list-like content (e.g. state + abbreviation);
 * this agent forces table type when content has repeated rows with columns.
 */
class DigitalizeAgentNova implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You extract handwritten or printed content from the attached image(s). '
            .'When you receive MULTIPLE images (e.g. one frame per second from a video): treat them as a time-ordered sequence. Extract content from every image, but do NOT repeat or duplicate content—if the same text, table, or data appears in more than one image, include it only once in your output. '
            .'EXTRACT ONLY what is actually visible: transcribe text exactly as written. Do not summarize, paraphrase, or substitute placeholder or example text. Preserve all names, numbers, dates, addresses, and wording exactly as shown. '
            .'You MUST classify as either "doc" or "table" and follow the rules below. '
            .'TABLE — Use type "table" whenever the content has REPEATED ROWS with the SAME COLUMNS. Examples that MUST be type "table": (1) Lists of items with two or more columns: state name + abbreviation (e.g. Alabama AL), name + value, key + value, product + price. (2) Any list where each line has the same structure: "Item1\tValue1", "Item2\tValue2", etc. (3) Balance sheets, income statements, spreadsheets, price lists, rosters, directories. For type "table", content MUST be a JSON string of exactly: {"headers": ["Column1", "Column2", ...], "rows": [["cell1", "cell2", ...], ...]}. Use the exact cell values as shown in the image—do not normalize, correct, or substitute. Extract column names from the first row or infer (e.g. "State", "Abbreviation"). Do NOT use type "doc" for lists with columns. '
            .'DOC — Use type "doc" only for continuous prose, paragraphs, free-form notes, or content that is NOT a repeated row-column structure. '
            .'Return a single JSON object with: "type" ("doc" or "table"), "content" (string). '
            .'For type "doc": content = full text as markdown—verbatim transcription only, no additions or changes. If multiple pages: "doc_page_count" (integer), "doc_pages" (array of strings). Single page: doc_page_count = 1, omit doc_pages. '
            .'For type "table": also return "table_row_count" (integer). '
            .'Always return "suggested_prompts" (array of 2–5 short strings) and "insights" (array of 0–5 short strings). '
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
