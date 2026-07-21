# Troubleshooting

## Frontend setup (v0.2.0)

### npm build fails

After `owl-admin:frontend-setup`, run:

```bash
npm install && npm run build
```

Or use the command flags:

```bash
php artisan owl-admin:frontend-setup --preset=core --backup --install-npm --run-build
```

Common causes:

| Symptom | Fix |
|---------|-----|
| `Cannot find module 'react'` / missing deps | Re-run with `--install-npm` or merge `package.json` manually |
| Vite cannot resolve `@/Components/ui` | Ensure `vite.config.js` has `@` alias (standard Laravel 13 config) |
| `app.jsx` not in manifest | Re-run frontend-setup with `--backup`; check dry-run for missing Vite inputs |
| Build succeeds but smoke fails `vite-manifest` | Run `npm run build`; confirm `public/build/manifest.json` exists |

Inspect dry-run first:

```bash
php artisan owl-admin:frontend-setup --preset=core --dry-run
```

### Tailwind v4 issues

Laravel 13 ships Tailwind v4 with `@import 'tailwindcss'` and `@tailwindcss/vite`. The package stub `resources/css/owl-admin.css` uses Tailwind v4 syntax (`@theme inline`).

If you see errors like **`Cannot apply unknown utility class border-border`** or **`@tailwind` directive errors**:

1. Ensure `owl-admin.css` is the v0.2 stub (not a v3 `@tailwind base/components/utilities` file)
2. Frontend-setup adds `@import './owl-admin.css'` and `@plugin "tailwindcss-animate"` to `resources/css/app.css` on Tailwind v4 hosts
3. Install `tailwindcss-animate` if missing:

```bash
npm install -D tailwindcss-animate
```

Do **not** mix Tailwind v3 PostCSS-only setup with the v4 stub without adapting `app.css`.

### vite.config.js non-standard

Frontend setup only auto-merges **standard** Laravel Vite configs: single `laravel()` call from `laravel-vite-plugin` with a literal `input` string or array.

**Non-standard** examples that block auto-merge:

- Dynamic `input` (variables, spread, function calls)
- Multiple `laravel()` plugin calls
- Missing `laravel-vite-plugin`
- Custom build pipeline without parseable inputs

Dry-run shows:

```text
vite.config.js:
  status: non-standard
  action: manual
  manual snippet: docs/merge-snippets/vite.config.js
```

Copy/adapt the snippet, then re-run smoke:

```bash
npm run build
php artisan owl-admin:smoke --preset=core
```

Use `--strict` to fail early when auto-merge is impossible:

```bash
php artisan owl-admin:frontend-setup --preset=core --strict --dry-run
```

### HandleInertiaRequests missing

`AdminLayout.jsx` reads shared Inertia props:

```js
usePage().props.owlAdmin.brand_name
usePage().props.owlAdmin.logo_path
```

If middleware does not exist, create it:

```bash
composer require inertiajs/inertia-laravel
php artisan inertia:middleware
```

Register the middleware in `bootstrap/app.php` (Laravel 11+) if not already present, then re-run:

```bash
php artisan owl-admin:frontend-setup --preset=core --backup
```

With `--strict`, missing middleware fails instead of warning.

If `share()` is non-standard, merge manually from `docs/merge-snippets/HandleInertiaRequests.php`.

### inertia:middleware not run

Symptoms:

- Doctor warns about missing `HandleInertiaRequests`
- Frontend-setup reports `HandleInertiaRequests: status: missing`
- Inertia pages render without shared props or fail at runtime

Fix:

```bash
composer require inertiajs/inertia-laravel
php artisan inertia:middleware
php artisan owl-admin:frontend-setup --preset=core --backup
```

Ensure `HandleInertiaRequests` is in the web middleware stack (`bootstrap/app.php` → `->withMiddleware(...)`).

### routes/web.php merge blocked

Frontend-setup creates `routes/owl-admin-pages.php` and tries to add:

```php
require __DIR__.'/owl-admin-pages.php';
```

Auto-merge is blocked when `routes/web.php` is **non-standard** (conditional requires, non-literal structure, or existing owl-admin include).

Dry-run output:

```text
routes:
  web.php include: no
  action: manual
  manual snippet: docs/merge-snippets/web.php
```

Without `--backup` / `--force`:

```text
Route setup changes require --backup or --force.
```

Add the include manually, then verify:

```bash
php artisan route:list --name=dashboard
php artisan route:list --name=settings
php artisan owl-admin:smoke --preset=core
```

### auth/login still required

Core preset does **not** publish login/register routes or Breeze/Jetstream scaffolding. Smoke may report **login route not found** until the host wires authentication.

Options:

1. Install Laravel Breeze (Inertia + React) or your existing auth stack
2. Add minimal login routes in the host app
3. For local testing only, temporarily access routes after manual session auth

The package registers **admin page routes** (`/dashboard`, `/settings`, etc.) behind `AdminRouteMiddleware::stack()` — they expect an authenticated user, not a guest.

### admin user not created

Install does **not** create a default `admin@admin.com` user. Create one explicitly:

```bash
php artisan owl-admin:make-admin
```

Or during install with `--seed`:

```env
OWL_ADMIN_EMAIL=you@example.com
OWL_ADMIN_PASSWORD=your-secure-password
OWL_ADMIN_NAME="Site Admin"
```

```bash
php artisan owl-admin:install --preset=core --seed --backup
```

Non-interactive `--seed` requires `OWL_ADMIN_EMAIL` and `OWL_ADMIN_PASSWORD` in `.env`. Passwords `admin` and email `admin@admin.com` are always rejected.

---

## AI Settings (v0.5.0)

### AI key not saving

Symptom: Settings → AI accepts input but the key does not persist / request body is empty.

Cause: broken Inertia `useForm().post(..., { data: ... })` options pattern.

Fix: ensure `resources/js/Pages/Settings/AiPanel.jsx` saves with:

```js
router.post(route('ai-settings.save-key', provider), { api_key: value }, { preserveScroll: true })
```

Do not pass payload via `useForm` `options.data`. Smoke check `ai-panel-router-post` verifies this.

## Telegram webhook (v0.5.0)

### Webhook returns 403

Symptom: Telegram (or a manual POST) hits `telegram.webhook` and gets `403 Forbidden`.

Cause: missing or mismatched secret header. The controller compares `X-Telegram-Bot-Api-Secret-Token` to the encrypted `webhook_secret` in `telegram_bot_settings`.

Fix:

1. Set webhook from Settings → Telegram (generates/stores secret and registers it with Telegram)
2. Confirm `APP_URL` is the public HTTPS URL used for the webhook
3. Manual tests must send the same secret in `X-Telegram-Bot-Api-Secret-Token`

### Webhook returns 419

Symptom: `POST /telegram/webhook` returns `419 Page Expired`.

Cause: CSRF / request forgery middleware still applied (Laravel 13 web stack uses `PreventRequestForgery`).

Fix: keep `withoutMiddleware([PreventRequestForgery::class, ValidateCsrfToken::class])` on `telegram.webhook` in `routes/owl-admin-pages.php`, then `php artisan route:clear`.

## Doctor failures

### Required host dependency missing

Install on host app:

```bash
composer require inertiajs/inertia-laravel tightenco/ziggy
```

### Missing frontend npm packages (warning on doctor, failure on install)

Doctor lists missing packages and a suggested command:

```text
! frontend-deps: Missing npm package(s): react, react-dom, ...
  → npm install react react-dom ...
```

**Why the package does not overwrite `package.json` during install:** merging would destroy host scripts, versions, and unrelated dependencies. The installer only **checks** or optionally runs `npm install` via `--install-frontend-deps`.

**Safe merge (v0.2):** use `owl-admin:frontend-setup` to add missing packages only:

```bash
php artisan owl-admin:frontend-setup --preset=core --dry-run
php artisan owl-admin:frontend-setup --preset=core --backup
```

If the command would change `package.json` and you omit `--backup` / `--force`:

```text
package.json changes require --backup or --force.
```

### Non-standard or incomplete `vite.config.js`

Frontend setup inspects the host Vite config without rewriting unknown structures.

**Standard config** (single `laravel()` call, literal `input` string or array): missing inputs can be added automatically:

```bash
php artisan owl-admin:frontend-setup --preset=core --dry-run
php artisan owl-admin:frontend-setup --preset=core --backup
```

**Non-standard config** (no `laravel-vite-plugin`, dynamic `input`, multiple `laravel()` calls): auto-merge is blocked. Copy/adapt the package snippet:

```text
docs/merge-snippets/vite.config.js
```

Example dry-run output:

```text
vite.config.js:
  status: non-standard
  missing inputs: resources/js/app.jsx
  action: manual
  manual snippet: docs/merge-snippets/vite.config.js
```

If the Inertia app entry is missing and the config cannot be auto-merged:

```bash
php artisan owl-admin:frontend-setup --preset=core --strict
```

exits with an error. Without `--strict`, the command warns and continues planning other merges.

If auto-merge is available but you omit `--backup` / `--force`:

```text
vite.config.js changes require --backup or --force.
```

### HandleInertiaRequests missing or non-standard

`AdminLayout.jsx` expects shared Inertia props:

```js
usePage().props.owlAdmin.brand_name
usePage().props.owlAdmin.logo_path
```

If middleware is missing:

```bash
composer require inertiajs/inertia-laravel
php artisan inertia:middleware
```

Then re-run:

```bash
php artisan owl-admin:frontend-setup --preset=core --dry-run
php artisan owl-admin:frontend-setup --preset=core --backup
```

With `--strict`, missing middleware fails instead of warning.

If `share()` is non-standard, merge manually from:

```text
docs/merge-snippets/HandleInertiaRequests.php
```

Example dry-run output:

```text
HandleInertiaRequests:
  status: exists
  owlAdmin share: no
  action: auto-merge
  will write: no
```

Without `--backup` / `--force`:

```text
HandleInertiaRequests changes require --backup or --force.
```

### Core admin routes missing or non-standard web.php

Install requires Inertia on the host:

```bash
composer require inertiajs/inertia-laravel
```

Then create/link routes:

```bash
php artisan owl-admin:frontend-setup --preset=core --dry-run
php artisan owl-admin:frontend-setup --preset=core --backup
```

This creates `routes/owl-admin-pages.php` and may add to `routes/web.php`:

```php
require __DIR__.'/owl-admin-pages.php';
```

If `routes/web.php` is non-standard, merge manually using `docs/merge-snippets/web.php`.

Example dry-run output:

```text
routes:
  owl-admin-pages.php: missing
  web.php include: no
  inertia dependency: no
  action: blocked
  install hint: composer require inertiajs/inertia-laravel
```

Without `--backup` / `--force`:

```text
Route setup changes require --backup or --force.
```

Fix manually:

```bash
npm install react react-dom @inertiajs/react lucide-react radix-ui class-variance-authority clsx tailwind-merge vite @vitejs/plugin-react laravel-vite-plugin tailwindcss postcss autoprefixer tailwindcss-animate
```

Or let install add them:

```bash
php artisan owl-admin:install --preset=core --install-frontend-deps --backup --force
```

Install is **blocked** before stub publish when deps are missing and `--install-frontend-deps` is not used.

### Missing admin credentials (--seed)

Non-interactive install with `--seed` requires env keys:

```env
OWL_ADMIN_EMAIL=you@example.com
OWL_ADMIN_PASSWORD=your-secure-password
```

Without them, install fails **before** stub publish:

```text
Install aborted: --seed requires OWL_ADMIN_EMAIL and OWL_ADMIN_PASSWORD in non-interactive mode.
```

Never use `admin@admin.com` or password `admin` — rejected explicitly.

Create admin manually instead:

```bash
php artisan owl-admin:make-admin --email=you@example.com --password=your-secure-password
```

Local dev only: `OWL_ADMIN_ALLOW_DEFAULT_PASSWORD=true` with `APP_ENV=local` allows a **random** generated password when `OWL_ADMIN_PASSWORD` is empty.

### Required env key missing

Set keys listed in [docs/REQUIRED_ENV_KEYS.md](./docs/REQUIRED_ENV_KEYS.md). Installer **never** writes `.env`.

### Blocked conflict (critical/high)

Existing file would be overwritten. Options:

1. `php artisan owl-admin:install --dry-run` — inspect plan
2. Resolve manually using `docs/merge-snippets/`
3. `php artisan owl-admin:install --backup --force` — last resort for copy-mode files only

## Install warnings

### `[MERGE] Manual step required`

Expected for `routes/web.php`, `app.jsx`, `vite.config.js`, `User.php`. Follow install report and merge snippets.

### Stubs skipped

Destination exists without `--force`. Use `--backup --force` or delete conflicting file.

## Smoke failures

### Vite manifest missing

```bash
npm install && npm run build
```

### Login route not found

Add to host `routes/web.php`:

```php
require __DIR__.'/owl-admin-auth.php';
```

Plus admin web routes from merge snippet (`docs/merge-snippets/core-routes.php`).

### Dashboard returns 403 "email address is not verified"

You enabled `OWL_ADMIN_EMAIL_VERIFICATION=true` but the user is not verified, or `User` does not implement `MustVerifyEmail`.

**Option A — disable verification (v0.1 default):**

```env
OWL_ADMIN_EMAIL_VERIFICATION=false
```

Use `AdminRouteMiddleware::stack()` in routes (no hardcoded `verified` middleware).

**Option B — enable properly:**

1. Add `MustVerifyEmail` to `App\Models\User`
2. Configure mail + verification routes in the host app
3. Verify the admin user's email or set `email_verified_at` during seed

Run `php artisan owl-admin:doctor` to confirm User implements `MustVerifyEmail`.

### Published files missing

Re-run:

```bash
php artisan owl-admin:repair --force --backup
```

## Permissions

```bash
chmod -R ug+rwx storage bootstrap/cache
```

## Spatie duplicate migrations

Do **not** publish Spatie migrations if tables already exist. See [docs/TODO_DEPENDENCIES.md](./docs/TODO_DEPENDENCIES.md).

## Backup locations

| Context | Location |
|---------|----------|
| **Frontend setup** (`--backup`) | `storage/app/owl-admin-kit/backups/YYYY-MM-DD-HH-mm-ss/` |
| Install stub overwrite | `*.owl-admin-backup-YYYYMMDDHHMMSS` next to the file |
| Pre-sync package backup | `.backup-before-audit-sync/` (maintainer only) |
