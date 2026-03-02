<?php

namespace App\Http\Controllers;

use App\Models\Data;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class DataViewController extends Controller
{
    public function show(Data $data): Response|JsonResponse
    {
        if ($data->user_id !== auth()->id()) {
            abort(404);
        }

        return Inertia::render('Data/Show', [
            'id' => $data->id,
        ]);
    }

    /** Single data record (JSON, same session auth as dashboard). Only if owned by current user. */
    public function dataShow(Data $data): JsonResponse
    {
        if ($data->user_id !== auth()->id()) {
            abort(404);
        }

        return response()->json([
            'id' => $data->id,
            'name' => $data->name,
            'raw_data' => $data->raw_data,
            'digital_data' => $data->digital_data,
            'created_at' => $data->created_at?->toIso8601String(),
            'updated_at' => $data->updated_at?->toIso8601String(),
        ]);
    }
}
