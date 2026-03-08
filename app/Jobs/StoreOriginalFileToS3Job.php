<?php

namespace App\Jobs;

use App\Models\Data;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreOriginalFileToS3Job implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 120;

    public int $tries = 2;

    public function __construct(
        public int $dataId
    ) {}

    public function handle(): void
    {
        $data = Data::find($this->dataId);
        if (! $data) {
            Log::warning('[StoreOriginalFileToS3] Data not found', ['data_id' => $this->dataId]);

            return;
        }

        $raw = $data->raw_data;
        if (! is_array($raw)) {
            Log::info('[StoreOriginalFileToS3] No raw_data', ['data_id' => $data->id]);

            return;
        }

        $bucket = config('filesystems.disks.s3.bucket');
        if (! $bucket || $bucket === '') {
            Log::info('[StoreOriginalFileToS3] S3 not configured, skipping', ['data_id' => $data->id]);

            return;
        }

        $files = $raw['files'] ?? null;
        if (is_array($files) && $files !== []) {
            $updated = false;
            foreach ($files as $i => $entry) {
                if (! empty($entry['s3_key'])) {
                    continue;
                }
                $sourceDisk = $entry['disk'] ?? $raw['disk'] ?? config('filesystems.default');
                $sourcePath = $entry['path'] ?? null;
                if ($sourcePath === null || $sourcePath === '') {
                    continue;
                }
                try {
                    $content = Storage::disk($sourceDisk)->get($sourcePath);
                } catch (\Throwable $e) {
                    Log::warning('[StoreOriginalFileToS3] Could not read source file', [
                        'data_id' => $data->id,
                        'index' => $i,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
                if ($content === null || $content === '') {
                    continue;
                }
                $ext = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'bin';
                $s3Key = sprintf(
                    'originals/%s/%s/%s.%s',
                    (string) $data->user_id,
                    (string) $data->id,
                    Str::uuid()->toString(),
                    $ext
                );
                try {
                    Storage::disk('s3')->put($s3Key, $content, 'private');
                } catch (\Throwable $e) {
                    Log::error('[StoreOriginalFileToS3] S3 put failed', [
                        'data_id' => $data->id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
                $raw['files'][$i]['s3_key'] = $s3Key;
                $updated = true;
            }
            if ($updated) {
                $data->update(['raw_data' => $raw]);
                Log::info('[StoreOriginalFileToS3] Stored multi-file', ['data_id' => $data->id]);
            }

            return;
        }

        if (empty($raw['path'])) {
            Log::info('[StoreOriginalFileToS3] No source path', ['data_id' => $data->id]);

            return;
        }

        if (! empty($raw['s3_key'])) {
            Log::info('[StoreOriginalFileToS3] Already stored in S3', ['data_id' => $data->id]);

            return;
        }

        $sourceDisk = $raw['disk'] ?? config('filesystems.default');
        $sourcePath = $raw['path'];

        try {
            $content = Storage::disk($sourceDisk)->get($sourcePath);
        } catch (\Throwable $e) {
            Log::warning('[StoreOriginalFileToS3] Could not read source file', [
                'data_id' => $data->id,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if ($content === null || $content === '') {
            Log::warning('[StoreOriginalFileToS3] Source file empty', ['data_id' => $data->id]);

            return;
        }

        $ext = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'bin';
        $s3Key = sprintf(
            'originals/%s/%s/%s.%s',
            (string) $data->user_id,
            (string) $data->id,
            Str::uuid()->toString(),
            $ext
        );

        try {
            Storage::disk('s3')->put($s3Key, $content, 'private');
        } catch (\Throwable $e) {
            Log::error('[StoreOriginalFileToS3] S3 put failed', [
                'data_id' => $data->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        $raw['s3_key'] = $s3Key;
        $data->update(['raw_data' => $raw]);

        Log::info('[StoreOriginalFileToS3] Stored', ['data_id' => $data->id, 's3_key' => $s3Key]);
    }
}
