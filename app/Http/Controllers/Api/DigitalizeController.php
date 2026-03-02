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
     * Accept base64 file (image or video), store to S3/local, send to AI, save structured result to Data.
     *
     * JSON body: { "file": "base64string" or "data:image/png;base64,...", "name": "optional", "mime_type": "optional if in data URL" }
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|string',
            'name' => 'nullable|string|max:255',
            'mime_type' => 'nullable|string|in:'.implode(',', array_merge(self::IMAGE_MIMES, self::VIDEO_MIMES)),
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

        $allowed = array_merge(self::IMAGE_MIMES, self::VIDEO_MIMES);
        if (! in_array($mimeType, $allowed, true)) {
            return response()->json(['message' => 'Allowed mime types: '.implode(', ', $allowed)], 422);
        }

        $decoded = base64_decode($base64, true);
        if ($decoded === false) {
            return response()->json(['message' => 'Invalid base64 in file.'], 422);
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

        $digitalData = [
            'type' => $response['type'],
            'content' => $response['content'],
        ];

        $name = $request->input('name') ?: pathinfo($path, PATHINFO_FILENAME);
        $data = Data::create([
            'name' => pathinfo($name, PATHINFO_FILENAME),
            'raw_data' => $rawData,
            'digital_data' => $digitalData,
        ]);

        return response()->json([
            'id' => $data->id,
            'name' => $data->name,
            'digital_data' => $data->digital_data,
        ], 201);
    }

    /**
     * Get a single Data record by id (the id returned from the upload).
     */
    public function show(Data $data): JsonResponse
    {
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
