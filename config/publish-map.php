<?php

$uiComponents = [
    'avatar.jsx', 'badge.jsx', 'button.jsx', 'card.jsx', 'dialog.jsx',
    'dropdown-menu.jsx', 'input.jsx', 'label.jsx', 'separator.jsx',
    'sheet.jsx', 'table.jsx', 'tooltip.jsx',
];

$map = [];

$add = static function (
    array &$map,
    string $source,
    string $stub,
    string $target,
    string $preset,
    string $conflict = 'low',
    string $notes = ''
): void {
    $map[] = [
        'source' => $source,
        'stub' => $stub,
        'target' => $target,
        'mode' => 'copy',
        'preset' => $preset,
        'conflict' => $conflict,
        'notes' => $notes,
    ];
};

foreach (['core', 'admin'] as $preset) {
    $add(
        $map,
        'config/owl-admin.php',
        'config/owl-admin.php',
        'config/owl-admin.php',
        $preset,
        'low',
        'Host branding and route names.',
    );
    $add(
        $map,
        'routes/owl-admin-core.php',
        'routes/owl-admin-core.php',
        'routes/owl-admin-core.php',
        $preset,
        'low',
        'Kit health route GET /owl-admin/health.',
    );
    $add(
        $map,
        'resources/css/owl-admin.css',
        'resources/css/owl-admin.css',
        'resources/css/owl-admin.css',
        $preset,
        'medium',
        'Import in host app.css or merge manually.',
    );
    $add(
        $map,
        'resources/js/lib/utils.js',
        'resources/js/lib/utils.js',
        'resources/js/lib/utils.js',
        $preset,
        'low',
        'shadcn cn() helper.',
    );
    $add(
        $map,
        'resources/js/Pages/Dashboard.jsx',
        'resources/js/Pages/Dashboard.jsx',
        'resources/js/Pages/Dashboard.jsx',
        $preset,
        'low',
        'Dashboard page.',
    );
    $add(
        $map,
        'resources/js/Pages/AppSettings/Index.jsx',
        'resources/js/Pages/AppSettings/Index.jsx',
        'resources/js/Pages/AppSettings/Index.jsx',
        $preset,
        'low',
        'Generic app settings page.',
    );
    $add(
        $map,
        'resources/js/Pages/Statistics/Logs.jsx',
        'resources/js/Pages/Statistics/Logs.jsx',
        'resources/js/Pages/Statistics/Logs.jsx',
        $preset,
        'low',
        'Generic statistics/logs page.',
    );
    $add(
        $map,
        'public/images/company-logo.svg',
        'public/images/company-logo.svg',
        'public/images/company-logo.svg',
        $preset,
        'low',
        'Replace via config owl-admin.logo_path.',
    );
    foreach ($uiComponents as $file) {
        $add(
            $map,
            "resources/js/Components/ui/{$file}",
            "resources/js/Components/ui/{$file}",
            "resources/js/Components/ui/{$file}",
            $preset,
            'low',
            'Canonical UI path resources/js/Components/ui.',
        );
    }
}

// Core preset keeps lightweight placeholders only.
$add(
    $map,
    'routes/owl-admin-pages.php',
    'routes/owl-admin-pages.php',
    'routes/owl-admin-pages.php',
    'core',
    'low',
    'Core shell pages routes only.',
);
$add(
    $map,
    'resources/js/Layouts/AdminLayout.jsx',
    'resources/js/Layouts/AdminLayout.jsx',
    'resources/js/Layouts/AdminLayout.jsx',
    'core',
    'medium',
    'Core shell layout only.',
);
$add(
    $map,
    'resources/js/Pages/Settings/Index.jsx',
    'resources/js/Pages/Settings/Index.jsx',
    'resources/js/Pages/Settings/Index.jsx',
    'core',
    'low',
    'Core placeholder settings page.',
);

// Admin preset extends core with full generic auth/admin shell.
$add(
    $map,
    'resources/views/app.blade.php',
    'resources/views/app.blade.php',
    'resources/views/app.blade.php',
    'admin',
    'medium',
    'Inertia root Blade view.',
);
$add(
    $map,
    'public/images/auth-abstract-bg.svg',
    'public/images/auth-abstract-bg.svg',
    'public/images/auth-abstract-bg.svg',
    'admin',
    'low',
    'Auth page branding asset.',
);
$add(
    $map,
    'routes/owl-admin-pages.php',
    'presets/admin/routes/owl-admin-pages.php',
    'routes/owl-admin-pages.php',
    'admin',
    'medium',
    'Admin pages routes with profile and user management.',
);
$add(
    $map,
    'routes/owl-admin-auth.php',
    'presets/admin/routes/owl-admin-auth.php',
    'routes/owl-admin-auth.php',
    'admin',
    'medium',
    'Login/logout routes.',
);
$add(
    $map,
    'app/Http/Controllers/Auth/AuthenticatedSessionController.php',
    'presets/admin/app/Http/Controllers/Auth/AuthenticatedSessionController.php',
    'app/Http/Controllers/Auth/AuthenticatedSessionController.php',
    'admin',
    'medium',
    'Auth login/logout controller.',
);
$add(
    $map,
    'app/Http/Requests/Auth/LoginRequest.php',
    'presets/admin/app/Http/Requests/Auth/LoginRequest.php',
    'app/Http/Requests/Auth/LoginRequest.php',
    'admin',
    'medium',
    'Auth request validation and throttling.',
);
$add(
    $map,
    'app/Http/Controllers/ProfileController.php',
    'presets/admin/app/Http/Controllers/ProfileController.php',
    'app/Http/Controllers/ProfileController.php',
    'admin',
    'medium',
    'Generic profile management.',
);
$add(
    $map,
    'app/Http/Controllers/Settings/SettingsController.php',
    'presets/admin/app/Http/Controllers/Settings/SettingsController.php',
    'app/Http/Controllers/Settings/SettingsController.php',
    'admin',
    'medium',
    'Settings screen and locale switch.',
);
$add(
    $map,
    'app/Http/Controllers/Settings/UserController.php',
    'presets/admin/app/Http/Controllers/Settings/UserController.php',
    'app/Http/Controllers/Settings/UserController.php',
    'admin',
    'medium',
    'Generic users CRUD for default users table.',
);
$add(
    $map,
    'app/Http/Controllers/Settings/AiSettingsController.php',
    'presets/admin/app/Http/Controllers/Settings/AiSettingsController.php',
    'app/Http/Controllers/Settings/AiSettingsController.php',
    'admin',
    'medium',
    'AI settings controller.',
);
$add(
    $map,
    'app/Models/AiProviderSetting.php',
    'presets/admin/app/Models/AiProviderSetting.php',
    'app/Models/AiProviderSetting.php',
    'admin',
    'medium',
    'AI provider settings model.',
);
$add(
    $map,
    'app/Services/Ai/AiProviderManager.php',
    'presets/admin/app/Services/Ai/AiProviderManager.php',
    'app/Services/Ai/AiProviderManager.php',
    'admin',
    'medium',
    'AI provider manager service.',
);
$add(
    $map,
    'app/Services/Ai/Contracts/AiProviderClient.php',
    'presets/admin/app/Services/Ai/Contracts/AiProviderClient.php',
    'app/Services/Ai/Contracts/AiProviderClient.php',
    'admin',
    'medium',
    'AI provider client contract.',
);
$add(
    $map,
    'app/Services/Ai/Clients/OpenAiClient.php',
    'presets/admin/app/Services/Ai/Clients/OpenAiClient.php',
    'app/Services/Ai/Clients/OpenAiClient.php',
    'admin',
    'medium',
    'OpenAI models API client.',
);
$add(
    $map,
    'app/Services/Ai/Clients/AnthropicClient.php',
    'presets/admin/app/Services/Ai/Clients/AnthropicClient.php',
    'app/Services/Ai/Clients/AnthropicClient.php',
    'admin',
    'medium',
    'Anthropic models API client.',
);
$add(
    $map,
    'app/Services/Ai/Clients/GeminiClient.php',
    'presets/admin/app/Services/Ai/Clients/GeminiClient.php',
    'app/Services/Ai/Clients/GeminiClient.php',
    'admin',
    'medium',
    'Gemini models API client.',
);
$add(
    $map,
    'database/migrations/2026_07_04_170000_create_ai_provider_settings_table.php',
    'presets/admin/database/migrations/2026_07_04_170000_create_ai_provider_settings_table.php',
    'database/migrations/2026_07_04_170000_create_ai_provider_settings_table.php',
    'admin',
    'medium',
    'AI provider settings table migration.',
);
$add(
    $map,
    'resources/js/Layouts/AdminLayout.jsx',
    'presets/admin/resources/js/Layouts/AdminLayout.jsx',
    'resources/js/Layouts/AdminLayout.jsx',
    'admin',
    'medium',
    'Full generic admin layout.',
);
$add(
    $map,
    'resources/js/Layouts/AuthLayout.jsx',
    'presets/admin/resources/js/Layouts/AuthLayout.jsx',
    'resources/js/Layouts/AuthLayout.jsx',
    'admin',
    'low',
    'Auth page wrapper.',
);
$add(
    $map,
    'resources/js/Pages/Auth/Login.jsx',
    'presets/admin/resources/js/Pages/Auth/Login.jsx',
    'resources/js/Pages/Auth/Login.jsx',
    'admin',
    'low',
    'Custom branded login page.',
);
$add(
    $map,
    'resources/js/Pages/Settings/Index.jsx',
    'presets/admin/resources/js/Pages/Settings/Index.jsx',
    'resources/js/Pages/Settings/Index.jsx',
    'admin',
    'low',
    'Settings hub with tabs.',
);
$add(
    $map,
    'resources/js/Pages/Settings/GeneralPanel.jsx',
    'presets/admin/resources/js/Pages/Settings/GeneralPanel.jsx',
    'resources/js/Pages/Settings/GeneralPanel.jsx',
    'admin',
    'low',
    'Settings general tab panel.',
);
$add(
    $map,
    'resources/js/Pages/Settings/UsersPanel.jsx',
    'presets/admin/resources/js/Pages/Settings/UsersPanel.jsx',
    'resources/js/Pages/Settings/UsersPanel.jsx',
    'admin',
    'low',
    'Settings users tab panel.',
);
$add(
    $map,
    'resources/js/Pages/Settings/AiPanel.jsx',
    'presets/admin/resources/js/Pages/Settings/AiPanel.jsx',
    'resources/js/Pages/Settings/AiPanel.jsx',
    'admin',
    'low',
    'Settings AI tab panel (router.post key flow).',
);
$add(
    $map,
    'resources/js/Pages/Settings/AppPanel.jsx',
    'presets/admin/resources/js/Pages/Settings/AppPanel.jsx',
    'resources/js/Pages/Settings/AppPanel.jsx',
    'admin',
    'low',
    'Settings app tab panel.',
);
$add(
    $map,
    'resources/js/Pages/Settings/TelegramPanel.jsx',
    'presets/admin/resources/js/Pages/Settings/TelegramPanel.jsx',
    'resources/js/Pages/Settings/TelegramPanel.jsx',
    'admin',
    'low',
    'Settings Telegram tab panel.',
);
$add(
    $map,
    'resources/js/Pages/Profile/Edit.jsx',
    'presets/admin/resources/js/Pages/Profile/Edit.jsx',
    'resources/js/Pages/Profile/Edit.jsx',
    'admin',
    'low',
    'Generic profile page.',
);
$add(
    $map,
    'app/Http/Controllers/Settings/TelegramSettingsController.php',
    'presets/admin/app/Http/Controllers/Settings/TelegramSettingsController.php',
    'app/Http/Controllers/Settings/TelegramSettingsController.php',
    'admin',
    'medium',
    'Telegram settings controller.',
);
$add(
    $map,
    'app/Http/Controllers/TelegramWebhookController.php',
    'presets/admin/app/Http/Controllers/TelegramWebhookController.php',
    'app/Http/Controllers/TelegramWebhookController.php',
    'admin',
    'medium',
    'Telegram webhook controller.',
);
$add(
    $map,
    'app/Models/TelegramBotSetting.php',
    'presets/admin/app/Models/TelegramBotSetting.php',
    'app/Models/TelegramBotSetting.php',
    'admin',
    'medium',
    'Telegram bot settings model.',
);
$add(
    $map,
    'app/Services/Telegram/TelegramBotManager.php',
    'presets/admin/app/Services/Telegram/TelegramBotManager.php',
    'app/Services/Telegram/TelegramBotManager.php',
    'admin',
    'medium',
    'Telegram bot manager service.',
);
$add(
    $map,
    'database/migrations/2026_07_21_150000_create_telegram_bot_settings_table.php',
    'presets/admin/database/migrations/2026_07_21_150000_create_telegram_bot_settings_table.php',
    'database/migrations/2026_07_21_150000_create_telegram_bot_settings_table.php',
    'admin',
    'medium',
    'Telegram bot settings table migration.',
);
$add(
    $map,
    'app/Models/Customer.php',
    'presets/admin/app/Models/Customer.php',
    'app/Models/Customer.php',
    'admin',
    'medium',
    'Starter CRM customer model.',
);
$add(
    $map,
    'app/Models/Service.php',
    'presets/admin/app/Models/Service.php',
    'app/Models/Service.php',
    'admin',
    'medium',
    'Starter CRM service model.',
);
$add(
    $map,
    'app/Models/Staff.php',
    'presets/admin/app/Models/Staff.php',
    'app/Models/Staff.php',
    'admin',
    'medium',
    'Starter CRM staff model.',
);
$add(
    $map,
    'app/Models/Order.php',
    'presets/admin/app/Models/Order.php',
    'app/Models/Order.php',
    'admin',
    'medium',
    'Starter CRM order model.',
);
$add(
    $map,
    'app/Http/Controllers/CustomersController.php',
    'presets/admin/app/Http/Controllers/CustomersController.php',
    'app/Http/Controllers/CustomersController.php',
    'admin',
    'medium',
    'Starter CRM customers controller.',
);
$add(
    $map,
    'app/Http/Controllers/ServicesController.php',
    'presets/admin/app/Http/Controllers/ServicesController.php',
    'app/Http/Controllers/ServicesController.php',
    'admin',
    'medium',
    'Starter CRM services controller.',
);
$add(
    $map,
    'app/Http/Controllers/StaffController.php',
    'presets/admin/app/Http/Controllers/StaffController.php',
    'app/Http/Controllers/StaffController.php',
    'admin',
    'medium',
    'Starter CRM staff controller.',
);
$add(
    $map,
    'app/Http/Controllers/OrdersController.php',
    'presets/admin/app/Http/Controllers/OrdersController.php',
    'app/Http/Controllers/OrdersController.php',
    'admin',
    'medium',
    'Starter CRM orders controller.',
);
$add(
    $map,
    'app/Http/Controllers/CalendarController.php',
    'presets/admin/app/Http/Controllers/CalendarController.php',
    'app/Http/Controllers/CalendarController.php',
    'admin',
    'medium',
    'Starter CRM calendar controller.',
);
$add(
    $map,
    'database/migrations/2026_07_05_100000_create_customers_table.php',
    'presets/admin/database/migrations/2026_07_05_100000_create_customers_table.php',
    'database/migrations/2026_07_05_100000_create_customers_table.php',
    'admin',
    'medium',
    'Starter CRM customers migration.',
);
$add(
    $map,
    'database/migrations/2026_07_05_100100_create_services_table.php',
    'presets/admin/database/migrations/2026_07_05_100100_create_services_table.php',
    'database/migrations/2026_07_05_100100_create_services_table.php',
    'admin',
    'medium',
    'Starter CRM services migration.',
);
$add(
    $map,
    'database/migrations/2026_07_05_100200_create_staff_table.php',
    'presets/admin/database/migrations/2026_07_05_100200_create_staff_table.php',
    'database/migrations/2026_07_05_100200_create_staff_table.php',
    'admin',
    'medium',
    'Starter CRM staff migration.',
);
$add(
    $map,
    'database/migrations/2026_07_05_100300_create_orders_table.php',
    'presets/admin/database/migrations/2026_07_05_100300_create_orders_table.php',
    'database/migrations/2026_07_05_100300_create_orders_table.php',
    'admin',
    'medium',
    'Starter CRM orders migration.',
);
$add(
    $map,
    'database/migrations/2026_07_05_100400_create_order_staff_table.php',
    'presets/admin/database/migrations/2026_07_05_100400_create_order_staff_table.php',
    'database/migrations/2026_07_05_100400_create_order_staff_table.php',
    'admin',
    'medium',
    'Starter CRM order_staff migration.',
);
$add(
    $map,
    'resources/js/Pages/Customers/Index.jsx',
    'presets/admin/resources/js/Pages/Customers/Index.jsx',
    'resources/js/Pages/Customers/Index.jsx',
    'admin',
    'low',
    'Starter CRM customers page.',
);
$add(
    $map,
    'resources/js/Pages/Services/Index.jsx',
    'presets/admin/resources/js/Pages/Services/Index.jsx',
    'resources/js/Pages/Services/Index.jsx',
    'admin',
    'low',
    'Starter CRM services page.',
);
$add(
    $map,
    'resources/js/Pages/Staff/Index.jsx',
    'presets/admin/resources/js/Pages/Staff/Index.jsx',
    'resources/js/Pages/Staff/Index.jsx',
    'admin',
    'low',
    'Starter CRM staff page.',
);
$add(
    $map,
    'resources/js/Pages/Orders/Index.jsx',
    'presets/admin/resources/js/Pages/Orders/Index.jsx',
    'resources/js/Pages/Orders/Index.jsx',
    'admin',
    'low',
    'Starter CRM orders page.',
);
$add(
    $map,
    'resources/js/Pages/Calendar/Index.jsx',
    'presets/admin/resources/js/Pages/Calendar/Index.jsx',
    'resources/js/Pages/Calendar/Index.jsx',
    'admin',
    'low',
    'Starter CRM calendar page.',
);

return $map;
