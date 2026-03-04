<?php

namespace App\Http\Controllers;

use App\Models\Data;
use App\Models\DataTableRow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DataTableRowsController extends Controller
{
    private function authorizeTableData(Data $data): void
    {
        if ($data->user_id !== auth()->id()) {
            abort(404);
        }
        $digital = $data->digital_data;
        if (! is_array($digital) || ($digital['type'] ?? null) !== 'table') {
            abort(404);
        }
    }

    /**
     * Paginated table rows with optional search. Lazy-syncs from digital_data if no rows exist.
     */
    public function index(Request $request, Data $data): JsonResponse
    {
        $this->authorizeTableData($data);

        if ($data->tableRows()->count() === 0) {
            $data->syncTableRowsFromDigitalData();
        }

        $decoded = json_decode($data->digital_data['content'] ?? '{}', true) ?: [];
        $headers = $decoded['headers'] ?? [];

        $perPage = (int) $request->input('per_page', 50);
        $perPage = max(5, min(100, $perPage));
        $search = $request->input('search');
        $search = is_string($search) ? trim($search) : '';

        $query = $data->tableRows()->orderBy('row_index');
        if ($search !== '') {
            $query->search($search);
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'headers' => $headers,
            'rows' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Update one row (cells). Rebuilds digital_data after.
     */
    public function update(Request $request, Data $data, DataTableRow $data_table_row): JsonResponse
    {
        $this->authorizeTableData($data);
        if ($data_table_row->data_id !== $data->id) {
            abort(404);
        }

        $cells = $request->input('cells');
        if (! is_array($cells)) {
            return response()->json(['message' => 'cells array is required.'], 422);
        }

        $searchContent = implode(' ', array_map(fn ($v) => (string) $v, $cells));
        $data_table_row->update(['cells' => $cells, 'search_content' => $searchContent]);
        $data->rebuildDigitalDataRowsFromTableRows();

        return response()->json(['row' => $data_table_row->fresh()]);
    }

    /**
     * Delete one row and rebuild digital_data.
     */
    public function destroy(Data $data, DataTableRow $data_table_row): JsonResponse
    {
        $this->authorizeTableData($data);
        if ($data_table_row->data_id !== $data->id) {
            abort(404);
        }

        $data_table_row->delete();
        $data->rebuildDigitalDataRowsFromTableRows();

        return response()->json(['deleted' => true]);
    }
}
