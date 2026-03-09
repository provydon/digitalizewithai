# Handling Large File Uploads

This app accepts images and videos for digitalization (and for appending to tables/documents). Below is how uploads work and how to support larger files.

## Current behavior

- **Single-file and batch digitalize**: Files are sent as multipart/form-data. The backend now **streams** multipart uploads to disk/S3 via `putFileAs()` so the full file is not loaded into PHP memory. Processing (frame extraction, AI) runs in queued jobs.
- **Append-to-table / append-to-doc**: The uploaded file is still read into memory in the request because extraction runs synchronously in the same request. These endpoints remain limited by PHP memory and request timeout for large files.
- **Limits**: Max file size is configurable (default 20 MB). Validation uses `config('upload.max_file_size_mb')`; the frontend uses the same limit from the `digitalize-options` API.

## Recommendations

### 1. **Increase the limit and align server config (quick win)**

- Set `UPLOAD_MAX_FILE_SIZE_MB=100` (or desired value) in `.env`.
- Ensure PHP allows it:
  - **php.ini**: `upload_max_filesize = 100M`, `post_max_size = 100M` (and `max_execution_time` high enough if you have any synchronous upload handling).
- If you use **Nginx**: `client_max_body_size 100m;`
- If you use **Apache**: optional `LimitRequestBody` or leave unset.

The app already uses `putFileAs()` for multipart digitalize/storeBatch, so doubling PHP memory for the request is not required for the upload itself; the job that later reads the file runs in a worker.

### 2. **Direct-to-S3 (presigned URL) for very large files**

For very large files (e.g. 100 MB+), avoid sending the body through your app:

- Frontend requests a presigned PUT/POST URL from your backend (authenticated).
- Frontend uploads the file **directly to S3** (or compatible) using that URL.
- Frontend calls your backend with the final object key (or backend discovers it via webhook/polling). Backend creates the `Data` record and dispatches the same jobs (orchestrator, S3 copy if needed).

Benefits: no PHP memory or timeout for the upload, better resilience and progress on the client. You already have S3 configured (`config/filesystems.disks.s3`); adding a small “request presigned URL” and “confirm and process” flow would complete this.

### 3. **Resumable / chunked uploads (e.g. TUS)**

If you need resume and chunked uploads (e.g. mobile, unstable networks):

- Run a **TUS** server (or similar) that writes chunks to S3 or local disk.
- After the TUS upload completes, your backend is notified (webhook or polling), creates the `Data` record and dispatches the same digitalize/orchestrator jobs.

This is more involved but gives the best UX for very large files and poor networks.

### 4. **Append endpoints (append rows / append doc)**

These still run extraction in the HTTP request and use `$uploaded->get()`. To support larger files there:

- **Option A**: Keep a lower limit (e.g. 20–50 MB) for append and document it.
- **Option B**: Refactor to “store upload → queue job that runs extraction and appends”; then the same streaming/presigned strategies apply.

## Config reference

| What | Where |
|------|--------|
| Max file size (MB) | `config/upload.php` → `max_file_size_mb` (env: `UPLOAD_MAX_FILE_SIZE_MB`, default 20) |
| Frontend limit | Fetched from `GET /dashboard/api/digitalize-options` → `max_file_size_bytes` |
| PHP upload limits | `upload_max_filesize`, `post_max_size` (e.g. in `php.ini` or `docker/php.ini`) |
| Web server body size | Nginx: `client_max_body_size`; Apache: `LimitRequestBody` (optional) |

## Summary

- **Up to ~100 MB**: Set `UPLOAD_MAX_FILE_SIZE_MB`, align PHP and web server limits; multipart digitalize/storeBatch already stream to disk.
- **Larger or more resilient**: Add presigned direct-to-S3 uploads, then process in the same jobs.
- **Resumable**: Add TUS (or similar) and wire completion into your existing job pipeline.
