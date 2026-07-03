# Compatibility Matrix

Synced from landing audit manifest and v0.2.0 integration test (2026-07-03).

## Supported stack

| Component | Supported | Notes |
|-----------|-----------|--------|
| PHP | ^8.3 | 8.3.30 tested |
| Laravel | ^13.0 | **v0.2.0 integration tested on Laravel 13.8** |
| Node | >= 20.19 | **20 / 22 / 24** supported; doctor warns if outside preferred range |
| npm | >= 10 | 11.x observed working |
| Admin transport | Inertia Laravel ^2.0 / ^3.0 | Host must `composer require inertiajs/inertia-laravel` |
| Frontend | React ^18.x | Added by `owl-admin:frontend-setup` when missing |
| CSS | **Tailwind v4 compatible** | Laravel 13 default `@tailwindcss/vite`; stub uses `@theme inline` |
| Build | Vite 8 | Host merge via frontend-setup on standard configs |
| Filament | **Not used** | Not supported by this package |
| Livewire | **Not used** | Not supported by this package |

## Package version matrix

| Package version | Laravel | Status |
|-----------------|---------|--------|
| 0.1.0 | 13.x | Core-only — safe installer, 23 stubs, manual frontend merge |
| 0.1.1 | 13.x | Core-only + smoke `published-files` / vite manifest fixes |
| **0.2.0** | 13.x | **Core stubs + `owl-admin:frontend-setup`** — tested end-to-end on clean Laravel 13 |

## Host dependencies

### Recommended (doctor warns, install proceeds)

- `inertiajs/inertia-laravel` ^2.0 or ^3.0
- `tightenco/ziggy` ^2.0

Run after install:

```bash
php artisan inertia:middleware
```

### Not required by package

- Spatie packages — excluded from core preset
- `laravel/breeze`, `laravel/sanctum` — deferred to future presets; host provides auth/login

## v0.2.0 integration test results (Laravel 13.8)

Verified on clean `laravel/laravel` project:

- `owl-admin:install --preset=core --backup --migrate --no-smoke`
- `owl-admin:frontend-setup --preset=core --backup --install-npm --run-build`
- `npm run build` — success
- `owl-admin:smoke --preset=core` — 10/10 checks including vite manifest
- Routes registered: `dashboard`, `settings.index`, `app-settings.index`, `statistics.logs`, `owl-admin.health`

## Known limitations

| Item | Detail |
|------|--------|
| Auth / login | Package does **not** publish Breeze/Jetstream routes — host must wire login separately |
| Admin user | Not created automatically unless `--seed` on install or `owl-admin:make-admin` |
| Non-standard Vite / routes | Auto-merge blocked — use `docs/merge-snippets/` |
| Email verification | Disabled by default (`OWL_ADMIN_EMAIL_VERIFICATION=false`) |
| Domain modules | Core shell has no customers/orders/staff — host adds separately |
| Duplicate UI paths | Package uses **`resources/js/Components/ui`** only |

## Presets

| Preset | Status |
|--------|--------|
| `core` | **Only available preset** |
| `full`, `auth`, `frontend` | Blocked with exit code 1 |
