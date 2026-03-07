<?php

namespace App\Jobs;

use App\Models\Data;
use App\Services\DigitalizeExtractionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Process first frame only: get suggested name and initial content so the UI can show progress immediately.
 */
class DigitalizeFirstFrameJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 120;

    public int $tries = 2;

    public function __construct(
        public int $dataId,
        public string $tempDir,
        public int $totalBatches = 0
    ) {}

    public function handle(DigitalizeExtractionService $extraction): void
    {
        $data = Data::find($this->dataId);
        if (! $data) {
            return;
        }

        $raw = $data->raw_data;
        $digital = $data->digital_data;
        if (! is_array($digital) || ($digital['type'] ?? null) !== 'pending' || ($digital['status'] ?? null) !== 'processing') {
            return;
        }

        $path = $this->tempDir.'/frame_0001.jpg';
        $contents = Storage::disk('local')->get($path);
        if ($contents === null || $contents === '') {
            $this->markFailed($data, 'First frame not found.');

            return;
        }

        Log::info('[digitalize] first-frame job: start', ['data_id' => $data->id]);

        try {
            $framesData = [['base64' => base64_encode($contents), 'mime' => 'image/jpeg']];
            $response = $extraction->extractFromAttachments(
                $framesData,
                $raw['ai_provider'] ?? null,
                $raw['ai_model'] ?? null,
                'First frame of a video. Extract content and suggest a short display name for this document or table.'
            );

            $result = $extraction->buildResultFromResponse(
                $response,
                $raw['name_from_request'] ?? null,
                $raw['ai_provider'] ?? null,
                $raw['ai_model'] ?? null
            );

            $digitalData = $result['digital_data'];
            if ($this->totalBatches > 0) {
                $digitalData['processing_batches_total'] = $this->totalBatches;
                $digitalData['processing_batches_done'] = 0;
                $digitalData['status'] = 'processing';
            }

            $data->update([
                'name' => pathinfo($result['resolved_name'], PATHINFO_FILENAME),
                'digital_data' => $digitalData,
                'ai_provider' => $result['ai_provider'],
                'ai_model' => $result['ai_model'],
            ]);

            if (($digitalData['type'] ?? '') === 'table') {
                $data->syncTableRowsFromDigitalData();
            }

            Log::info('[digitalize] first-frame job: done', ['data_id' => $data->id, 'name' => $data->name]);
        } catch (\Throwable $e) {
            Log::error('[digitalize] first-frame job: failed', ['data_id' => $data->id, 'error' => $e->getMessage()]);
            $this->markFailed($data, $e->getMessage());
            throw $e;
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
