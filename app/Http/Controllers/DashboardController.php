<?php

namespace App\Http\Controllers;

use App\Models\Data;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard');
    }

    /** List data for dashboard (JSON, same session auth as dashboard). Only current user's data. */
    public function dataIndex(): JsonResponse
    {
        $items = Data::query()
            ->forUser(auth()->id())
            ->latest()
            ->get()
            ->map(fn (Data $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'type' => $d->digital_data['type'] ?? null,
                'created_at' => $d->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $items]);
    }
}
