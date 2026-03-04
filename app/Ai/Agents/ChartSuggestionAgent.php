<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class ChartSuggestionAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You are a data visualization expert. The user will provide a table with column headers and optionally a few sample rows. '
            .'Choose the best chart type and which columns to use. '
            .'Rules: Use "bar" for categorical comparison (e.g. categories vs counts or amounts). Use "line" for time series or ordered sequences. Use "pie" only when showing composition of a whole (few categories, one value column). '
            .'Return JSON with: chartType (exactly one of: bar, line, pie), labelColumn (0-based index of the column for labels/categories), valueColumn (0-based index of the column for values to plot), and title (short string describing the chart; use empty string if none).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'chartType' => $schema->string()->required(),
            'labelColumn' => $schema->integer()->min(0)->required(),
            'valueColumn' => $schema->integer()->min(0)->required(),
            'title' => $schema->string()->required(),
        ];
    }
}
