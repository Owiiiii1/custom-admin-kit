# OwlSolutions Custom Admin Kit

Installable Laravel package for a generic Inertia + React admin shell.

**Version:** 0.3.1 — `core` preset and `admin` preset (core + auth/admin shell + AI Settings)

## Requirements

| Component | Version |
|-----------|---------|
| PHP | ^8.3 |
| Laravel | ^13.0 |
| Node (host frontend) | >= 20.19 (20 / 22 / 24 supported; warn if outside preferred range) |
| Host packages (recommended) | `inertiajs/inertia-laravel`, `tightenco/ziggy` |

See [COMPATIBILITY.md](./COMPATIBILITY.md) and [docs/TODO_DEPENDENCIES.md](./docs/TODO_DEPENDENCIES.md).

## Commands

```bash
php artisan owl-admin:doctor [--preset=core|admin]
php artisan owl-admin:install [--preset=core|admin] [--dry-run] [--backup] [--force] [--migrate]
php artisan owl-admin:frontend-setup [--preset=core|admin] [--dry-run] [--backup] [--force] [--install-npm] [--run-build] [--strict]
php artisan owl-admin:make-admin
php artisan owl-admin:smoke [--preset=core|admin]
php artisan owl-admin:repair [--preset=core] [--backup] [--force]
php artisan owl-admin:uninstall [--keep-files]
```

## Presets

| Preset | Status |
|--------|--------|
| `core` | **Available** — lightweight skeleton (dashboard/settings/app-settings/statistics + UI) |
| `admin` | **Available** — core + login/logout + profile + users-in-settings + AI Settings + full generic AdminLayout |
| `full` | **Blocked** — use `--preset=admin` |

Full file map: [docs/PACKAGE_FILE_MAP.md](./docs/PACKAGE_FILE_MAP.md)

## Recommended install flow (v0.3.1)

```bash
composer config repositories.custom-admin-kit vcs git@github.com:Owiiiii1/custom-admin-kit.git
composer require owlsolutions/custom-admin-kit:v0.3.1

composer require inertiajs/inertia-laravel tightenco/ziggy
php artisan inertia:middleware

php artisan owl-admin:doctor --preset=admin
php artisan owl-admin:install --preset=admin --backup --migrate --no-smoke
php artisan owl-admin:frontend-setup --preset=admin --backup --install-npm --run-build
php artisan owl-admin:make-admin --email=admin@admin.com --password=admin
php artisan owl-admin:smoke --preset=admin
```

## Version overview

| Version | Scope |
|---------|--------|
| **0.1.x** | Core stubs only (`owl-admin:install`) — manual frontend/route merge |
| **0.2.0** | Core stubs + safe `owl-admin:frontend-setup` for npm, Vite, Inertia, middleware, routes |
| **0.3.0** | New `admin` preset (core + auth/login/logout + profile + users in settings + AI Settings) |
| **0.3.1** | Patch compatibility fix after `0.3.0`: test/backward-compat restoration, core preset remains `23/23` |

Recommended for new installs: **`0.3.1`**.

## AI Settings

- Providers: `OpenAI`, `Anthropic`, `Gemini`
- Exactly one active provider/model at a time
- API keys are stored in DB via encrypted cast (`api_key => encrypted`)
- Connection check loads available models through Laravel HTTP client
- `AdminLayout` header shows AI status badge from shared Inertia props (`owlAdmin.ai`)
- No provider SDK dependencies required

## Documentation

- [INSTALL.md](./INSTALL.md)
- [COMPATIBILITY.md](./COMPATIBILITY.md)
- [TROUBLESHOOTING.md](./TROUBLESHOOTING.md)
- [CHANGELOG.md](./CHANGELOG.md)
- [docs/REQUIRED_ENV_KEYS.md](./docs/REQUIRED_ENV_KEYS.md)
- [docs/TODO_DEPENDENCIES.md](./docs/TODO_DEPENDENCIES.md)

## Package layout

```
config/owl-admin-kit.php    # supported matrix, presets, branding, publish map
config/publish-map.php      # core + admin copy entries
stubs/                      # core and admin preset stubs
src/Commands/               # doctor, install, frontend-setup, make-admin, smoke, repair
docs/merge-snippets/        # manual merge templates for non-standard host files
```
