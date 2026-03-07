<?php

namespace App\Ai\Tools;

use App\Models\Data;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class AddTableRowTool implements Tool
{
    public function __construct(
        protected Data $data,
    ) {}

    public function name(): string
    {
        return 'add_table_row';
    }

    public function description(): Stringable|string
    {
        return 'Add one new row to the table. Use when the user asks to add, insert, or append a row. Provide cells as a JSON array of strings in column order (same order as the table headers).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'cells' => $schema->string()
                ->description('JSON array of cell values as strings, one per column, e.g. ["Value1", "Value2", "3"]')
                ->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $cells = $request['cells'] ?? null;
        $cells = is_string($cells) ? json_decode($cells, true) : $cells;
        if (! is_array($cells)) {
            return 'Error: cells must be a JSON array of strings.';
        }

        if ($this->data->tableRows()->count() === 0) {
            $this->data->syncTableRowsFromDigitalData();
        }

        $content = $this->data->digital_data['content'] ?? '{}';
        $decoded = is_array($content) ? $content : (json_decode(is_string($content) ? $content : '{}', true) ?: []);
        $headers = $decoded['headers'] ?? [];
        $cellCount = count($headers);
        $cells = array_slice(array_values(array_map('strval', $cells)), 0, $cellCount);
        while (count($cells) < $cellCount) {
            $cells[] = '';
        }

        $maxIndex = (int) $this->data->tableRows()->max('row_index');
        $searchContent = implode(' ', $cells);
        $this->data->tableRows()->create([
            'row_index' => $maxIndex + 1,
            'search_content' => $searchContent,
            'cells' => $cells,
        ]);
        $this->data->rebuildDigitalDataRowsFromTableRows();

        return 'Row added successfully.';
    }
}
