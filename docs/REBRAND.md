# Rebranding (Hackathon vs Product)

The app runs as a **standalone product** by default (no Amazon Nova theme). Nova is available as an extraction option in the upload section and defaults to **Amazon Nova** there so hackathon submissions can show Nova is enabled without app-wide Nova branding.

## Default (standalone)

- **APP_NAME** = `"Digitalize with AI"` (or your product name)
- **APP_AI_ATTRIBUTION** = leave empty or omit (default)

Result: App icon + app name everywhere. No “Powered by” or Nova wordmark. Upload section includes an **Extraction model** dropdown that defaults to **Amazon Nova**; when Nova is selected, a “Nova enabled” badge is shown.

## Optional: app-wide Nova attribution (legacy hackathon theme)

- **APP_AI_ATTRIBUTION** = `Amazon Nova`

Result: If you re-enable this, “Powered by Amazon Nova” and Nova wordmark can appear where the app still references `branding.ai_attribution` (e.g. NovaLogo component). The app is now designed to run without this; the upload dropdown is the primary place Nova is indicated.

## Where it’s used

- **config/branding.php** – reads `APP_NAME` and `APP_AI_ATTRIBUTION`
- **Shared to frontend** – `branding.name`, `branding.ai_attribution` (null when empty)
- **Upload section** – Extraction model dropdown (default: Amazon Nova from `config/ai.default_digitalize_provider`); “Nova enabled” badge when Nova is selected
- **config/ai.php** – `default_digitalize_provider` (default `nova`), `digitalize_providers` list

## Default extraction provider

- **AI_DEFAULT_DIGITALIZE_PROVIDER** = `nova` (default) — dropdown in the upload section defaults to Amazon Nova. Set to `anthropic`, `openai`, etc. to change the default; Nova remains selectable if listed in `digitalize_providers`.
