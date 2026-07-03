# Changelog

## [Unreleased]

## 0.2.2 — 2026-07-03

### Fixed

- Smoke output section grouping for frontend setup checks (`Core` vs `Frontend setup`)

## 0.2.0 — 2026-07-03

### Added

- `owl-admin:frontend-setup` command for safe host frontend preparation after core install
- Safe `package.json` merge — adds only missing npm dependencies/devDependencies; never overwrites versions
- Safe `vite.config.js` merge — standard Laravel configs get missing Vite inputs and `@vitejs/plugin-react` when needed
- Safe `resources/js/app.jsx` creation from package snippet when host uses `app.js` or has no Inertia entry
- Safe `HandleInertiaRequests` merge — adds shared `owlAdmin` props for `AdminLayout.jsx`
- `routes/owl-admin-pages.php` setup — core admin page routes from package stub
- Safe `routes/web.php` include merge — adds `require __DIR__.'/owl-admin-pages.php';` on standard host files
- Laravel 13 / Tailwind v4 compatible `owl-admin.css` stub (`@theme inline`, no `@tailwind` directives)
- `--install-npm` and `--run-build` integration on `owl-admin:frontend-setup`
- Frontend setup backups under `storage/app/owl-admin-kit/backups/YYYY-MM-DD-HH-mm-ss/`
- Manual merge snippets: `docs/merge-snippets/` for non-standard host files

### Fixed

- Vite React plugin and `resources/js/app.jsx` input setup when host ships Laravel 13 with `app.js` only
- `owl-admin.css` Tailwind v4 build errors (`border-border` and related shadcn tokens)
- `app.css` merge adds `@plugin "tailwindcss-animate"` for Laravel 13 `@tailwindcss/vite` hosts
- Smoke test verifies `public/build/manifest.json` when present (`vite-manifest` check)

### Integration

- End-to-end tested on clean Laravel 13.8: install → frontend-setup → `npm run build` → smoke → core routes registered

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

## 0.1.0 — 2026-07-03 (initial skeleton)

- Initial skeleton: commands, support classes, minimal stubs, CI
