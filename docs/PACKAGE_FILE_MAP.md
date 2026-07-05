# Package File Map — v0.4 (`core` + `admin`)

Package: `owlsolutions/custom-admin-kit` **0.4.0**

Available presets:

- `core` — lightweight skeleton
- `admin` — core + auth/profile/users/settings + AI settings + starter CRM

**Canonical UI path:** `resources/js/Components/ui` (import: `@/Components/ui`)

| Stub | Install target | Mode | Notes |
|------|----------------|------|-------|
| `config/owl-admin.php` | `config/owl-admin.php` | copy | Branding via `OWL_ADMIN_BRAND`, `OWL_ADMIN_LOGO` |
| `routes/owl-admin-core.php` | `routes/owl-admin-core.php` | copy | GET `/owl-admin/health` |
| `resources/css/owl-admin.css` | `resources/css/owl-admin.css` | copy | Merge into host CSS |
| `resources/js/lib/utils.js` | `resources/js/lib/utils.js` | copy | cn() helper |
| `resources/js/Layouts/AdminLayout.jsx` | same | copy | Core shell (core preset) / full generic shell (admin preset override) |
| `resources/js/Pages/Dashboard.jsx` | same | copy | Placeholder |
| `resources/js/Pages/AppSettings/Index.jsx` | same | copy | Placeholder |
| `resources/js/Pages/Statistics/Logs.jsx` | same | copy | Placeholder |
| `resources/js/Pages/Settings/Index.jsx` | same | copy | Placeholder in core, users management in admin preset override |
| `routes/owl-admin-auth.php` | `routes/owl-admin-auth.php` | copy | Admin preset: login/logout routes |
| `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | same | copy | Admin preset auth controller |
| `app/Http/Requests/Auth/LoginRequest.php` | same | copy | Admin preset login request |
| `app/Http/Controllers/ProfileController.php` | same | copy | Admin preset profile controller |
| `app/Http/Controllers/Settings/SettingsController.php` | same | copy | Admin preset settings controller |
| `app/Http/Controllers/Settings/UserController.php` | same | copy | Admin preset users CRUD controller |
| `app/Http/Controllers/Settings/AiSettingsController.php` | same | copy | Admin preset AI settings controller |
| `app/Models/AiProviderSetting.php` | same | copy | Admin preset AI provider model |
| `app/Services/Ai/*` | same | copy | Admin preset AI provider service layer |
| `database/migrations/*create_ai_provider_settings_table*.php` | same | copy | Admin preset AI settings migration |
| `resources/js/Layouts/AuthLayout.jsx` | same | copy | Admin preset auth wrapper |
| `resources/js/Pages/Auth/Login.jsx` | same | copy | Admin preset login page |
| `resources/js/Pages/Profile/Edit.jsx` | same | copy | Admin preset profile page |
| `resources/js/Pages/AiSettings/Index.jsx` | same | copy | Admin preset AI settings page |
| `app/Models/Customer.php` | same | copy | Admin preset CRM customer model |
| `app/Models/Order.php` | same | copy | Admin preset CRM order model |
| `app/Models/Service.php` | same | copy | Admin preset CRM service model |
| `app/Models/Staff.php` | same | copy | Admin preset CRM staff model |
| `app/Http/Controllers/CustomersController.php` | same | copy | Admin preset CRM customers controller |
| `app/Http/Controllers/OrdersController.php` | same | copy | Admin preset CRM orders controller |
| `app/Http/Controllers/ServicesController.php` | same | copy | Admin preset CRM services controller |
| `app/Http/Controllers/StaffController.php` | same | copy | Admin preset CRM staff controller |
| `app/Http/Controllers/CalendarController.php` | same | copy | Admin preset CRM calendar controller |
| `database/migrations/*create_customers_table*.php` | same | copy | Admin preset CRM customers migration |
| `database/migrations/*create_services_table*.php` | same | copy | Admin preset CRM services migration |
| `database/migrations/*create_staff_table*.php` | same | copy | Admin preset CRM staff migration |
| `database/migrations/*create_orders_table*.php` | same | copy | Admin preset CRM orders migration |
| `database/migrations/*create_order_staff_table*.php` | same | copy | Admin preset CRM order_staff migration |
| `resources/js/Pages/Customers/Index.jsx` | same | copy | Admin preset CRM customers page |
| `resources/js/Pages/Orders/Index.jsx` | same | copy | Admin preset CRM orders page |
| `resources/js/Pages/Services/Index.jsx` | same | copy | Admin preset CRM services page |
| `resources/js/Pages/Staff/Index.jsx` | same | copy | Admin preset CRM staff page |
| `resources/js/Pages/Calendar/Index.jsx` | same | copy | Admin preset CRM calendar page |
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
