<?php

$publishMap = require __DIR__.'/publish-map.php';

return [

    'version' => '0.1.1',

    'name' => 'custom-admin-kit',

    'supported_matrix' => [
        'php' => '^8.3',
        'laravel' => '^13.0',
        'node' => '>=20.19.0',
        'npm' => '>=10.0.0',
        'admin_type' => 'inertia-react-blade-shell',
        'uses_filament' => false,
        'uses_livewire' => false,
        'audit_reference' => [
            'php' => '8.3.30',
            'laravel' => '13.9.0',
            'node' => 'v24.14.0',
            'npm' => '11.9.0',
        ],
    ],

    /*
    | v0.1 is core-only. Other presets are reserved for future releases.
    */
    'presets' => ['core'],

    'unavailable_presets' => [
        'full' => 'Full preset is not available in v0.1. Use --preset=core.',
        'auth' => 'Auth preset is not available in v0.1. Use --preset=core.',
        'frontend' => 'Frontend preset is not available in v0.1. Use --preset=core.',
    ],

    'state_file' => 'storage/app/owl-admin-kit.json',
    'report_file' => 'storage/app/owl-admin-kit-report.json',
    'backup_suffix' => '.owl-admin-backup',

    'paths' => [
        'config' => 'config/owl-admin.php',
        'routes_core' => 'routes/owl-admin-core.php',
        'merge_snippets' => 'docs/vendor/owl-admin/merge-snippets',
    ],

    'ui' => [
        'canonical_path' => 'resources/js/Components/ui',
        'import_alias' => '@/Components/ui',
    ],

    'branding' => [
        'brand_name' => env('OWL_ADMIN_BRAND', 'Service Admin'),
        'logo_path' => env('OWL_ADMIN_LOGO', '/images/company-logo.svg'),
        'inertia_prop_key' => 'owlAdmin',
    ],

    'admin' => [
        'login_path' => 'admin',
        'dashboard_route' => 'dashboard',
        'route_prefix' => '',
        'middleware' => ['web', 'auth'],
    ],

    'email_verification' => [
        'enabled' => filter_var(env('OWL_ADMIN_EMAIL_VERIFICATION', false), FILTER_VALIDATE_BOOL),
    ],

    'admin_user' => [
        'enabled' => true,
        'name' => env('OWL_ADMIN_NAME', 'Admin'),
        'email' => env('OWL_ADMIN_EMAIL'),
        'password_env' => 'OWL_ADMIN_PASSWORD',
        'default_password_allowed' => filter_var(env('OWL_ADMIN_ALLOW_DEFAULT_PASSWORD', false), FILTER_VALIDATE_BOOL),
        'command' => 'owl-admin:make-admin',
    ],

    'admin_seed_env' => [
        [
            'key' => 'OWL_ADMIN_EMAIL',
            'description' => 'Admin user email for --seed / make-admin',
        ],
        [
            'key' => 'OWL_ADMIN_PASSWORD',
            'description' => 'Admin user password (env key from admin_user.password_env)',
        ],
    ],

    'required_env' => [
        ['key' => 'APP_NAME', 'required' => true, 'description' => 'Application title'],
        ['key' => 'APP_KEY', 'required' => true, 'description' => 'Encryption key'],
        ['key' => 'APP_URL', 'required' => true, 'description' => 'Base URL for assets'],
    ],

    'host_dependencies' => [
        'required' => [],
        'recommended' => [
            'inertiajs/inertia-laravel' => [
                'composer_path' => 'inertiajs/inertia-laravel',
                'probe' => 'class:Inertia\\Inertia',
                'install_hint' => 'composer require inertiajs/inertia-laravel (required to render published JSX pages)',
            ],
            'tightenco/ziggy' => [
                'composer_path' => 'tightenco/ziggy',
                'probe' => 'path:vendor/tightenco/ziggy',
                'install_hint' => 'composer require tightenco/ziggy (for route() in AdminLayout)',
            ],
        ],
        'optional' => [],
    ],

    'excluded_from_core' => [
        'booking', 'customers', 'jobs', 'orders', 'services', 'staff', 'calendar',
        'public booking', 'domain migrations', 'spatie packages', 'auth controllers',
        'user migrations', 'routes/web.php', 'package.json', 'vite.config.js', 'app.jsx',
    ],

    'features' => [
        'auth' => false,
        'frontend' => false,
        'database' => false,
        'filament' => false,
        'domain_modules' => false,
    ],

    /*
    | Core frontend npm packages (derived from stub imports + PostCSS/Vite audit).
    | route() uses @routes + tightenco/ziggy (Composer), not ziggy-js npm.
    | @tailwindcss/vite is excluded — landing vite.config uses PostCSS + tailwindcss.
    */
    'frontend_dependencies' => [
        'from_stub_imports' => [
            'react', 'react-dom', '@inertiajs/react', 'lucide-react', 'radix-ui',
            'class-variance-authority', 'clsx', 'tailwind-merge',
        ],
        'build_toolchain' => [
            'vite', '@vitejs/plugin-react', 'laravel-vite-plugin',
            'tailwindcss', 'postcss', 'autoprefixer', 'tailwindcss-animate',
        ],
        'excluded' => [
            'ziggy-js', '@tailwindcss/vite', '@tanstack/react-table', 'recharts',
        ],
    ],

    'publish_map' => $publishMap,

];
