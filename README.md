# OwlSolutions Custom Admin Kit

Installable Laravel package that publishes a **core-only** Inertia + React admin shell and UI components.

**Version:** 0.1.1 (core-only) — **v0.2.0** adds `owl-admin:frontend-setup`

## Requirements

| Component | Version |
|-----------|---------|
| PHP | ^8.3 |
| Laravel | ^13.0 |
| Node (host frontend) | >= 20.19 recommended |
| Host packages (recommended) | `inertiajs/inertia-laravel`, `tightenco/ziggy` |

See [COMPATIBILITY.md](./COMPATIBILITY.md) and [docs/TODO_DEPENDENCIES.md](./docs/TODO_DEPENDENCIES.md).

## Commands

```bash
php artisan owl-admin:doctor [--preset=core]
php artisan owl-admin:install [--preset=core] [--dry-run] [--backup] [--force]
php artisan owl-admin:frontend-setup [--preset=core] [--dry-run] [--backup] [--install-npm] [--run-build]
php artisan owl-admin:make-admin
php artisan owl-admin:smoke [--preset=core]
php artisan owl-admin:repair [--preset=core] [--backup] [--force]
php artisan owl-admin:uninstall [--keep-files]
```

## Presets (v0.1)

| Preset | Status |
|--------|--------|
| `core` | **Available** — config, health route, admin shell, UI components |
| `full`, `auth`, `frontend` | **Blocked** — exit code 1 with guidance to use `--preset=core` |

Full file map: [docs/PACKAGE_FILE_MAP.md](./docs/PACKAGE_FILE_MAP.md)

## Install workflow

```bash
composer require owlsolutions/custom-admin-kit
php artisan owl-admin:doctor
php artisan owl-admin:install --preset=core --dry-run   # preview
php artisan owl-admin:install --preset=core --backup
php artisan owl-admin:frontend-setup --preset=core --dry-run
php artisan owl-admin:frontend-setup --preset=core --backup --install-npm --run-build
# merge host routes/web.php manually in v0.2
npm install && npm run build    # or use --install-npm --run-build above
php artisan owl-admin:smoke
```

**Important:** merge-only files are **never** published — see install report `merge_required`.

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
src/Commands/               # doctor, install, make-admin, smoke, repair
```
