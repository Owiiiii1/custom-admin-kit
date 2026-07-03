<?php

/**
 * Core-only publish map for owlsolutions/custom-admin-kit v0.1.
 * Domain/auth/frontend merge files from landing are intentionally excluded.
 */

$uiComponents = [
    'avatar.jsx', 'badge.jsx', 'button.jsx', 'card.jsx', 'dialog.jsx',
    'dropdown-menu.jsx', 'input.jsx', 'label.jsx', 'separator.jsx',
    'sheet.jsx', 'table.jsx', 'tooltip.jsx',
];

$map = [
    [
        'source' => 'config/owl-admin.php',
        'stub' => 'config/owl-admin.php',
        'target' => 'config/owl-admin.php',
        'mode' => 'copy',
        'preset' => 'core',
        'conflict' => 'low',
        'notes' => 'Host branding and route names; brand_name read by AdminLayout via shared Inertia props',
    ],
    [
        'source' => 'routes/owl-admin-core.php',
        'stub' => 'routes/owl-admin-core.php',
        'target' => 'routes/owl-admin-core.php',
        'mode' => 'copy',
        'preset' => 'core',
        'conflict' => 'low',
        'notes' => 'Kit health route GET /owl-admin/health; loaded by package ServiceProvider when file exists',
    ],
    [
        'source' => 'resources/css/owl-admin.css',
        'stub' => 'resources/css/owl-admin.css',
        'target' => 'resources/css/owl-admin.css',
        'mode' => 'copy',
        'preset' => 'core',
        'conflict' => 'medium',
        'notes' => 'Import in host app.css or merge manually',
    ],
    [
        'source' => 'resources/js/lib/utils.js',
        'stub' => 'resources/js/lib/utils.js',
        'target' => 'resources/js/lib/utils.js',
        'mode' => 'copy',
        'preset' => 'core',
        'conflict' => 'low',
        'notes' => 'shadcn cn() helper',
    ],
    [
        'source' => 'resources/js/Layouts/AdminLayout.jsx',
        'stub' => 'resources/js/Layouts/AdminLayout.jsx',
        'target' => 'resources/js/Layouts/AdminLayout.jsx',
        'mode' => 'copy',
        'preset' => 'core',
        'conflict' => 'medium',
        'notes' => 'Core shell only — no landing domain nav items',
    ],
    [
        'source' => 'resources/js/Pages/Dashboard.jsx',
        'stub' => 'resources/js/Pages/Dashboard.jsx',
        'target' => 'resources/js/Pages/Dashboard.jsx',
        'mode' => 'copy',
        'preset' => 'core',
        'conflict' => 'low',
        'notes' => 'Placeholder dashboard page',
    ],
    [
        'source' => 'resources/js/Pages/AppSettings/Index.jsx',
        'stub' => 'resources/js/Pages/AppSettings/Index.jsx',
        'target' => 'resources/js/Pages/AppSettings/Index.jsx',
        'mode' => 'copy',
        'preset' => 'core',
        'conflict' => 'low',
        'notes' => 'Placeholder app settings page',
    ],
    [
        'source' => 'resources/js/Pages/Statistics/Logs.jsx',
        'stub' => 'resources/js/Pages/Statistics/Logs.jsx',
        'target' => 'resources/js/Pages/Statistics/Logs.jsx',
        'mode' => 'copy',
        'preset' => 'core',
        'conflict' => 'low',
        'notes' => 'Placeholder statistics/logs page',
    ],
    [
        'source' => 'resources/js/Pages/Settings/Index.jsx',
        'stub' => 'resources/js/Pages/Settings/Index.jsx',
        'target' => 'resources/js/Pages/Settings/Index.jsx',
        'mode' => 'copy',
        'preset' => 'core',
        'conflict' => 'low',
        'notes' => 'Placeholder settings page (no user CRUD in v0.1 core)',
    ],
    [
        'source' => 'public/images/company-logo.svg',
        'stub' => 'public/images/company-logo.svg',
        'target' => 'public/images/company-logo.svg',
        'mode' => 'copy',
        'preset' => 'core',
        'conflict' => 'low',
        'notes' => 'Replace via config owl-admin.logo_path',
    ],
    [
        'source' => 'public/images/auth-abstract-bg.svg',
        'stub' => 'public/images/auth-abstract-bg.svg',
        'target' => 'public/images/auth-abstract-bg.svg',
        'mode' => 'copy',
        'preset' => 'core',
        'conflict' => 'low',
        'notes' => 'Optional branding asset',
    ],
];

foreach ($uiComponents as $file) {
    $map[] = [
        'source' => "resources/js/Components/ui/{$file}",
        'stub' => "resources/js/Components/ui/{$file}",
        'target' => "resources/js/Components/ui/{$file}",
        'mode' => 'copy',
        'preset' => 'core',
        'conflict' => 'low',
        'notes' => 'Canonical UI path resources/js/Components/ui',
    ];
}

return $map;
