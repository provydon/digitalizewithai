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
            . 'Classify the content as either "doc" (prose, notes, paragraphs) or "table" (tabular data with columns and rows). '
            . 'Return a single JSON object with: "type" (either "doc" or "table") and "content". '
            . 'For type "doc", content must be the extracted text as markdown. '
            . 'For type "table", content must be a JSON string of an object with "headers" (array of column names) and "rows" (array of arrays, each inner array is a row of cell values).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->required(),
            'content' => $schema->string()->required(),
        ];
    }
}
