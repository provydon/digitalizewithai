<?php

namespace App\Ai\Tools;

use App\Models\Data;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdateTableRowTool implements Tool
{
    public function __construct(
        protected Data $data,
    ) {}

    public function name(): string
    {
        return 'update_table_row';
    }

    public function description(): Stringable|string
    {
        return 'Update an existing row by its 0-based row index (first row is 0). Use when the user asks to change, edit, or update a row. Provide row_index and cells as a JSON array of strings in column order.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'row_index' => $schema->string()
                ->description('0-based index of the row to update (0 = first data row)')
                ->required(),
            'cells' => $schema->string()
                ->description('JSON array of cell values as strings, one per column')
                ->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $rowIndex = (int) ($request['row_index'] ?? 0);
        $cells = $request['cells'] ?? null;
        $cells = is_string($cells) ? json_decode($cells, true) : $cells;
        if (! is_array($cells)) {
            return 'Error: cells must be a JSON array of strings.';
        }

        if ($this->data->tableRows()->count() === 0) {
            $this->data->syncTableRowsFromDigitalData();
        }

        $row = $this->data->tableRows()->orderBy('row_index')->skip($rowIndex)->first();
        if (! $row) {
            return "Error: no row at index {$rowIndex}.";
        }

        $content = $this->data->digital_data['content'] ?? '{}';
        $decoded = is_array($content) ? $content : (json_decode(is_string($content) ? $content : '{}', true) ?: []);
        $headers = $decoded['headers'] ?? [];
        $cellCount = count($headers);
        $cells = array_slice(array_values(array_map('strval', $cells)), 0, $cellCount);
        while (count($cells) < $cellCount) {
            $cells[] = '';
        }

        $searchContent = implode(' ', $cells);
        $row->update(['cells' => $cells, 'search_content' => $searchContent]);
        $this->data->rebuildDigitalDataRowsFromTableRows();

        return 'Row updated successfully.';
    }
}
