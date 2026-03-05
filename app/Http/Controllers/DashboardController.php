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

        $items = collect($paginator->items())->map(fn (Data $d) => [
            'id' => $d->id,
            'name' => $d->name,
            'type' => $d->digital_data['type'] ?? null,
            'ai_provider' => $d->ai_provider,
            'ai_model' => $d->ai_model,
            'created_at' => $d->created_at?->toIso8601String(),
        ])->all();

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
