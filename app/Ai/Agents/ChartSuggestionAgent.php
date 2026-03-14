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
        return 'You are a data visualization expert. The user will provide a table with column headers and optionally a few sample rows, and may ask for a specific chart (e.g. "pie chart of market share", "line chart over time"). '
            .'When the user requests a specific chart type (bar, line, or pie), use that type if it fits the data; otherwise choose the best type. '
            .'Rules: Use "bar" for categorical comparison (e.g. categories vs counts or amounts). Use "line" for time series or ordered sequences. Use "pie" for composition of a whole (e.g. market share, distribution; few categories, one value column). '
            .'When the user does not give a custom chart request, choose the most meaningful default chart based on the data shape. Prefer line charts for date/time plus numeric totals, bar charts for categories plus numeric values, and count-based bar charts when the table is mostly categorical. '
            .'The chart must make business or logistics sense. Avoid low-signal charts such as IDs, serial numbers, row numbers, references, codes, or one-off identifiers unless the user explicitly asks for them. Prefer charts about totals, revenue, counts, items, customers, dates, channels, platforms, regions, inventory, or operations. '
            .'If the user asks for a derived chart such as totals, revenue by category, money spent per customer, total money spent per item, counts per group, etc...set aggregation accordingly instead of expecting the raw table to already be summarized. '
            .'Prefer total/amount/revenue/sales/price columns when the request is about money or totals. '
            .'Return JSON with: chartType (exactly one of: bar, line, pie), labelColumn (0-based index of the column for labels/categories), valueColumn (0-based index of the numeric column to plot or aggregate), aggregation (exactly one of: none, sum, count), xAxisName (short human-readable name for the x-axis; use empty string if pie or if none), yAxisName (short human-readable name for the y-axis; use empty string if pie or if none), and title (short string describing the chart; use empty string if none).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'chartType' => $schema->string()->required(),
            'labelColumn' => $schema->integer()->min(0)->required(),
            'valueColumn' => $schema->integer()->min(0)->required(),
            'aggregation' => $schema->string()->required(),
            'xAxisName' => $schema->string()->required(),
            'yAxisName' => $schema->string()->required(),
            'title' => $schema->string()->required(),
        ];
    }
}
