<?php

namespace App\Jobs;

use App\Models\Data;
use App\Services\VideoFrameExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * For video: extract frames, save to temp, dispatch first-frame job (quick name + initial data) then batch jobs.
 * For image: dispatch single DigitalizeFileJob.
 */
class DigitalizeOrchestratorJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300;

    public int $tries = 2;

    public function __construct(
        public int $dataId
    ) {}

    public function handle(): void
    {
        $data = Data::find($this->dataId);
        if (! $data) {
            Log::warning('[digitalize] orchestrator: Data not found', ['data_id' => $this->dataId]);

            return;
        }

        $raw = $data->raw_data;
        if (! is_array($raw) || empty($raw['disk']) || empty($raw['path'])) {
            Log::error('[digitalize] orchestrator: invalid raw_data', ['data_id' => $data->id]);
            $this->markFailed($data, 'Invalid file reference.');

            return;
        }

        $digital = $data->digital_data;
        if (! is_array($digital) || ($digital['type'] ?? null) !== 'pending' || ($digital['status'] ?? null) !== 'processing') {
            Log::info('[digitalize] orchestrator: Data not pending, skipping', ['data_id' => $data->id]);

            return;
        }

        $decoded = Storage::disk($raw['disk'])->get($raw['path']);
        if ($decoded === null || $decoded === '') {
            $this->markFailed($data, 'File not found or empty.');

            return;
        }

        $mimeType = $raw['mime_type'] ?? 'application/octet-stream';
        $isImage = in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);

        if ($isImage) {
            Log::info('[digitalize] orchestrator: single image, dispatching full extraction job', ['data_id' => $data->id]);
            DigitalizeFileJob::dispatch($data->id);

            return;
        }

        Log::info('[digitalize] orchestrator: video, extracting frames', ['data_id' => $data->id]);
        $extractor = new VideoFrameExtractor;
        $frames = $extractor->extractFramesPerSecond($decoded, $mimeType);

        if ($frames === []) {
            Log::warning('[digitalize] orchestrator: no frames, dispatching full extraction (video as document)', ['data_id' => $data->id]);
            DigitalizeFileJob::dispatch($data->id);

            return;
        }

        $tempDir = "temp/digitalize/{$data->id}";
        $disk = Storage::disk('local');
        foreach ($frames as $i => $frame) {
            $path = $tempDir.'/frame_'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT).'.jpg';
            $disk->put($path, base64_decode($frame['base64'], true));
        }

        $totalFrames = count($frames);
        $batchSize = max(1, (int) config('video_extract.batch_size', 20));
        $batchCount = (int) ceil(($totalFrames - 1) / $batchSize);

        Log::info('[digitalize] orchestrator: frames saved, dispatching first-frame + batch jobs', [
            'data_id' => $data->id,
            'total_frames' => $totalFrames,
            'batch_count' => $batchCount,
        ]);

        DigitalizeFirstFrameJob::dispatch($data->id, $tempDir, $batchCount);

        for ($b = 0; $b < $batchCount; $b++) {
            $start = 1 + $b * $batchSize;
            $end = min($start + $batchSize, $totalFrames);
            DigitalizeBatchJob::dispatch($data->id, $tempDir, $b + 1, $batchCount, $start, $end);
        }
    }

    private function markFailed(Data $data, string $message): void
    {
        $digital = $data->digital_data;
        if (! is_array($digital)) {
            $digital = [];
        }
        $data->update([
            'status' => 'failed',
            'digital_data' => array_merge($digital, [
                'type' => 'pending',
                'status' => 'failed',
                'error' => $message,
            ]),
        ]);
    }
}
