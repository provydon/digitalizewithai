<?php

namespace App\Http\Controllers\Api;

use App\Ai\Agents\DigitalizeAgent;
use App\Http\Controllers\Controller;
use App\Models\Data;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Ai\Files\Document;
use Laravel\Ai\Files\Image;

class DigitalizeController extends Controller
{
    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    private const VIDEO_MIMES = ['video/mp4', 'video/quicktime', 'video/webm'];

    /**
     * Accept file (image or video) via multipart/form-data or base64 JSON, store to S3/local, send to AI, save structured result to Data.
     *
     * Multipart: file (file), name (optional).
     * JSON body: { "file": "base64string" or "data:image/png;base64,...", "name": "optional", "mime_type": "optional if in data URL" }
     */
    public function store(Request $request): JsonResponse
    {
        $allowedMimes = array_merge(self::IMAGE_MIMES, self::VIDEO_MIMES);
        $mimeRule = 'in:'.implode(',', $allowedMimes);

        if ($request->hasFile('file')) {
            $request->validate([
                'file' => ['required', 'file', 'mimetypes:'.implode(',', $allowedMimes), 'max:20480'],
                'name' => 'nullable|string|max:255',
            ]);
            $uploaded = $request->file('file');
            $mimeType = $uploaded->getMimeType();
            if (! in_array($mimeType, $allowedMimes, true)) {
                return response()->json(['message' => 'Allowed mime types: '.implode(', ', $allowedMimes)], 422);
            }
            $decoded = $uploaded->get();
            $base64 = base64_encode($decoded);
            $nameFromRequest = $request->input('name') ?: pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME);
        } else {
            $request->validate([
                'file' => 'required|string',
                'name' => 'nullable|string|max:255',
                'mime_type' => 'nullable|string|'.$mimeRule,
            ]);

            $fileInput = $request->input('file');
            $mimeType = $request->input('mime_type');
            $base64 = $fileInput;

            if (str_starts_with($fileInput, 'data:')) {
                if (! preg_match('/^data:([^;]+);base64,(.+)$/', $fileInput, $m)) {
                    return response()->json(['message' => 'Invalid data URL for file.'], 422);
                }
                $mimeType = $m[1];
                $base64 = $m[2];
            }

            if (! $mimeType) {
                return response()->json(['message' => 'mime_type is required when file is not a data URL.'], 422);
            }

            if (! in_array($mimeType, $allowedMimes, true)) {
                return response()->json(['message' => 'Allowed mime types: '.implode(', ', $allowedMimes)], 422);
            }

            $decoded = base64_decode($base64, true);
            if ($decoded === false) {
                return response()->json(['message' => 'Invalid base64 in file.'], 422);
            }
            $nameFromRequest = $request->input('name');
        }

        $disk = config('filesystems.default');
        $ext = $this->mimeToExt($mimeType);
        $path = 'digitalize/'.Str::uuid().'.'.$ext;
        \Illuminate\Support\Facades\Storage::disk($disk)->put($path, $decoded);

        $rawData = [
            'disk' => $disk,
            'path' => $path,
        ];

        $isImage = in_array($mimeType, self::IMAGE_MIMES, true);
        $attachment = $isImage
            ? Image::fromBase64($base64, $mimeType)
            : Document::fromBase64($base64, $mimeType);

        $agent = new DigitalizeAgent;
        $response = $agent->prompt(
            'Extract all content from this image or video (e.g. handwritten or printed text, tables). Return structured JSON with type (doc or table) and content as described in your instructions.',
            attachments: [$attachment],
        );

        $type = $response['type'] ?? 'doc';
        $content = $response['content'] ?? '';
        $digitalData = [
            'type' => $type,
            'content' => $content,
        ];
        if ($type === 'table' && isset($response['table_row_count'])) {
            $digitalData['table_row_count'] = (int) $response['table_row_count'];
        }
        if ($type === 'doc') {
            $docPages = $response['doc_pages'] ?? null;
            $docPageCount = isset($response['doc_page_count']) ? (int) $response['doc_page_count'] : 1;
            $digitalData['doc_page_count'] = $docPageCount;
            if (is_array($docPages) && $docPageCount > 1) {
                $digitalData['doc_pages'] = array_values($docPages);
                $digitalData['content'] = implode("\n\n", $digitalData['doc_pages']);
            }
        }

        $name = $nameFromRequest ?: pathinfo($path, PATHINFO_FILENAME);
        $data = Data::create([
            'user_id' => $request->user()->id,
            'name' => pathinfo($name, PATHINFO_FILENAME),
            'raw_data' => $rawData,
            'digital_data' => $digitalData,
        ]);

        if (($digitalData['type'] ?? '') === 'table') {
            $data->syncTableRowsFromDigitalData();
        }

        return response()->json([
            'id' => $data->id,
            'name' => $data->name,
            'digital_data' => $data->digital_data,
        ], 201);
    }

    /**
     * Append rows to an existing table from an uploaded photo or video.
     * Same allowed types as store(). Extracted data must be type "table"; rows are appended to match existing headers.
     */
    public function appendToTable(Request $request, Data $data): JsonResponse
    {
        if ($data->user_id !== $request->user()->id) {
            abort(404);
        }
        $digital = $data->digital_data;
        if (! is_array($digital) || ($digital['type'] ?? null) !== 'table') {
            return response()->json(['message' => 'This record is not a table. Appending is only supported for tables.'], 422);
        }

        $allowedMimes = array_merge(self::IMAGE_MIMES, self::VIDEO_MIMES);
        $request->validate([
            'file' => ['required', 'file', 'mimetypes:'.implode(',', $allowedMimes), 'max:20480'],
        ]);
        $uploaded = $request->file('file');
        $mimeType = $uploaded->getMimeType();
        if (! in_array($mimeType, $allowedMimes, true)) {
            return response()->json(['message' => 'Allowed: images (JPEG, PNG, GIF, WebP) or video (MP4, WebM).'], 422);
        }

        $base64 = base64_encode($uploaded->get());
        $isImage = in_array($mimeType, self::IMAGE_MIMES, true);
        $attachment = $isImage
            ? Image::fromBase64($base64, $mimeType)
            : Document::fromBase64($base64, $mimeType);

        $agent = new DigitalizeAgent;
        $response = $agent->prompt(
            'Extract all content from this image or video (e.g. handwritten or printed text, tables). Return structured JSON with type (doc or table) and content as described in your instructions.',
            attachments: [$attachment],
        );

        $type = $response['type'] ?? 'doc';
        if ($type !== 'table') {
            return response()->json(['message' => 'The upload did not contain table data. Use a photo or video of a table to add rows.'], 422);
        }

        $content = $response['content'] ?? '';
        $decoded = is_string($content) ? json_decode($content, true) : $content;
        if (! is_array($decoded)) {
            return response()->json(['message' => 'Could not parse extracted table data.'], 422);
        }
        $newRows = $decoded['rows'] ?? [];
        if (! is_array($newRows)) {
            $newRows = [];
        }

        $existingDecoded = json_decode($digital['content'] ?? '{}', true) ?: [];
        $existingHeaders = $existingDecoded['headers'] ?? [];
        $headerCount = count($existingHeaders);
        if ($headerCount === 0) {
            return response()->json(['message' => 'This table has no columns. Add columns first or add rows manually.'], 422);
        }

        if ($data->tableRows()->count() === 0) {
            $data->syncTableRowsFromDigitalData();
        }
        $maxIndex = (int) $data->tableRows()->max('row_index');

        $added = 0;
        foreach ($newRows as $row) {
            $cells = is_array($row) ? array_values($row) : [];
            $cells = array_slice($cells, 0, $headerCount);
            while (count($cells) < $headerCount) {
                $cells[] = '';
            }
            $searchContent = implode(' ', array_map(fn ($v) => (string) $v, $cells));
            $data->tableRows()->create([
                'row_index' => ++$maxIndex,
                'search_content' => $searchContent,
                'cells' => $cells,
            ]);
            $added++;
        }

        $data->rebuildDigitalDataRowsFromTableRows();

        return response()->json([
            'added' => $added,
            'message' => $added === 1 ? '1 row added.' : "{$added} rows added.",
        ], 201);
    }

    /**
     * List all Data records for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $items = Data::query()
            ->forUser($request->user()->id)
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

    /**
     * Get a single Data record by id (must belong to the authenticated user).
     */
    public function show(Request $request, Data $data): JsonResponse
    {
        if ($data->user_id !== $request->user()->id) {
            abort(404);
        }

        return response()->json([
            'id' => $data->id,
            'name' => $data->name,
            'raw_data' => $data->raw_data,
            'digital_data' => $data->digital_data,
            'created_at' => $data->created_at,
            'updated_at' => $data->updated_at,
        ]);
    }

    private function mimeToExt(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/webm' => 'webm',
            default => 'bin',
        };
    }
}
