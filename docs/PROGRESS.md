# Progress Log

## 2026-07-05 — v0.4.0 starter CRM modules

- Added admin preset starter CRM backend stubs:
  - models: `Customer`, `Order`, `Service`, `Staff`
  - controllers: `CustomersController`, `OrdersController`, `ServicesController`, `StaffController`, `CalendarController`
  - migrations: `customers`, `services`, `staff`, `orders`, `order_staff`
- Added admin preset CRM frontend pages:
  - `Customers/Index`
  - `Orders/Index`
  - `Services/Index`
  - `Staff/Index`
  - `Calendar/Index`
- Extended admin routes with `customers.*`, `orders.*`, `services.*`, `staff.*`, `calendar.index`
- Updated `AdminLayout` menu with CRM sections while keeping AI status badge.
- Extended smoke/doctor checks for CRM files, migrations, tables, and routes.
- Kept `core` preset compatibility unchanged (`23/23` expectations preserved).

## 2026-07-04 — v0.3.1 compatibility patch

- Restored backward compatibility for `WebRoutesMerger::apply()` old/new signatures
- Added compatibility shim for legacy `WebRoutesAnalysis::$hasInclude`
- Restored frontend setup output compatibility (`web.php include:`)
- Restored `core` preset published-files compatibility (`23/23`)
- Marked `v0.3.1` as recommended install version instead of `v0.3.0`

## 2026-07-04 — v0.3.0 admin preset implementation

- Added new preset `admin` while keeping `core` intact
- Added generic auth shell (`/login`, `/logout`) with custom login page
- Added generic profile page/controller/routes
- Added generic users management in settings page/controller/routes
- Extended publish map with admin-only stubs (controllers/requests/routes/pages/layouts)
- Extended frontend setup route merge to support both `owl-admin-pages.php` and `owl-admin-auth.php`
- Extended smoke checks for admin preset (auth/profile/users table/dashboard middleware)
- Updated make-admin behavior: explicit CLI credentials are accepted (warning for weak values)
- Updated docs and changelog for v0.3.0 scope
- Added AI Settings module in v0.3.0 (OpenAI, Anthropic, Gemini; one active provider/model)
- Added AI provider service layer stubs (Laravel HTTP client, no SDKs)
- Added encrypted `ai_provider_settings` migration/model stubs
- Added AI status badge in `AdminLayout` via shared Inertia props (`owlAdmin.ai`)

## Planned next (TODO)

- Advanced order workflow states and timeline interactions
- Optional richer calendar grid view
- Optional custom order status dictionaries
