# Digitalize with AI – Implementation Plan

## Goal

Turn handwritten notes (from image or video) into structured data that can be:
- Read as a **doc** (text/markdown), or
- Imported into a **table** and used for charts.

Data types (`doc` / `table`) are **not** enforced at the database level; they are only in the AI-returned JSON.

---

## High-Level Flow

1. User **digitalizes**: sends an **image or video** as **base64** in the request body.
2. Backend **API** receives the base64 payload.
3. File is decoded and stored on **S3** (or configured disk).
4. Stored file is sent to an **LLM** (Laravel AI SDK) with a prompt to extract structured content.
5. LLM returns **structured JSON** (doc or table).
6. JSON is saved in the **Data** table (`digital_data`); metadata/path in `raw_data`.

---

## Database

### Table: `data`

| Column        | Type           | Purpose |
|---------------|----------------|--------|
| `id`          | bigint, PK     | Primary key |
| `name`        | string         | User-facing name (e.g. filename or title) |
| `raw_data`    | json/text      | Ref to original: e.g. `{"disk":"s3","path":"uploads/xxx.jpg"}` |
| `digital_data`| json           | AI output: `{"type":"doc"|"table","content":"..."}` |
| `created_at`  | timestamp      | |
| `updated_at`  | timestamp      | |

- **No DB-level enforcement** of `doc` vs `table`; that lives only inside `digital_data`.

---

## Backend (Laravel)

### 1. Config & dependencies

- Publish Laravel AI config: `php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"`.
- Ensure `.env` has AI provider key (e.g. `OPENAI_API_KEY`) and S3 vars if using S3.

### 2. Migration

- Ensure `data` table has: `id`, `name`, `raw_data`, `digital_data`, `timestamps`.
- Use JSON columns for `raw_data` and `digital_data` (or text if preferred).

### 3. Model

- `App\Models\Data`: fillable `name`, `raw_data`, `digital_data`; cast `raw_data` and `digital_data` as array (or json).

### 4. API route

- **POST** `/api/digitalize`: accept JSON body with base64 `file` (image or video).
- **GET** `/api/data/{id}`: fetch a single Data record by id (returned from upload).
- Local dev: server is at **digitalizewithai.test** (e.g. `POST https://digitalizewithai.test/api/digitalize`).
- Auth: use existing `auth` middleware if app is authenticated.

### 5. Controller (keep it very simple)

- **Single action**: e.g. `DigitalizeController@store`.
- Steps:
  1. Validate: required `file`, allowed mimes (image: jpeg, png, gif, webp; video: mp4, quicktime, webm).
  2. Decode base64 and store: `Storage::put($path, $decoded)`.
  3. Build `raw_data`: e.g. `['disk' => 's3', 'path' => $path]`.
  4. Call **Laravel AI** agent with:
     - Attachment: images `Image::fromBase64($base64, $mime)`, video `Document::fromBase64($base64, $mime)`.
     - Prompt: “extract content from image or video; return structured JSON (doc or table).”
  5. Agent returns **structured output** (see below).
  6. Create `Data` record: `name` (e.g. original filename), `raw_data`, `digital_data` = agent response.
  7. Return JSON: e.g. `{ "id", "name", "digital_data" }`.

### 6. Laravel AI – Agent with structured output

- Use **one agent** (e.g. `DigitalizeAgent`) with **HasStructuredOutput**.
- **Instructions**: Extract all handwritten (or printed) content from the image. Classify as either a **doc** (prose/notes) or **table** (tabular data). Return a single JSON object.
- **Schema** (minimal, flexible):
  - `type`: string (`"doc"` or `"table"`).
  - `content`: string.
    - For **doc**: markdown or plain text.
    - For **table**: JSON string of `{"headers":["..."],"rows":[["..."],...]}` so it can be parsed and used for charts/tables.
- Attach the image (from S3 or upload) when prompting the agent.
- No DB-level type enforcement; the app/frontend can branch on `digital_data.type`.

### 7. Video

- API accepts **images** (jpeg, png, gif, webp) and **video** (mp4, mov, webm). File is sent as **base64** in JSON.
- Video is sent to the AI as a document attachment. **Video support depends on the provider**: e.g. **Gemini** supports video; OpenAI may not. Configure a video-capable provider in `config/ai.php` or use Gemini as default for this use case if you need video.

---

## Frontend (later)

- UI to upload image (and optionally video).
- Call `POST /api/digitalize` with the file.
- Display result: if `doc` → render markdown/text; if `table` → render table and optionally “spin up” charts from the same `digital_data`.

---

## File Structure (backend)

```
app/
  Http/Controllers/
    Api/
      DigitalizeController.php   # POST: upload → S3 → AI → save Data
  Models/
    Data.php
  Ai/
    Agents/
      DigitalizeAgent.php        # instructions + schema, no tools
database/migrations/
  xxxx_create_data_table.php     # id, name, raw_data, digital_data
routes/
  api.php                        # POST /digitalize → DigitalizeController@store
config/
  ai.php                         # published from Laravel AI
```

---

## Summary Checklist

- [ ] Docs: this plan under `/docs`.
- [ ] Migration: `data` table with `name`, `raw_data`, `digital_data`.
- [ ] Model: `Data` with casts.
- [ ] Publish AI config; set env keys.
- [ ] Agent: `DigitalizeAgent` (instructions + structured schema: type + content).
- [ ] Controller: validate file → S3 → prompt agent with image → save to `Data` → return JSON.
- [ ] Route: `POST /api/digitalize` (auth if needed).
- [ ] Keep code very simple; no extra layers unless needed.
