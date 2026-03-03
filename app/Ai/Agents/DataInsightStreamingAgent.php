<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Same as DataInsightAgent but without structured output so streaming works.
 * Laravel AI does not support streaming when HasStructuredOutput is used.
 */
class DataInsightStreamingAgent implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You are a helpful data analyst. The user will provide a block of data (either tabular with headers and rows, or document text) and a question or request. '
            . 'Answer based only on the provided data. Be concise and accurate. '
            . 'If the user asks for insights, summarize key patterns, totals, or notable points. '
            . 'If the user asks for a chart suggestion, suggest chart type and which columns to use, in a short sentence. '
            . 'Respond in markdown when appropriate (lists, bold, code, etc.).';
    }
}
