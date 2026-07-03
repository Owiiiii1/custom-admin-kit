# Required Environment Keys

Environment variables needed by the admin kit (`owlsolutions/custom-admin-kit`) when installed into a Laravel 13 host.

**Rule:** the installer must **never write to `.env`**. Document keys, validate in `owl-admin:doctor`, and show a checklist after install.

Sources: `.env.example`, `config/*.php`, `app/Http/Middleware/SetLocale.php`, `app/Http/Controllers/ProfileController.php`, `resources/views/app.blade.php`, `config/sanctum.php`, `config/filesystems.php`.

Legend: **required** = admin/auth cannot work without it · **optional** = feature degrades gracefully · **host** = standard Laravel; host usually already has it

---

## Application core

| Key | Required | Default (`.env.example`) | Description | Where used |
|-----|----------|--------------------------|-------------|------------|
| `APP_NAME` | required | `Laravel` | Application name in HTML title and mail from-name | `resources/views/app.blade.php` (`config('app.name')`), `MAIL_FROM_NAME` |
| `APP_ENV` | host | `local` | Environment mode | Laravel bootstrap |
| `APP_KEY` | required | *(empty)* | Encryption key for sessions/cookies | Laravel bootstrap, session encryption |
| `APP_DEBUG` | host | `true` | Debug mode | Laravel bootstrap |
| `APP_URL` | required | `http://localhost` | Base URL for assets, public disk URLs, Ziggy | `config/filesystems.php` (`public` disk URL), `@routes` / asset helpers |
| `APP_LOCALE` | required | `en` | Default locale for admin UI | `config/app.php` → `SetLocale` middleware |
| `APP_FALLBACK_LOCALE` | optional | `en` | Fallback when translation missing | `config/app.php` |
| `APP_FAKER_LOCALE` | optional | `en_US` | Faker locale (dev/seeding only) | `config/app.php` |
| `APP_MAINTENANCE_DRIVER` | optional | `file` | Maintenance mode driver | `config/app.php` |

---

## Database

| Key | Required | Default | Description | Where used |
|-----|----------|---------|-------------|------------|
| `DB_CONNECTION` | required | `sqlite` | Default DB connection | All Eloquent, migrations, auth |
| `DB_HOST` | optional* | `127.0.0.1` | DB host (*required for mysql/pgsql) | `config/database.php` |
| `DB_PORT` | optional* | `3306` / `5432` | DB port | `config/database.php` |
| `DB_DATABASE` | required* | `laravel` / sqlite path | Database name or sqlite file | `config/database.php` |
| `DB_USERNAME` | optional* | `root` | DB user | `config/database.php` |
| `DB_PASSWORD` | optional* | *(empty)* | DB password | `config/database.php` |
| `DB_URL` | optional | — | Single URL override | `config/database.php` |
| `DB_FOREIGN_KEYS` | optional | `true` | SQLite FK enforcement | `config/database.php` |

Admin kit migrations (user columns, sessions if `SESSION_DRIVER=database`) require a working database connection.

---

## Session & authentication

| Key | Required | Default | Description | Where used |
|-----|----------|---------|-------------|------------|
| `SESSION_DRIVER` | required | `database` | Session storage driver | Login persistence, `SetLocale` session locale |
| `SESSION_LIFETIME` | optional | `120` | Session idle timeout (minutes) | `config/session.php` |
| `SESSION_ENCRYPT` | optional | `false` | Encrypt session payload | `config/session.php` |
| `SESSION_PATH` | optional | `/` | Session cookie path | `config/session.php` |
| `SESSION_DOMAIN` | optional | `null` | Session cookie domain | `config/session.php` |
| `SESSION_SECURE_COOKIE` | optional | env-dependent | HTTPS-only cookies | `config/session.php` |
| `SESSION_SAME_SITE` | optional | `lax` | SameSite cookie policy | `config/session.php` |
| `BCRYPT_ROUNDS` | optional | `12` | Password hashing cost | `config/hashing.php` |

If `SESSION_DRIVER=database`, the `sessions` table migration must exist (Laravel default).

---

## Filesystem & avatars

| Key | Required | Default | Description | Where used |
|-----|----------|---------|-------------|------------|
| `FILESYSTEM_DISK` | required | `local` | Default disk; profile avatars use `public` explicitly | `ProfileController` → `Storage::disk('public')` for avatar upload/delete |

**Post-install step (not env):** run `php artisan storage:link` so `public/storage` serves uploaded avatars.

| Key | Required | Default | Description | Where used |
|-----|----------|---------|-------------|------------|
| `AWS_ACCESS_KEY_ID` | optional | — | S3 disk key | `config/filesystems.php` — only if using S3 for storage |
| `AWS_SECRET_ACCESS_KEY` | optional | — | S3 secret | same |
| `AWS_DEFAULT_REGION` | optional | `us-east-1` | S3 region | same |
| `AWS_BUCKET` | optional | — | S3 bucket | same |
| `AWS_URL` | optional | — | Custom S3 URL | same |
| `AWS_ENDPOINT` | optional | — | S3-compatible endpoint | same |
| `AWS_USE_PATH_STYLE_ENDPOINT` | optional | `false` | Path-style S3 URLs | same |

---

## Mail (password reset & email verification)

| Key | Required | Default | Description | Where used |
|-----|----------|---------|-------------|------------|
| `MAIL_MAILER` | optional* | `log` | Mail driver (*required for real reset/verify emails) | Auth password reset, email verification controllers |
| `MAIL_HOST` | optional | `127.0.0.1` | SMTP host | `config/mail.php` |
| `MAIL_PORT` | optional | `2525` | SMTP port | `config/mail.php` |
| `MAIL_USERNAME` | optional | `null` | SMTP user | `config/mail.php` |
| `MAIL_PASSWORD` | optional | `null` | SMTP password | `config/mail.php` |
| `MAIL_ENCRYPTION` | optional | — | TLS/SSL | `config/mail.php` |
| `MAIL_FROM_ADDRESS` | optional* | `hello@example.com` | From address | Password reset / verify emails |
| `MAIL_FROM_NAME` | optional | `${APP_NAME}` | From name | same |

With `MAIL_MAILER=log`, flows work in dev but no real email is sent.

---

## Frontend build (Vite)

| Key | Required | Default | Description | Where used |
|-----|----------|---------|-------------|------------|
| `VITE_APP_NAME` | optional | `${APP_NAME}` | Exposed to frontend via Vite | `package.json` / Vite env (if referenced in JS) |

Admin pages load via `@vite(['resources/js/app.jsx', ...])` in `app.blade.php` — production requires `npm run build` and `public/build` manifest (no extra env key beyond `APP_URL` for asset URLs).

---

## Sanctum (optional API preset)

| Key | Required | Default | Description | Where used |
|-----|----------|---------|-------------|------------|
| `SANCTUM_STATEFUL_DOMAINS` | optional | derived from `APP_URL` | Domains receiving stateful API auth cookies | `config/sanctum.php` — `/api/user` route |
| `SANCTUM_TOKEN_PREFIX` | optional | *(empty)* | Token prefix | `config/sanctum.php` |

Only needed if host enables Sanctum SPA authentication alongside the admin session.

---

## Cache & queue (host defaults)

| Key | Required | Default | Description | Where used |
|-----|----------|---------|-------------|------------|
| `CACHE_STORE` | host | `database` | Cache driver | Laravel; not admin-specific |
| `QUEUE_CONNECTION` | host | `database` | Queue driver | Laravel; admin UI does not require queues |
| `REDIS_HOST` | optional | `127.0.0.1` | Redis host | If session/cache/queue use redis |

---

## Optional Spatie packages (full preset)

These apply only if installer detects/publishes Spatie packages. Not wired in current app PHP code, but migrations/config exist in landing.

| Key | Required | Default | Description | Where used |
|-----|----------|---------|-------------|------------|
| `ACTIVITY_LOGGER_ENABLED` | optional | `true` | Enable activity log | `config/activitylog.php` |
| `ACTIVITY_LOGGER_TABLE_NAME` | optional | `activity_log` | Activity table name | `config/activitylog.php` |
| `ACTIVITY_LOGGER_DB_CONNECTION` | optional | — | Separate DB connection | `config/activitylog.php` |
| `SETTINGS_CACHE_ENABLED` | optional | `false` | Cache settings | `config/settings.php` |
| `SETTINGS_CACHE_MEMO` | optional | `false` | Memoize settings | `config/settings.php` |

Spatie Permission and Media Library use config files without additional `.env` keys in this project.

---

## Keys the installer must NOT set

| Key | Reason |
|-----|--------|
| Any secret in `.env` | Host-owned; installer reads only |
| `APP_KEY` | Must be generated by host (`php artisan key:generate`) |
| Hardcoded admin credentials | Never `admin@admin.com` / password `admin` — use env or `owl-admin:make-admin` |

---

## Admin kit env keys (package installer)

| Key | Required | When | Description |
|-----|----------|------|-------------|
| `OWL_ADMIN_NAME` | optional | `--seed` / `make-admin` | Display name (default: `Admin`) |
| `OWL_ADMIN_EMAIL` | optional* | `--seed` / `make-admin` | Admin login email (*required for non-interactive `--seed`) |
| `OWL_ADMIN_PASSWORD` | optional* | `--seed` / `make-admin` | Admin password (*required for non-interactive `--seed` unless local auto-generate) |
| `OWL_ADMIN_ALLOW_DEFAULT_PASSWORD` | optional | local dev only | When `true` and `APP_ENV=local`, install may generate a random password if `OWL_ADMIN_PASSWORD` is empty |
| `OWL_ADMIN_EMAIL_VERIFICATION` | optional | default `false` | When `true`, admin routes use `verified` middleware; User must implement `MustVerifyEmail` |
| `OWL_ADMIN_BRAND` | optional | branding | Inertia `owlAdmin.brand_name` |
| `OWL_ADMIN_LOGO` | optional | branding | Logo path for admin shell |

**Non-interactive `--seed`:** both `OWL_ADMIN_EMAIL` and `OWL_ADMIN_PASSWORD` must be set (or local auto-generate allowed for password only).

**Forbidden:** `admin@admin.com`, password `admin` — rejected by installer and `owl-admin:make-admin`.

---

## Doctor validation checklist

`owl-admin:doctor` should verify:

1. `APP_KEY` is non-empty
2. `APP_URL` is set and matches deployment URL (warning if `localhost` in production)
3. `DB_*` connection succeeds
4. `SESSION_DRIVER` compatible with existing migrations
5. `public/storage` symlink exists if avatar upload enabled
6. `MAIL_*` configured (warning only) if email verification or password reset enabled
7. `npm run build` output exists at `public/build/manifest.json` (after frontend preset)

---

## Minimum `.env` for local admin smoke test

```env
APP_NAME="Service Admin"
APP_KEY=base64:...          # php artisan key:generate
APP_URL=http://localhost
APP_LOCALE=en

DB_CONNECTION=sqlite        # or mysql credentials

SESSION_DRIVER=database     # or file

FILESYSTEM_DISK=local

MAIL_MAILER=log
```

After install: `php artisan migrate`, `php artisan storage:link`, `php artisan owl-admin:make-admin`, `npm install && npm run build`.

Or with env credentials:

```env
OWL_ADMIN_EMAIL=you@example.com
OWL_ADMIN_PASSWORD=your-secure-password
```

```bash
php artisan owl-admin:install --preset=core --seed --backup
```
