# OwlSolutions Custom Admin Kit

Installable Laravel package that publishes a **core-only** Inertia + React admin shell and UI components, with optional safe frontend setup (v0.2).

**Version:** 0.2.0 — core stubs + `owl-admin:frontend-setup`

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
php artisan owl-admin:doctor [--preset=core]
php artisan owl-admin:install [--preset=core] [--dry-run] [--backup] [--force] [--migrate]
php artisan owl-admin:frontend-setup [--preset=core] [--dry-run] [--backup] [--force] [--install-npm] [--run-build] [--strict]
php artisan owl-admin:make-admin
php artisan owl-admin:smoke [--preset=core]
php artisan owl-admin:repair [--preset=core] [--backup] [--force]
php artisan owl-admin:uninstall [--keep-files]
```

## Presets

| Preset | Status |
|--------|--------|
| `core` | **Available** — config, health route, admin shell, UI components |
| `full`, `auth`, `frontend` | **Blocked** — exit code 1 with guidance to use `--preset=core` |

Full file map: [docs/PACKAGE_FILE_MAP.md](./docs/PACKAGE_FILE_MAP.md)

## Recommended install flow (v0.2.0)

```bash
composer config repositories.custom-admin-kit vcs git@github.com:Owiiiii1/custom-admin-kit.git
composer require owlsolutions/custom-admin-kit:v0.2.0

composer require inertiajs/inertia-laravel tightenco/ziggy
php artisan inertia:middleware

php artisan owl-admin:doctor --preset=core
php artisan owl-admin:install --preset=core --backup --migrate --no-smoke
php artisan owl-admin:frontend-setup --preset=core --backup --install-npm --run-build
php artisan owl-admin:smoke --preset=core
```

Create an admin user separately:

```bash
php artisan owl-admin:make-admin
```

Host auth/login routes and Blade `@vite` / `@inertia` wiring are still the host app's responsibility — see [INSTALL.md](./INSTALL.md) and [TROUBLESHOOTING.md](./TROUBLESHOOTING.md).

## Version overview

| Version | Scope |
|---------|--------|
| **0.1.x** | Core stubs only (`owl-admin:install`) — manual frontend/route merge |
| **0.2.0** | Core stubs + safe `owl-admin:frontend-setup` for npm, Vite, Inertia, middleware, routes |

## Documentation

- [INSTALL.md](./INSTALL.md)
- [COMPATIBILITY.md](./COMPATIBILITY.md)
- [TROUBLESHOOTING.md](./TROUBLESHOOTING.md)
- [CHANGELOG.md](./CHANGELOG.md)
- [docs/REQUIRED_ENV_KEYS.md](./docs/REQUIRED_ENV_KEYS.md)
- [docs/TODO_DEPENDENCIES.md](./docs/TODO_DEPENDENCIES.md)

## Package layout

```
config/owl-admin-kit.php    # supported matrix, core preset, branding, publish map
config/publish-map.php      # 23 core-only copy entries
stubs/                      # safe core stubs only
src/Commands/               # doctor, install, frontend-setup, make-admin, smoke, repair
docs/merge-snippets/        # manual merge templates for non-standard host files
```
