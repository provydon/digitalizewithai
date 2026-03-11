<?php

namespace App\Jobs;

use App\Events\DataRecordUpdated;
use App\Models\Data;
use App\Services\DigitalizeExtractionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DigitalizeFileJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** Job timeout (seconds); must exceed ai.request_timeout for long extractions. */
    public int $timeout;

    /** Number of times to attempt the job. */
    public int $tries = 2;

    public function __construct(
        public int $dataId
    ) {
        $aiTimeout = (int) config('ai.request_timeout', 600);

        $this->timeout = max(660, $aiTimeout + 60);
    }

    public function handle(DigitalizeExtractionService $extraction): void
    {
        $data = Data::find($this->dataId);
        if (! $data) {
            Log::warning('[digitalize] job: Data not found', ['data_id' => $this->dataId]);

            return;
        }

        $raw = $data->raw_data;
        if (! is_array($raw) || empty($raw['disk']) || empty($raw['path'])) {
            Log::error('[digitalize] job: invalid raw_data', ['data_id' => $data->id]);
            $this->markDataFailed($data, 'Invalid file reference.');

            return;
        }

        $digital = $data->digital_data;
        $isPending = is_array($digital) && ($digital['type'] ?? null) === 'pending' && ($digital['status'] ?? null) === 'processing';
        if (! $isPending) {
            Log::info('[digitalize] job: Data no longer pending, skipping', ['data_id' => $data->id]);

            return;
        }

        Log::info('[digitalize] job: start', ['data_id' => $data->id]);

        if ($data->extraction_started_at === null) {
            $data->update(['extraction_started_at' => now()]);
            $data->refresh();
        }

        try {
            $decoded = Storage::disk($raw['disk'])->get($raw['path']);
            if ($decoded === null || $decoded === '') {
                throw new \RuntimeException('File not found or empty in storage.');
            }

            $mimeType = $raw['mime_type'] ?? 'application/octet-stream';
            $nameFromRequest = $raw['name_from_request'] ?? null;

            $result = $extraction->extract(
                $decoded,
                $mimeType,
                $raw['ai_provider'] ?? null,
                $raw['ai_model'] ?? null,
                $nameFromRequest
            );

            $startedAt = $data->extraction_started_at ?? $data->created_at;
            $durationSeconds = (int) max(0, now()->getTimestamp() - $startedAt->getTimestamp());

            $data->update([
                'name' => pathinfo($result['resolved_name'], PATHINFO_FILENAME),
                'status' => 'ready',
                'digital_data' => $result['digital_data'],
                'ai_provider' => $result['ai_provider'],
                'ai_model' => $result['ai_model'],
                'extraction_duration_seconds' => $durationSeconds,
            ]);

            if (($result['digital_data']['type'] ?? '') === 'table') {
                $data->syncTableRowsFromDigitalData();
            }

            broadcast(new DataRecordUpdated($data->id, 'ready'));
            Log::info('[digitalize] job: complete', ['data_id' => $data->id, 'type' => $result['digital_data']['type'] ?? null]);
        } catch (\Throwable $e) {
            Log::error('[digitalize] job: failed', ['data_id' => $data->id, 'error' => $e->getMessage()]);
            $this->markDataFailed($data, $e->getMessage());
            throw $e;
        }
    }

    private function markDataFailed(Data $data, string $message): void
    {
        $digital = $data->digital_data;
        if (! is_array($digital)) {
            $digital = [];
        }
        $data->update([
            'status' => 'failed',
            'extraction_failure_message' => $message,
            'digital_data' => array_merge($digital, [
                'type' => 'pending',
                'status' => 'failed',
                'error' => $message,
            ]),
        ]);
        broadcast(new DataRecordUpdated($data->id, 'failed'));
    }
}
