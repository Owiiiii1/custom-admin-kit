# Compatibility Matrix — v0.1 core-only

Synced from landing audit manifest (2026-07-03).

## Supported stack

| Component | Supported | Detected in landing |
|-----------|-----------|---------------------|
| PHP | ^8.3 | 8.3.30 |
| Laravel | ^13.0 | 13.9.0 |
| Node | >= 20.19 recommended | v24.14.0 |
| npm | >= 10 | 11.9.0 |
| Admin transport | Inertia Laravel ^2.0 | Yes (host) |
| Frontend | React ^18.2 | Yes (host merge) |
| CSS | Tailwind 3 + PostCSS | Yes (host merge) |
| Build | Vite 8 | Yes (host merge) |
| Filament | **Not supported** | Not used |
| Livewire | **Not supported** | Not used |

## Package version matrix

| Package version | Laravel | Status |
|-----------------|---------|--------|
| 0.1.0 | 13.x | **Core-only** — safe installer, no domain modules |

## Host dependencies

### Recommended (doctor warns, install proceeds)

- `inertiajs/inertia-laravel` ^2.0
- `tightenco/ziggy` ^2.0

### Not required by package

- Spatie packages — excluded from v0.1
- `laravel/breeze`, `laravel/sanctum` — deferred to future presets

## Known mismatches / remaining blockers

| Item | Detail |
|------|--------|
| Frontend merge | `npm run build` fails until host merges `package.json`, `vite.config.js`, `app.jsx` |
| `@tailwindcss/vite` | Present in landing `package.json` but not in `vite.config.js` |
| Email verification | Disabled by default (`OWL_ADMIN_EMAIL_VERIFICATION=false`). Enable only with `MustVerifyEmail` on User |
| Duplicate UI paths | Package uses **`resources/js/Components/ui`** only; remove legacy lowercase dir in host |
| Domain routes | Core shell has no customers/orders/staff nav — host adds domain modules separately |

## Presets (v0.1)

| Preset | Status |
|--------|--------|
| `core` | **Only available preset** |
| `full`, `auth`, `frontend` | Blocked with exit code 1 |
