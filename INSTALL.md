# Installation Guide

## Version scope

| Version | What it does |
|---------|----------------|
| **v0.1.1** | **Core stubs only** — `owl-admin:install` publishes 23 files (config, health route, JSX/CSS/UI). Host must merge `package.json`, Vite, Inertia entry, routes, and middleware manually. |
| **v0.2.0** | **Core stubs + frontend setup** — everything in v0.1.x plus `owl-admin:frontend-setup` for safe merges of host frontend files and core admin routes. |
| **v0.3.0** | **Admin preset available** — `preset=admin` installs core + login/logout/profile/settings-users + AI Settings pages and generic controllers/services. |

Recommended flow for new projects: install core stubs, then run frontend setup with `--backup`.

---

## 1. Require package

```bash
composer config repositories.custom-admin-kit vcs git@github.com:Owiiiii1/custom-admin-kit.git
composer require owlsolutions/custom-admin-kit:v0.3.0
```

Or from Packagist / path repository:

```bash
composer require owlsolutions/custom-admin-kit
```

Recommended host packages (doctor warns if missing):

```bash
composer require inertiajs/inertia-laravel tightenco/ziggy
php artisan inertia:middleware
```

## 2. Doctor

```bash
php artisan owl-admin:doctor --preset=admin
```

Prints: **Admin preset installs generic auth/admin shell (no business domain modules).**

Checks: PHP/Laravel/Node, writable paths, `.env` keys, recommended Composer dependencies, **frontend npm packages (warn if missing)**, publish conflicts.

## 3. Frontend npm dependencies

The package **does not overwrite** `package.json` or `vite.config.js` during `owl-admin:install` — use `owl-admin:frontend-setup` for safe merges (v0.2).

Core preset publishes JSX/CSS stubs that need npm packages. Doctor and install scan `package.json` for packages listed in `config/owl-admin-kit.php` → `frontend_dependencies`:

**dependencies:** `@inertiajs/react`, `react`, `react-dom`, `lucide-react`, `radix-ui`, `class-variance-authority`, `clsx`, `tailwind-merge`

**devDependencies:** `@vitejs/plugin-react`, `postcss`, `autoprefixer`, `tailwindcss-animate`

**Conditional devDependencies (added only when missing):** `vite`, `laravel-vite-plugin`, `tailwindcss` — Laravel 13 often already includes these. `@tailwindcss/vite` is never added unless the host `vite.config.js` already references it.

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
php artisan owl-admin:install --preset=admin --seed --backup
```

| Rule | Behavior |
|------|----------|
| Non-interactive `--seed` | Requires `OWL_ADMIN_EMAIL` + `OWL_ADMIN_PASSWORD` in `.env` (FAIL if missing) |
| Interactive `--seed` | Prompts for missing email/password |
| Local dev auto-password | `OWL_ADMIN_ALLOW_DEFAULT_PASSWORD=true` + `APP_ENV=local` generates a random password if `OWL_ADMIN_PASSWORD` is empty |
| Explicit CLI credentials | `--email`/`--password` values are accepted as-is (warning only for weak credentials). |

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
php artisan owl-admin:install --preset=admin --backup
```

| Option | Description |
|--------|-------------|
| `--preset` | `core` or `admin` |
| `--force` | Overwrite existing destination files |
| `--backup` | Copy `.owl-admin-backup-*` before overwrite |
| `--dry-run` | Plan only |
| `--install-frontend-deps` | Install missing npm packages before publishing |
| `--seed` | Create admin user after publish (env or interactive; never hardcoded) |

Blocked preset (`full`) exits with code 1:

```text
Full preset is not available yet. Use --preset=admin.
```

Writes:

- `storage/app/owl-admin-kit.json` — install state
- `storage/app/owl-admin-kit-report.json` — steps, warnings, `frontend`, `merge_required`

## 7. Merge steps (manual)

Not published by core preset:

| Host file | Action |
|-----------|--------|
| `resources/js/app.jsx` | Register Inertia pages |
| `vite.config.js` | Safe input merge via `owl-admin:frontend-setup` (standard Laravel config only) |
| `package.json` | Safe merge via `owl-admin:frontend-setup` (adds missing deps only) |
| `routes/web.php` | Include `owl-admin-pages.php` via `owl-admin:frontend-setup` or `docs/merge-snippets/web.php` |
| `app/Models/User.php` | Host user model |
| `HandleInertiaRequests` | Share `owlAdmin` props via `owl-admin:frontend-setup` or `docs/merge-snippets/HandleInertiaRequests.php` |

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

## 8.1 AI Settings (preset=admin)

- Supported providers: `OpenAI`, `Anthropic`, `Gemini`
- API keys are stored encrypted in `ai_provider_settings` (`api_key` encrypted cast)
- Exactly one provider/model can be active at a time
- Header badge in `AdminLayout` reflects AI state:
  - `AI: not connected`
  - `AI: connected — {provider} / {model}`

## 9. Repair

```bash
php artisan owl-admin:repair --preset=core --backup --force
```

Republishes core stubs from package.

## 10. Frontend setup (v0.2.0)

After `owl-admin:install --preset=core`, prepare the host frontend and core admin routes:

```bash
php artisan owl-admin:frontend-setup --preset=core --dry-run
php artisan owl-admin:frontend-setup --preset=core --backup --install-npm --run-build
php artisan owl-admin:smoke --preset=core
```

### What `frontend-setup` may change

| Host file | Action |
|-----------|--------|
| `package.json` | Merge — add missing npm dependencies/devDependencies only |
| `vite.config.js` | Merge — add missing Vite inputs + `@vitejs/plugin-react` (standard configs) |
| `resources/css/app.css` | Merge — `@import './owl-admin.css'` + `@plugin "tailwindcss-animate"` on Tailwind v4 hosts |
| `resources/js/app.jsx` | Create — standard Inertia React entrypoint (does not overwrite existing `app.jsx`) |
| `app/Http/Middleware/HandleInertiaRequests.php` | Merge — shared `owlAdmin` props |
| `routes/owl-admin-pages.php` | Create — dashboard, settings, app-settings, statistics/logs |
| `routes/web.php` | Merge — `require __DIR__.'/owl-admin-pages.php';` (standard configs only) |

**Never overwritten:** existing dependency versions, non-standard host files, `resources/js/app.js`, auth routes, `User.php`.

### Backup policy

- **Always use `--backup`** for production or shared environments before the first frontend setup.
- Without `--backup` or `--force`, the command **refuses to write** when changes are needed.
- Backups are stored in:

```text
storage/app/owl-admin-kit/backups/YYYY-MM-DD-HH-mm-ss/
```

Example backed-up files: `package.json`, `vite.config.js`, `resources/css/app.css`, `HandleInertiaRequests.php`, `routes/web.php`.

Install-time stub backups (separate) remain next to overwritten files as `*.owl-admin-backup-YYYYMMDDHHMMSS`.

The command checks host files and builds a safe merge plan before writing.

### Safe `package.json` merge

`PackageJsonMerger` reads the host `package.json` and **adds only missing** packages from `frontend_dependencies`. It:

- preserves existing package names and versions
- never removes unrelated dependencies
- writes with `JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES`
- sorts only dependency groups that were modified

Dry-run output:

```text
package.json merge:
  missing dependencies: react, @inertiajs/react, ...
  missing devDependencies: @vitejs/plugin-react, postcss, ...
  will write: no (dry-run)
```

If changes are needed, the command **refuses to write** without `--backup` or `--force`:

```text
package.json changes require --backup or --force.
```

| Flag | Behavior |
|------|----------|
| `--dry-run` | Show merge plan; never write files |
| `--backup` | Copy host files to `storage/app/owl-admin-kit/backups/YYYY-MM-DD-HH-mm-ss/` before merge |
| `--force` | Apply merges without creating a backup |
| `--install-npm` | Run `npm install` for packages still missing after merge |
| `--run-build` | Run `npm run build` after setup |

Without `--backup` or `--force`, host files are not modified.

### Safe `vite.config.js` merge

Core kit publishes CSS/JS stubs under `resources/css/owl-admin.css`, `resources/js/Pages/`, `resources/js/Layouts/`, and `resources/js/Components/ui/`. v0.2 does **not** add a separate Vite entry for each page — the host Inertia app entry must load them.

`ViteConfigMerger` is conservative:

| Host config | Behavior |
|-------------|----------|
| **standard** Laravel `vite.config.js` with `laravel-vite-plugin` and a parseable `input` string/array | Auto-merge missing inputs only (`resources/css/app.css`, `resources/js/app.jsx` or `app.js`) with `--backup` / `--force` |
| **non-standard** (dynamic input, no laravel plugin, multiple `laravel()` calls, etc.) | No auto-write — manual merge from `docs/merge-snippets/vite.config.js` |
| **missing** `vite.config.js` | FAIL (prerequisite) |

Dry-run output:

```text
vite.config.js:
  status: standard
  missing inputs: resources/js/app.jsx
  action: auto-merge
  will write: no (dry-run)
```

If the Inertia app entry is missing from `laravel({ input: ... })`:

- default: **WARN** in prerequisites
- `--strict`: **FAIL** when auto-merge is not possible

Required inputs (when absent):

- `resources/css/app.css`
- `resources/js/app.jsx` if that file exists, otherwise `resources/js/app.js`

Non-standard configs show:

```text
vite.config.js:
  status: non-standard
  missing inputs: resources/js/app.jsx
  action: manual
  manual snippet: docs/merge-snippets/vite.config.js
  will write: no (manual merge required)
```

If auto-merge would change `vite.config.js`:

```text
vite.config.js changes require --backup or --force.
```

### Safe `HandleInertiaRequests` merge

`AdminLayout.jsx` reads `usePage().props.owlAdmin.brand_name` and `logo_path`. The host middleware must share `owlAdmin`.

| Host middleware | Behavior |
|-----------------|----------|
| **missing** | WARN (FAIL with `--strict`); install hint: `composer require inertiajs/inertia-laravel && php artisan inertia:middleware` |
| **exists** + `owlAdmin` already shared | OK — no changes |
| **standard** `share(Request $request): array` with `array_merge(parent::share($request), [` | Auto-merge `owlAdmin` with `--backup` / `--force` |
| **non-standard** share method | Manual merge from `docs/merge-snippets/HandleInertiaRequests.php` |

Auto-merge adds:

```php
'owlAdmin' => fn () => config('owl-admin.branding', [
    'brand_name' => config('owl-admin.brand_name', config('owl-admin.name', 'Service Admin')),
    'logo_path' => config('owl-admin.logo_path', '/images/company-logo.svg'),
]),
```

Dry-run output:

```text
HandleInertiaRequests:
  status: exists
  owlAdmin share: no
  action: auto-merge
  will write: no (dry-run)
```

If auto-merge would change the middleware:

```text
HandleInertiaRequests changes require --backup or --force.
```

### Safe admin routes (v0.3)

Core JSX pages are published by install, but `routes/web.php` is never overwritten. Frontend setup can:

1. Create `routes/owl-admin-pages.php` from the package stub
2. Add `require __DIR__.'/owl-admin-pages.php';` to a **standard** host `routes/web.php` with `--backup` / `--force`

Routes use `AdminRouteMiddleware::stack()` and register:

| Route | Page | Name |
|-------|------|------|
| `GET /dashboard` | `Dashboard` | `dashboard` |
| `GET /settings` | `Settings/Index` | `settings.index` |
| `GET /app-settings` | `AppSettings/Index` | `app-settings.index` |
| `GET /statistics/logs` | `Statistics/Logs` | `statistics.logs` |
| `GET /ai-settings` | `AiSettings/Index` | `ai-settings.index` |

Requires `inertiajs/inertia-laravel` on the host app.

Dry-run output:

```text
routes:
  owl-admin-pages.php: missing
  web.php include: no
  inertia dependency: yes
  action: auto-merge
  will write: no (dry-run)
```

Non-standard `routes/web.php` → manual snippet: `docs/merge-snippets/web.php`

```text
Route setup changes require --backup or --force.
```
