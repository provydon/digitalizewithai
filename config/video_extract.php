<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ffmpeg / ffprobe binary path
    |--------------------------------------------------------------------------
    | When PHP runs under the web server (e.g. Herd, Valet, Apache), PATH often
    | does not include Homebrew. Set the full path to ffmpeg (and ffprobe) so
    | video frame extraction works. Example: /opt/homebrew/bin/ffmpeg
    | Leave null to use "ffmpeg" / "ffprobe" from PATH (e.g. when using CLI).
    */

    'ffmpeg_path' => env('FFMPEG_PATH'),

    'ffprobe_path' => env('FFPROBE_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Frames per second
    |--------------------------------------------------------------------------
    | Extract this many frames per second of video (e.g. 1 = one frame per second).
    | Lower = fewer images and lower AI cost; higher = more coverage for fast-changing content.
    */

    'fps' => (float) env('VIDEO_EXTRACT_FPS', 1),

    /*
    |--------------------------------------------------------------------------
    | Maximum frames per video
    |--------------------------------------------------------------------------
    | Cap total frames sent to the AI. Long videos are sampled down so we never
    | exceed this (e.g. 60 = at most 60 images per upload). Protects cost and limits.
    */

    'max_frames' => (int) env('VIDEO_EXTRACT_MAX_FRAMES', 60),

    /*
    |--------------------------------------------------------------------------
    | Batch size for video frames
    |--------------------------------------------------------------------------
    | When a video yields many frames, we send them to the AI in batches of this
    | size (e.g. 20 images per request) to avoid provider limits. Results are
    | merged and deduplicated. Set to 0 to send all frames in one request.
    */

    'batch_size' => (int) env('VIDEO_EXTRACT_BATCH_SIZE', 20),

];
