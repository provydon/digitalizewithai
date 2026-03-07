<?php

namespace App\Ai\Tools;

use App\Models\Data;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class DeleteTableRowTool implements Tool
{
    public function __construct(
        protected Data $data,
    ) {}

    public function name(): string
    {
        return 'delete_table_row';
    }

    public function description(): Stringable|string
    {
        return 'Delete one row by its 0-based row index (first row is 0). Use when the user asks to remove or delete a row.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'row_index' => $schema->string()
                ->description('0-based index of the row to delete (0 = first data row)')
                ->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $rowIndex = (int) ($request['row_index'] ?? 0);

        if ($this->data->tableRows()->count() === 0) {
            $this->data->syncTableRowsFromDigitalData();
        }

        $row = $this->data->tableRows()->orderBy('row_index')->skip($rowIndex)->first();
        if (! $row) {
            return "Error: no row at index {$rowIndex}.";
        }

        $row->delete();
        $this->data->rebuildDigitalDataRowsFromTableRows();

        return 'Row deleted successfully.';
    }
}
