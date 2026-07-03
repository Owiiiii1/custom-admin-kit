# Troubleshooting

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

## Backup location

Pre-sync package backup: `.backup-before-audit-sync/`

Install-time file backups: `*.owl-admin-backup-YYYYMMDDHHMMSS` next to overwritten files.

Frontend setup backups: `storage/app/owl-admin-kit/backups/YYYY-MM-DD-HH-mm-ss/` (includes `package.json` when `--backup` is used).
