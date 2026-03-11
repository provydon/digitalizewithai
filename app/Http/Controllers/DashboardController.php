<?php

namespace App\Http\Controllers;

use App\Models\Data;
use App\Models\Folder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard');
    }

    /** Full data list page (search + pagination). */
    public function dataPage(): Response
    {
        return Inertia::render('Data/Index');
    }

    /** List data for dashboard (JSON). Paginated, filterable by folder_id, searchable by name. Only current user's data. */
    public function dataIndex(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(5, min(50, $perPage));
        $search = $request->input('search');
        $search = is_string($search) ? trim($search) : '';
        $folderIdParam = $request->input('folder_id');
        // Omit or "all" = show all; "uncategorized" or empty = only items with no folder; number = that folder
        $filterFolderId = null;
        if ($folderIdParam !== null && $folderIdParam !== '') {
            if ($folderIdParam === 'uncategorized' || $folderIdParam === 'null') {
                $filterFolderId = 'uncategorized';
            } elseif (is_numeric($folderIdParam)) {
                $filterFolderId = (int) $folderIdParam;
            }
        }

        $query = Data::query()
            ->forUser(auth()->id())
            ->latest();

        if ($filterFolderId === 'uncategorized') {
            $query->whereNull('folder_id');
        } elseif ($filterFolderId !== null) {
            $query->where('folder_id', $filterFolderId);
        }

        if ($search !== '') {
            if ($query->getConnection()->getDriverName() === 'pgsql') {
                $query->pgSearch($search, ['name']);
            } else {
                $query->where('name', 'like', '%'.addcslashes($search, '%_\\').'%');
            }
        }

        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(function (Data $d) {
            $dd = $d->digital_data;
            $processing = is_array($dd) && ($dd['status'] ?? null) === 'processing';
            $failed = is_array($dd) && ($dd['status'] ?? null) === 'failed';
            $status = $d->status ?? ($failed ? 'failed' : ($processing ? 'processing' : 'ready'));

            return [
                'id' => $d->id,
                'name' => $d->name,
                'folder_id' => $d->folder_id,
                'type' => $dd['type'] ?? null,
                'status' => $status,
                'processing' => $processing,
                'processing_batches_done' => $processing ? (int) ($dd['processing_batches_done'] ?? 0) : null,
                'processing_batches_total' => $processing ? (int) ($dd['processing_batches_total'] ?? 0) : null,
                'ai_provider' => $d->ai_provider,
                'ai_model' => $d->ai_model,
                'extraction_duration_seconds' => $d->extraction_duration_seconds,
                'extraction_started_at' => $d->extraction_started_at?->toIso8601String(),
                'created_at' => $d->created_at?->toIso8601String(),
            ];
        })->all();

        $folders = Folder::query()
            ->forUser(auth()->id())
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name'])
            ->map(fn (Folder $f) => ['id' => $f->id, 'parent_id' => $f->parent_id, 'name' => $f->name])
            ->values()
            ->all();

        return response()->json([
            'data' => $items,
            'folders' => $folders,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /** Delete a data record. Must belong to current user. */
    public function destroyData(Data $data): JsonResponse
    {
        if ($data->user_id !== auth()->id()) {
            abort(404);
        }
        $data->delete();

        return response()->json(['deleted' => true]);
    }
}
