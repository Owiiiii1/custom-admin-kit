# Installation Guide — v0.1 core-only

## 1. Require package

```bash
composer require owlsolutions/custom-admin-kit
```

Recommended host packages (doctor warns if missing):

```bash
composer require inertiajs/inertia-laravel tightenco/ziggy
```

## 2. Doctor

```bash
php artisan owl-admin:doctor --preset=core
```

Prints: **Core preset does not install landing domain modules.**

Checks: PHP/Laravel/Node, writable paths, `.env` keys, recommended Composer dependencies, **frontend npm packages (warn if missing)**, publish conflicts.

## 3. Frontend npm dependencies

The package **does not overwrite** `package.json` or `vite.config.js` — those require manual merge in the host app.

Core preset publishes JSX/CSS stubs that need npm packages. Doctor and install scan `package.json` for:

**From stub imports:** `react`, `react-dom`, `@inertiajs/react`, `lucide-react`, `radix-ui`, `class-variance-authority`, `clsx`, `tailwind-merge`

**Build toolchain (audit-aligned PostCSS + Vite):** `vite`, `@vitejs/plugin-react`, `laravel-vite-plugin`, `tailwindcss`, `postcss`, `autoprefixer`, `tailwindcss-animate`

`route()` in AdminLayout uses `@routes` in Blade + `tightenco/ziggy` (Composer) — **not** `ziggy-js` npm.

### Manual install

```bash
npm install react react-dom @inertiajs/react lucide-react radix-ui class-variance-authority clsx tailwind-merge vite @vitejs/plugin-react laravel-vite-plugin tailwindcss postcss autoprefixer tailwindcss-animate
```

Or copy the exact command from `owl-admin:doctor` / `owl-admin:install` output.

### Automatic install

```bash
php artisan owl-admin:install --preset=core --install-frontend-deps --backup
```

- Shows missing packages before `npm install`
- Runs `npm install <missing>` in the Laravel project root
- With `-n` / `--no-interaction`, npm install runs **only** when `--install-frontend-deps` is also passed

If deps are missing and `--install-frontend-deps` is **not** passed, install **stops before publishing stubs** (exit code 1).

## 4. Admin user (no hardcoded credentials)

The package **never** publishes a DatabaseSeeder with `admin@admin.com` / `admin`.

Create an admin after install:

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

| Rule | Behavior |
|------|----------|
| Non-interactive `--seed` | Requires `OWL_ADMIN_EMAIL` + `OWL_ADMIN_PASSWORD` in `.env` (FAIL if missing) |
| Interactive `--seed` | Prompts for missing email/password |
| Local dev auto-password | `OWL_ADMIN_ALLOW_DEFAULT_PASSWORD=true` + `APP_ENV=local` generates a random password if `OWL_ADMIN_PASSWORD` is empty |
| Forbidden | `admin@admin.com`, password `admin` — always rejected |

Without `--seed`, install only publishes stubs. Run `owl-admin:make-admin` separately.

## 5. Dry run

```bash
php artisan owl-admin:install --preset=core --dry-run
```

Shows:

- frontend required: yes/no
- missing npm packages
- suggested `npm install` command
- whether a real install would be blocked

Lists core stub files without writing.

## 6. Install

```bash
php artisan owl-admin:install --preset=core --backup
```

| Option | Description |
|--------|-------------|
| `--preset` | `core` only in v0.1 |
| `--force` | Overwrite existing destination files |
| `--backup` | Copy `.owl-admin-backup-*` before overwrite |
| `--dry-run` | Plan only |
| `--install-frontend-deps` | Install missing npm packages before publishing |
| `--seed` | Create admin user after publish (env or interactive; never hardcoded) |

Blocked presets (`full`, `auth`, `frontend`) exit with code 1:

```text
Full preset is not available in v0.1. Use --preset=core.
```

Writes:

- `storage/app/owl-admin-kit.json` — install state
- `storage/app/owl-admin-kit-report.json` — steps, warnings, `frontend`, `merge_required`

## 7. Merge steps (manual)

Not published by core preset:

| Host file | Action |
|-----------|--------|
| `resources/js/app.jsx` | Register Inertia pages |
| `vite.config.js` | React + Tailwind plugins |
| `package.json` | Ensure deps + scripts (package adds via `--install-frontend-deps` only) |
| `routes/web.php` | Dashboard and domain routes — use `docs/merge-snippets/core-routes.php` |
| `app/Models/User.php` | Host user model |
| `HandleInertiaRequests` | Share `owlAdmin.brand_name` from `config('owl-admin.brand_name')` |

Core routes example (`AdminRouteMiddleware::stack()` — no `verified` by default):

```php
use OwlSolutions\CustomAdminKit\Support\AdminRouteMiddleware;

Route::middleware(AdminRouteMiddleware::stack())->group(function () {
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');
});
```

### Email verification (optional, off by default)

v0.1 does **not** require email verification. Default:

```env
OWL_ADMIN_EMAIL_VERIFICATION=false
```

To enable verification manually:

1. Set `OWL_ADMIN_EMAIL_VERIFICATION=true` in `.env`
2. Implement `MustVerifyEmail` on `App\Models\User`
3. Ensure auth verification routes/mail are configured in the host app
4. Run `php artisan owl-admin:doctor` — fails if User lacks `MustVerifyEmail`

Branding env keys:

```env
OWL_ADMIN_BRAND="My Company Admin"
OWL_ADMIN_LOGO="/images/company-logo.svg"
```

## 8. Smoke test

```bash
php artisan owl-admin:smoke --preset=core
```

## 9. Repair

```bash
php artisan owl-admin:repair --preset=core --backup --force
```

Republishes core stubs from package.

## 10. Frontend setup (v0.2)

After `owl-admin:install --preset=core`, prepare the host frontend:

```bash
php artisan owl-admin:frontend-setup --preset=core --dry-run
php artisan owl-admin:frontend-setup --preset=core --backup --install-npm --run-build
```

The command checks host files (`package.json`, `vite.config.js`, `app.jsx`, `app.css`, `HandleInertiaRequests`) and builds a safe merge plan. Without `--backup` or `--force`, host files are not overwritten.

Backups are stored in `storage/app/owl-admin-kit/backups/YYYY-MM-DD-HH-mm-ss/`.
