<?php

namespace App\Jobs;

use App\Models\Data;
use App\Services\DigitalizeExtractionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Process one batch of video frames and merge result into the same Data record.
 */
class DigitalizeBatchJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300;

    public int $tries = 2;

    public function __construct(
        public int $dataId,
        public string $tempDir,
        public int $batchIndex,
        public int $totalBatches,
        public int $frameStart,
        public int $frameEnd
    ) {}

    public function handle(DigitalizeExtractionService $extraction): void
    {
        $data = Data::find($this->dataId);
        if (! $data) {
            return;
        }

        $raw = $data->raw_data;
        $digital = $data->digital_data;
        $type = $digital['type'] ?? null;
        if ($type === 'pending' && ($digital['status'] ?? null) === 'failed') {
            return;
        }
        if ($type !== 'table' && $type !== 'doc') {
            return;
        }

        $framesData = [];
        for ($i = $this->frameStart; $i <= $this->frameEnd; $i++) {
            $path = $this->tempDir.'/frame_'.str_pad((string) $i, 4, '0', STR_PAD_LEFT).'.jpg';
            $contents = Storage::disk('local')->get($path);
            if ($contents !== null && $contents !== '') {
                $framesData[] = ['base64' => base64_encode($contents), 'mime' => 'image/jpeg'];
            }
        }

        if ($framesData === []) {
            Log::warning('[digitalize] batch job: no frames loaded', ['data_id' => $data->id, 'batch' => $this->batchIndex]);
            $this->incrementDoneAndMaybeFinish($data);

            return;
        }

        Log::info('[digitalize] batch job: start', [
            'data_id' => $data->id,
            'batch' => $this->batchIndex.'/'.$this->totalBatches,
            'frame_range' => $this->frameStart.'-'.$this->frameEnd,
        ]);

        try {
            $response = $extraction->extractFromAttachments(
                $framesData,
                $raw['ai_provider'] ?? null,
                $raw['ai_model'] ?? null,
                'Frames '.$this->frameStart.'–'.$this->frameEnd.' of a video. Extract content. Do not repeat content that appears in more than one image.'
            );

            DB::transaction(function () use ($extraction, $response) {
                $data = Data::where('id', $this->dataId)->lockForUpdate()->first();
                if (! $data) {
                    return;
                }
                $currentDigital = $data->digital_data;
                if (! is_array($currentDigital)) {
                    $currentDigital = [];
                }
                $type = $currentDigital['type'] ?? null;
                if ($type === 'pending' && ($currentDigital['status'] ?? null) === 'failed') {
                    return;
                }
                if ($type !== 'table' && $type !== 'doc') {
                    return;
                }
                $merged = $extraction->mergeBatchResponseIntoDigitalData($currentDigital, $response);
                $merged['processing_batches_total'] = $this->totalBatches;
                $merged['processing_batches_done'] = (int) ($currentDigital['processing_batches_done'] ?? 0) + 1;
                $merged['status'] = 'processing';
                $data->update(['digital_data' => $merged]);
                if (($merged['type'] ?? '') === 'table') {
                    $data->syncTableRowsFromDigitalData();
                }
            });

            $data = Data::find($this->dataId);
            Log::info('[digitalize] batch job: merged', [
                'data_id' => $this->dataId,
                'batch' => $this->batchIndex.'/'.$this->totalBatches,
                'done' => $data?->digital_data['processing_batches_done'] ?? 0,
            ]);

            $this->incrementDoneAndMaybeFinish($data);
        } catch (\Throwable $e) {
            Log::error('[digitalize] batch job: failed', ['data_id' => $this->dataId, 'batch' => $this->batchIndex, 'error' => $e->getMessage()]);
            $data = Data::find($this->dataId);
            if ($data) {
                $digital = $data->digital_data;
                if (! is_array($digital)) {
                    $digital = [];
                }
                $data->update([
                    'status' => 'failed',
                    'digital_data' => array_merge($digital, [
                        'type' => 'pending',
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                    ]),
                ]);
            }
            throw $e;
        }
    }

    private function incrementDoneAndMaybeFinish(?Data $data): void
    {
        if (! $data) {
            $data = Data::find($this->dataId);
        }
        if (! $data) {
            return;
        }
        $data->refresh();
        $digital = $data->digital_data;
        if (! is_array($digital)) {
            return;
        }

        $done = (int) ($digital['processing_batches_done'] ?? 0);
        $total = (int) ($digital['processing_batches_total'] ?? 0);

        if ($done < $total) {
            return;
        }

        unset($digital['processing_batches_done'], $digital['processing_batches_total'], $digital['status']);
        $data->update(['status' => 'ready', 'digital_data' => $digital]);

        $this->deleteTempDir();

        if (($digital['type'] ?? '') === 'table') {
            $data->syncTableRowsFromDigitalData();
        }

        Log::info('[digitalize] batch job: all batches complete', ['data_id' => $data->id]);
    }

    private function deleteTempDir(): void
    {
        try {
            if (! Storage::disk('local')->exists($this->tempDir)) {
                return;
            }
            $files = Storage::disk('local')->allFiles($this->tempDir);
            foreach ($files as $file) {
                Storage::disk('local')->delete($file);
            }
            Storage::disk('local')->deleteDirectory($this->tempDir);
        } catch (\Throwable $e) {
            Log::warning('[digitalize] batch job: cleanup temp dir failed', ['dir' => $this->tempDir, 'error' => $e->getMessage()]);
        }
    }
}
