<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Extract frames from a video using ffmpeg so we send images to the AI, not video.
 * FPS and max frames are configurable (config/video_extract.php).
 * ffmpeg/ffprobe are discovered: use FFMPEG_PATH/FFPROBE_PATH if set, else search common paths
 * (Docker/apt: /usr/bin, Homebrew: /opt/homebrew/bin, /usr/local/bin), else rely on PATH.
 */
class VideoFrameExtractor
{
    /** JPEG quality for extracted frames (2 = high quality, scale 2–31). */
    private const JPEG_QUALITY = 2;

    /** Common directories to search for ffmpeg/ffprobe (Docker, Homebrew, etc.). */
    private const BINARY_SEARCH_DIRS = [
        '/usr/bin',           // Linux, Docker (apt install ffmpeg)
        '/opt/homebrew/bin',  // macOS Apple Silicon Homebrew
        '/usr/local/bin',     // macOS Intel Homebrew, Linux local
    ];

    /**
     * Extract frames from video: up to (fps × duration), capped at max_frames, evenly spaced.
     *
     * @param  string  $videoBinary  Raw video file contents
     * @param  string  $mimeType  e.g. video/mp4, video/webm
     * @return array<int, array{base64: string, mime: string}> Frame data for Image::fromBase64($base64, $mime)
     *
     * @throws \RuntimeException If ffmpeg is missing or extraction fails
     */
    public function extractFramesPerSecond(string $videoBinary, string $mimeType): array
    {
        $ext = $this->mimeToExt($mimeType);
        $dir = storage_path('app/temp/'.Str::random(16));

        Log::info('[digitalize] VideoFrameExtractor: start', [
            'mime' => $mimeType,
            'size_bytes' => strlen($videoBinary),
        ]);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        Log::info('[digitalize] VideoFrameExtractor: temp dir created', ['dir' => $dir]);

        $inputPath = $dir.'/input.'.$ext;
        $outputPattern = $dir.'/frame_%04d.jpg';

        try {
            if (file_put_contents($inputPath, $videoBinary) === false) {
                throw new \RuntimeException('Failed to write temporary video file.');
            }

            $ffmpegBin = $this->ffmpegBinary();
            $ffprobeBin = $this->ffprobeBinary();
            Log::info('[digitalize] VideoFrameExtractor: binaries resolved', [
                'ffmpeg' => $ffmpegBin,
                'ffprobe' => $ffprobeBin,
            ]);

            $this->assertFfmpegAvailable();
            $duration = $this->getDuration($inputPath);
            $fps = (float) config('video_extract.fps', 1);
            $maxFrames = (int) config('video_extract.max_frames', 60);
            $targetCount = (int) min(max(1, ceil($duration * $fps)), $maxFrames);
            $effectiveFps = $duration > 0 ? $targetCount / $duration : 1;

            Log::info('[digitalize] VideoFrameExtractor: duration and target', [
                'duration_sec' => round($duration, 2),
                'config_fps' => $fps,
                'max_frames' => $maxFrames,
                'target_frame_count' => $targetCount,
                'effective_fps' => round($effectiveFps, 4),
            ]);

            $this->runFfmpeg($inputPath, $outputPattern, $effectiveFps);

            $frames = [];
            $files = glob($dir.'/frame_*.jpg') ?: [];
            sort($files);
            foreach ($files as $path) {
                $data = file_get_contents($path);
                if ($data !== false && $data !== '') {
                    $frames[] = [
                        'base64' => base64_encode($data),
                        'mime' => 'image/jpeg',
                    ];
                }
            }

            Log::info('[digitalize] VideoFrameExtractor: done', ['frames_extracted' => count($frames)]);

            return $frames;
        } finally {
            $this->removeDirectory($dir);
        }
    }

    private function ffmpegBinary(): string
    {
        return $this->resolveBinary(
            'video_extract.ffmpeg_path',
            'ffmpeg'
        );
    }

    private function ffprobeBinary(): string
    {
        return $this->resolveBinary(
            'video_extract.ffprobe_path',
            'ffprobe'
        );
    }

    /**
     * Resolve binary path: config if set and usable, else first found in common dirs, else bare name (use PATH).
     */
    private function resolveBinary(string $configKey, string $defaultName): string
    {
        $configured = config($configKey);
        if (is_string($configured) && $configured !== '' && $this->isExecutable($configured)) {
            return $configured;
        }

        foreach (self::BINARY_SEARCH_DIRS as $dir) {
            $path = $dir.'/'.$defaultName;
            if ($this->isExecutable($path)) {
                return $path;
            }
        }

        return $defaultName;
    }

    private function isExecutable(string $path): bool
    {
        return is_file($path) && is_executable($path);
    }

    private function getDuration(string $inputPath): float
    {
        $probe = escapeshellarg($this->ffprobeBinary());
        $path = escapeshellarg($inputPath);
        $cmd = "{$probe} -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 {$path} 2>&1";
        $out = [];
        exec($cmd, $out, $code);
        if ($code === 0 && isset($out[0]) && is_numeric(trim($out[0]))) {
            return (float) trim($out[0]);
        }

        return 60.0;
    }

    private function assertFfmpegAvailable(): void
    {
        $bin = escapeshellarg($this->ffmpegBinary());
        $out = [];
        $code = null;
        exec("{$bin} -version 2>&1", $out, $code);
        if ($code !== 0) {
            $hint = 'Install ffmpeg (apt install ffmpeg in Docker, brew install ffmpeg on macOS). If the web server cannot find it, set FFMPEG_PATH (and FFPROBE_PATH) in .env to the full binary path.';
            throw new \RuntimeException('ffmpeg is required to process video but was not found or failed. '.$hint);
        }
    }

    private function runFfmpeg(string $inputPath, string $outputPattern, float $fps): void
    {
        $bin = escapeshellarg($this->ffmpegBinary());
        $inputPathEscaped = escapeshellarg($inputPath);
        $outputPatternEscaped = escapeshellarg($outputPattern);
        $q = (string) self::JPEG_QUALITY;
        $cmd = "{$bin} -y -i {$inputPathEscaped} -vf fps=".((string) $fps)." -q:v {$q} {$outputPatternEscaped} 2>&1";
        $output = [];
        exec($cmd, $output, $code);
        if ($code !== 0) {
            $msg = implode("\n", $output);
            throw new \RuntimeException('ffmpeg frame extraction failed: '.$msg);
        }
    }

    private function mimeToExt(string $mime): string
    {
        return match ($mime) {
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/webm' => 'webm',
            default => 'mp4',
        };
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
