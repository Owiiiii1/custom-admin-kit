# Changelog

## [Unreleased] — v0.2.0

- Add `owl-admin:frontend-setup` command for safe host frontend preparation
- Add frontend merge planners for `package.json`, Vite, Inertia app entry, and middleware
- Add backup support under `storage/app/owl-admin-kit/backups/`
- Install command now suggests `owl-admin:frontend-setup` as the next step

## 0.1.1 — 2026-07-03

- Fix smoke `published-files` count — verify publish map targets on disk (`23/23` format)
- Do not treat empty install-state `published_files` as success
- Improve vite manifest smoke check when `public/build/manifest.json` exists

## 0.1.0 — 2026-07-03 (core-only release)

### Safe core-only scope

- Reduced publish map to **23 core stubs** (config, health route, admin shell, UI, assets)
- Default preset: **`core`**; `full`, `auth`, `frontend` blocked with exit code 1
- Doctor/install print: *Core preset does not install landing domain modules.*
- Excluded: booking, customers, jobs, orders, services, staff, auth, migrations, Spatie, seeder
- Canonical UI: `resources/js/Components/ui` (`@/Components/ui`)
- Branding via `config/owl-admin-kit.php` / `OWL_ADMIN_BRAND` — no hardcoded OwlSolutions in stubs
- No publish of: `web.php`, `User.php`, `package.json`, `vite.config.js`, `app.jsx`

### Commands

- `InstallCommand`, `DoctorCommand`, `SmokeCommand`, `RepairCommand` — core preset only
- `SmokeTester` — health route + canonical UI path checks
- Install report lists `merge_required` host files

## 0.2.0 — 2026-07-03 (superseded)

Audit sync with full landing stubs — **replaced by 0.1.0 core-only** for safe installs.

## 0.1.0 — 2026-07-03 (initial skeleton)

- Initial skeleton: commands, support classes, minimal stubs, CI

