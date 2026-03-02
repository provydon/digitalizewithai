<?php

namespace App\Console\Commands;

use App\Ai\Agents\DigitalizeAgent;
use App\Models\Data;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Files\Document;
use Laravel\Ai\Files\Image;

class DigitalizeFileCommand extends Command
{
    protected $signature = 'digitalize {file : Path to the image or video file}';

    protected $description = 'Digitalize a local file (image or video): extract content via AI and store in the data table.';

    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    private const VIDEO_MIMES = ['video/mp4', 'video/quicktime', 'video/webm'];

    public function handle(): int
    {
        $path = $this->argument('file');

        if (! is_readable($path) || ! is_file($path)) {
            $this->error("File not found or not readable: {$path}");
            return self::FAILURE;
        }

        $mime = $this->getMimeType($path);
        $allowed = array_merge(self::IMAGE_MIMES, self::VIDEO_MIMES);
        if (! in_array($mime, $allowed, true)) {
            $this->error('Allowed mime types: '.implode(', ', $allowed));
            return self::FAILURE;
        }

        $content = file_get_contents($path);
        $base64 = base64_encode($content);

        $disk = config('filesystems.default');
        $ext = $this->mimeToExt($mime);
        $storagePath = 'digitalize/'.Str::uuid().'.'.$ext;
        Storage::disk($disk)->put($storagePath, $content);

        $rawData = ['disk' => $disk, 'path' => $storagePath];

        $isImage = in_array($mime, self::IMAGE_MIMES, true);
        $attachment = $isImage
            ? Image::fromBase64($base64, $mime)
            : Document::fromBase64($base64, $mime);

        $this->info('Sending to AI...');
        $agent = new DigitalizeAgent;
        $response = $agent->prompt(
            'Extract all content from this image or video (e.g. handwritten or printed text, tables). Return structured JSON with type (doc or table) and content as described in your instructions.',
            attachments: [$attachment],
        );

        $digitalData = [
            'type' => $response['type'],
            'content' => $response['content'],
        ];

        $name = pathinfo($path, PATHINFO_FILENAME);
        $data = Data::create([
            'name' => $name,
            'raw_data' => $rawData,
            'digital_data' => $digitalData,
        ]);

        $this->info("Saved. Id: {$data->id}, Name: {$data->name}, Type: {$data->digital_data['type']}");
        $this->line('Content preview: '.Str::limit($data->digital_data['content'], 200));

        return self::SUCCESS;
    }

    private function getMimeType(string $path): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);
        return $mime ?: 'application/octet-stream';
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
