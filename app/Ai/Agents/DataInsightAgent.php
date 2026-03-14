<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class DataInsightAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You are a helpful data analyst. The user will provide a block of data (either tabular with headers and rows, or document text) and a question or request. '
            .'Answer based only on the provided data. Be concise and accurate. '
            .'Return only the final answer for the user. Do not reveal chain-of-thought, hidden reasoning, or internal analysis. Do not include "thinking", "thought process", "reasoning", or similar sections in the output. '
            .'If the user\'s question contains a term that does not exactly match the data (e.g. misspelled column or value), assume they meant the closest match from the data and answer accordingly; you may briefly note the correction then give the answer. '
            .'If the user asks for insights, summarize key patterns, totals, or notable points. '
            .'If the user asks for a chart suggestion, suggest chart type and which columns to use, in a short sentence. '
            .'Return a single JSON object with one key: "answer" (string), containing your full response in markdown when appropriate.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'answer' => $schema->string()->required(),
        ];
    }
}
