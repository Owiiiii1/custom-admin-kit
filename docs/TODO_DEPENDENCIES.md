# Dependency decisions — v0.1 core-only (+ v0.5.0 updates)

## Package `composer.json` require

| Package | Status |
|---------|--------|
| `php` ^8.3 | **CONFIRMED** |
| `illuminate/console`, `filesystem`, `support` ^13 | **CONFIRMED** |
| `nutgram/nutgram` ^4.48 | **REQUIRED** (v0.5.0) — pulled into host via package require |

No Spatie, no Filament, no Inertia in package require.

## Host app (recommended, not required for install)

| Package | Why | v0.1 |
|---------|-----|------|
| `inertiajs/inertia-laravel` | Render published JSX | recommended (doctor warns) |
| `tightenco/ziggy` | `route()` in AdminLayout | recommended (doctor warns) |

## Explicitly excluded from v0.1

| Package | Reason |
|---------|--------|
| `spatie/laravel-permission` | Not used in landing app PHP; duplicate migration risk |
| `spatie/laravel-activitylog` | Not wired in app |
| `spatie/laravel-medialibrary` | Not wired in app |
| `spatie/laravel-settings` | Not wired in app |
| `laravel/breeze` | Auth preset deferred |
| `laravel/sanctum` | Optional API; not in core |

## Host npm (merge only — not published)

Core v0.1 does **not** publish or merge `package.json`. The installer checks required packages via `FrontendDependencyChecker` (stub imports + PostCSS/Vite toolchain).

Required for core preset (15 packages):

- **Stub imports:** `react`, `react-dom`, `@inertiajs/react`, `lucide-react`, `radix-ui`, `class-variance-authority`, `clsx`, `tailwind-merge`
- **Build toolchain:** `vite`, `@vitejs/plugin-react`, `laravel-vite-plugin`, `tailwindcss`, `postcss`, `autoprefixer`, `tailwindcss-animate`

Excluded (not in core stubs / audit mismatch): `ziggy-js`, `@tailwindcss/vite`, domain libs (`@tanstack/react-table`, `recharts`, etc.)

Install options:

```bash
npm install <packages>   # manual
php artisan owl-admin:install --install-frontend-deps   # optional auto-install
```

## TODO (future presets)

- `@tailwindcss/vite` mismatch in landing audit — do not add until vite config aligned
- Domain npm: `@tanstack/react-table`, `recharts`, `react-hook-form`, `zod`
- Auth preset dependencies when v0.2+
