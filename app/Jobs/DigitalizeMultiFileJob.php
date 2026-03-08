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

/**
 * Process multiple uploaded files as a single Data record (one extraction, one doc/table).
 */
class DigitalizeMultiFileJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 600;

    public int $tries = 2;

    public function __construct(
        public int $dataId
    ) {}

    public function handle(DigitalizeExtractionService $extraction): void
    {
        $data = Data::find($this->dataId);
        if (! $data) {
            Log::warning('[digitalize] multi-file job: Data not found', ['data_id' => $this->dataId]);

            return;
        }

        $raw = $data->raw_data;
        $files = $raw['files'] ?? null;
        if (! is_array($files) || count($files) < 2) {
            Log::error('[digitalize] multi-file job: invalid raw_data.files', ['data_id' => $data->id]);

            return;
        }

        $digital = $data->digital_data;
        $isPending = is_array($digital) && ($digital['type'] ?? null) === 'pending' && ($digital['status'] ?? null) === 'processing';
        if (! $isPending) {
            Log::info('[digitalize] multi-file job: Data not pending, skipping', ['data_id' => $data->id]);

            return;
        }

        Log::info('[digitalize] multi-file job: start', ['data_id' => $data->id, 'file_count' => count($files)]);

        if ($data->extraction_started_at === null) {
            $data->update(['extraction_started_at' => now()]);
            $data->refresh();
        }

        $filePayloads = [];
        foreach ($files as $entry) {
            $disk = $entry['disk'] ?? $raw['disk'] ?? config('filesystems.default');
            $path = $entry['path'] ?? null;
            if ($path === null || $path === '') {
                $this->markDataFailed($data, 'Invalid file reference in batch.');
                return;
            }
            $decoded = Storage::disk($disk)->get($path);
            if ($decoded === null || $decoded === '') {
                $this->markDataFailed($data, 'File not found or empty: '.$path);
                return;
            }
            $filePayloads[] = [
                'decoded' => $decoded,
                'mime_type' => $entry['mime_type'] ?? 'application/octet-stream',
                'name_from_request' => $entry['name_from_request'] ?? null,
            ];
        }

        try {
            $result = $extraction->extractFromMultipleFiles(
                $filePayloads,
                $raw['ai_provider'] ?? null,
                $raw['ai_model'] ?? null
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
            Log::info('[digitalize] multi-file job: complete', ['data_id' => $data->id, 'type' => $result['digital_data']['type'] ?? null]);
        } catch (\Throwable $e) {
            Log::error('[digitalize] multi-file job: failed', ['data_id' => $data->id, 'error' => $e->getMessage()]);
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
