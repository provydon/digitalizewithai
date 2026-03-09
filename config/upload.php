<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Max file size (MB) for digitalize / append uploads
    |--------------------------------------------------------------------------
    | Single upload (multipart or base64) and batch uploads are limited to this
    | size per file. Ensure PHP and your web server allow at least this size:
    | - PHP: upload_max_filesize, post_max_size (e.g. 100M in php.ini)
    | - Nginx: client_max_body_size
    | - Apache: LimitRequestBody (optional)
    */

    'max_file_size_mb' => (int) env('UPLOAD_MAX_FILE_SIZE_MB', 20),

];
