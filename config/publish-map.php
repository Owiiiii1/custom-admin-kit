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
    'Settings with users management.',
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
    'resources/js/Pages/AiSettings/Index.jsx',
    'presets/admin/resources/js/Pages/AiSettings/Index.jsx',
    'resources/js/Pages/AiSettings/Index.jsx',
    'admin',
    'low',
    'AI settings page.',
);

return $map;
