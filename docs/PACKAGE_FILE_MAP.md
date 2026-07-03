# Package File Map — v0.1 core-only

Package: `owlsolutions/custom-admin-kit` **0.1.0**

Only **`--preset=core`** is available. `full`, `auth`, and `frontend` are blocked.

**Canonical UI path:** `resources/js/Components/ui` (import: `@/Components/ui`)

| Stub | Install target | Mode | Notes |
|------|----------------|------|-------|
| `config/owl-admin.php` | `config/owl-admin.php` | copy | Branding via `OWL_ADMIN_BRAND`, `OWL_ADMIN_LOGO` |
| `routes/owl-admin-core.php` | `routes/owl-admin-core.php` | copy | GET `/owl-admin/health` |
| `resources/css/owl-admin.css` | `resources/css/owl-admin.css` | copy | Merge into host CSS |
| `resources/js/lib/utils.js` | `resources/js/lib/utils.js` | copy | cn() helper |
| `resources/js/Layouts/AdminLayout.jsx` | same | copy | Core shell, no domain nav |
| `resources/js/Pages/Dashboard.jsx` | same | copy | Placeholder |
| `resources/js/Pages/AppSettings/Index.jsx` | same | copy | Placeholder |
| `resources/js/Pages/Statistics/Logs.jsx` | same | copy | Placeholder |
| `resources/js/Pages/Settings/Index.jsx` | same | copy | Placeholder (no user CRUD) |
| `resources/js/Components/ui/*` (12) | same | copy | shadcn components |
| `public/images/*.svg` | same | copy | Branding assets |

## Excluded from v0.1 (landing domain / unsafe)

- Auth controllers, middleware, migrations, `routes/owl-admin-auth.php`
- Profile pages, Login.jsx, Breeze components
- `Settings/UserController`, domain Admin controllers
- Booking, customers, orders, jobs, services, staff, calendar
- Spatie migrations and packages
- `routes/web.php`, `package.json`, `vite.config.js`, `app.jsx`, `User.php`
- Hardcoded seeder `admin@admin.com/admin`
- `postcss.config.js`, `components.json`, `tailwind.config.js` (frontend merge)

## Merge required in host (not published)

| Host file | Reason |
|-----------|--------|
| `resources/js/app.jsx` | Inertia bootstrap |
| `package.json` | React/Tailwind deps |
| `vite.config.js` | Vite plugins |
| `routes/web.php` | Dashboard and domain routes |
| `app/Models/User.php` | Custom columns |
| `HandleInertiaRequests` | Share `owlAdmin.brand_name` from config |
