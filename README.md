# Digitalize with AI

Turn documents and spreadsheets into structured data with AI. Upload files (PDF, images, Excel, CSV), get extracted tables and text, then query your data in natural language—all in one place.

**Stack:** Laravel 12, Vue 3 (Inertia), Vite. PHP 8.2+, Node.js, Composer required.

---

## Amazon Nova (Hackathon)

This app uses **Amazon Nova** as its default AI provider for document understanding and data Q&A:

- **Nova 2 Lite** (and configurable Nova models) via the [Nova API](https://api.nova.amazon.com/v1) for:
  - **Document extraction** — tables, lists, and key-value pairs from PDFs, images, and spreadsheets
  - **Data Q&A** — natural-language questions over your uploaded datasets with chart suggestions
- Custom **Nova gateway** and **Nova-specific agent** (`NovaGateway`, `DigitalizeAgentNova`) for structured extraction and table vs. document detection.
- Extraction model is selectable in the UI (default: Amazon Nova); optional app-wide Nova attribution via `APP_AI_ATTRIBUTION` (see [docs/REBRAND.md](docs/REBRAND.md)).

Requires a Nova API key from [nova.amazon.com/dev/api](https://nova.amazon.com/dev/api). Set `NOVA_API_KEY` in `.env` (see `.env.example`).

---

## Setup

**One command** (installs PHP deps, copies `.env`, generates key, runs migrations, installs npm deps, builds assets):

```bash
composer run setup
```

**Or step by step:**

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

Default DB is SQLite; create `database/database.sqlite` if needed, or set `DB_*` in `.env` for MySQL/PostgreSQL.

## Run

Start dev server, queue worker, logs, and Vite:

```bash
composer dev
```

Then open **http://localhost:8000** (or the URL shown in the terminal).

## Production / Docker

See [docker/README.md](docker/README.md) for Docker, Octane, and deployment (e.g. Render).
