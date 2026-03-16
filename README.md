# Digitalize with AI

Laravel 12 + Vue 3 (Inertia) + Vite. PHP 8.2+, Node.js, Composer required.

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
