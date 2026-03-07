<?php

namespace App\Http\Controllers;

use App\Models\Data;
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

    /** List data for dashboard (JSON). Paginated and searchable by name. Only current user's data. */
    public function dataIndex(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(5, min(50, $perPage));
        $search = $request->input('search');
        $search = is_string($search) ? trim($search) : '';

        $query = Data::query()
            ->forUser(auth()->id())
            ->latest();

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
                'type' => $dd['type'] ?? null,
                'status' => $status,
                'processing' => $processing,
                'processing_batches_done' => $processing ? (int) ($dd['processing_batches_done'] ?? 0) : null,
                'processing_batches_total' => $processing ? (int) ($dd['processing_batches_total'] ?? 0) : null,
                'ai_provider' => $d->ai_provider,
                'ai_model' => $d->ai_model,
                'created_at' => $d->created_at?->toIso8601String(),
            ];
        })->all();

        return response()->json([
            'data' => $items,
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
