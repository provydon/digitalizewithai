<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    /** List folders for the current user (flat list, with parent_id for nesting). */
    public function index(): JsonResponse
    {
        $folders = Folder::query()
            ->forUser(auth()->id())
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'created_at'])
            ->map(fn (Folder $f) => [
                'id' => $f->id,
                'parent_id' => $f->parent_id,
                'name' => $f->name,
                'created_at' => $f->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $folders]);
    }

    /** Create a folder. */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:folders,id'],
        ]);

        $parentId = $validated['parent_id'] ?? null;
        if ($parentId !== null) {
            $parent = Folder::where('id', $parentId)->forUser(auth()->id())->first();
            if (! $parent) {
                abort(404, 'Parent folder not found.');
            }
        }

        $folder = Folder::create([
            'user_id' => auth()->id(),
            'parent_id' => $parentId,
            'name' => $validated['name'],
        ]);

        return response()->json([
            'id' => $folder->id,
            'parent_id' => $folder->parent_id,
            'name' => $folder->name,
            'created_at' => $folder->created_at?->toIso8601String(),
        ], 201);
    }

    /** Update a folder. */
    public function update(Request $request, Folder $folder): JsonResponse
    {
        if ($folder->user_id !== auth()->id()) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:folders,id'],
        ]);

        if (isset($validated['parent_id'])) {
            $parentId = $validated['parent_id'];
            if ($parentId === $folder->id) {
                abort(422, 'Folder cannot be its own parent.');
            }
            if ($parentId !== null) {
                $parent = Folder::where('id', $parentId)->forUser(auth()->id())->first();
                if (! $parent) {
                    abort(404, 'Parent folder not found.');
                }
            }
            $folder->parent_id = $parentId;
        }
        if (isset($validated['name'])) {
            $folder->name = $validated['name'];
        }
        $folder->save();

        return response()->json([
            'id' => $folder->id,
            'parent_id' => $folder->parent_id,
            'name' => $folder->name,
            'created_at' => $folder->created_at?->toIso8601String(),
        ]);
    }

    /** Delete a folder. Items in the folder become uncategorized (folder_id = null). */
    public function destroy(Folder $folder): JsonResponse
    {
        if ($folder->user_id !== auth()->id()) {
            abort(404);
        }

        $folder->data()->update(['folder_id' => null]);
        $folder->children()->update(['parent_id' => null]);
        $folder->delete();

        return response()->json(['deleted' => true]);
    }
}
