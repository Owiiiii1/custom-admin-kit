# Changelog

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

