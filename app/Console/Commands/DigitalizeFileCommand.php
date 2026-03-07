<?php

namespace App\Console\Commands;

use App\Ai\Agents\DigitalizeAgent;
use App\Models\Data;
use App\Services\VideoFrameExtractor;
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
        $attachments = $this->attachmentsForDigitalize($isImage, $content, $base64, $mime);
        $prompt = count($attachments) > 1
            ? 'Extract all content from these images (one frame per second from a video). Return structured JSON with type (doc or table) and content as described in your instructions. Do not repeat or duplicate content that appears in more than one image.'
            : 'Extract all content from this image or video (e.g. handwritten or printed text, tables). Return structured JSON with type (doc or table) and content as described in your instructions.';

        $this->info('Sending to AI...');
        $agent = new DigitalizeAgent;
        $response = $agent->prompt($prompt, attachments: $attachments);

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

    /**
     * @return array<int, \Laravel\Ai\Files\Image|\Laravel\Ai\Files\Document>
     */
    private function attachmentsForDigitalize(bool $isImage, string $decoded, string $base64, string $mimeType): array
    {
        if ($isImage) {
            return [Image::fromBase64($base64, $mimeType)];
        }

        $extractor = new VideoFrameExtractor;
        $frames = $extractor->extractFramesPerSecond($decoded, $mimeType);
        if ($frames === []) {
            return [Document::fromBase64($base64, $mimeType)];
        }

        return array_map(
            fn (array $f) => Image::fromBase64($f['base64'], $f['mime']),
            $frames
        );
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
