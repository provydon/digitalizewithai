# Rebranding (Hackathon vs Product)

The app supports two modes via environment variables so you can submit to the **Amazon Nova hackathon** and later release as a **standalone product** without code changes.

## Hackathon (Amazon Nova)

- **APP_NAME** = `"Digitalize with AI"` (or any display name)
- **APP_AI_ATTRIBUTION** = `Amazon Nova`

Result: “Powered by Amazon Nova” and Nova wordmark appear; copy uses “Nova” where appropriate.

## Product (standalone)

- **APP_NAME** = your product name (e.g. `"Scanflow"`, `"Notable"`)
- **APP_AI_ATTRIBUTION** = leave empty or omit

Result: No “Powered by” line, no Nova logo; copy uses “AI” instead of “Nova”. Sidebar and welcome page show your app name and the generic app icon.

## Where it’s used

- **config/branding.php** – reads `APP_NAME` and `APP_AI_ATTRIBUTION`
- **Shared to frontend** – `branding.name`, `branding.ai_attribution` (null when empty)
- **Welcome page** – hero, feature copy, header logo
- **NovaLogo component** – shows Nova logo + name when attribution is set; otherwise app icon + name
- **Page title** – `{name}` or `{name} — {ai_attribution}`

## Switching after the hackathon

1. Set `APP_NAME` to your product name.
2. Set `APP_AI_ATTRIBUTION=` (empty) or remove the line.
3. Restart the app / clear config cache if you use `php artisan config:cache`.

No code or asset changes required.
