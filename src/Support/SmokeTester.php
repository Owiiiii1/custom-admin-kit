<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class SmokeTester
{
    public const SECTION_CORE = 'Core';

    public const SECTION_FRONTEND_SETUP = 'Frontend setup';

    /** @var list<string> */
    private const FRONTEND_CORE_ROUTE_NAMES = [
        'dashboard',
        'settings.index',
        'app-settings.index',
        'statistics.logs',
    ];

    public function __construct(
        private readonly PublishMapResolver $publishMap,
    ) {}

    /**
     * @return list<CheckResult>
     */
    public function run(string $basePath, ?array $installState, string $preset = 'core'): array
    {
        $results = [];

        if ($installState === null) {
            $results[] = CheckResult::warn(
                'install-state',
                'Install state not found.',
                'Run owl-admin:install --preset=core (published files are verified from publish map, not state).',
                self::SECTION_CORE,
            );
        } else {
            $installedVersion = (string) ($installState['version'] ?? '?');
            $statePreset = (string) ($installState['preset'] ?? 'core');

            $results[] = CheckResult::pass(
                'install-state',
                'Install state present ('.PackageVersion::display($installedVersion).', preset: '.$statePreset.').',
                section: self::SECTION_CORE,
            );

            $currentVersion = PackageVersion::current();

            if ($installedVersion !== '?' && ! PackageVersion::equals($installedVersion, $currentVersion)) {
                $results[] = CheckResult::warn(
                    'install-state-version',
                    'Install state version '.PackageVersion::display($installedVersion).' differs from current package version '.PackageVersion::display($currentVersion).'.',
                    'Run owl-admin:install --preset=core --repair or reinstall if needed.',
                    self::SECTION_CORE,
                );
            }
        }

        $results[] = CheckResult::pass(
            'core-scope',
            'Core preset does not install landing domain modules.',
            section: self::SECTION_CORE,
        );

        if ($preset === 'admin') {
            $results[] = CheckResult::pass(
                'admin-scope',
                'Admin preset includes generic auth/profile/settings shell only.',
                section: self::SECTION_CORE,
            );
        }

        $reportPath = $basePath.'/'.config('owl-admin-kit.report_file');
        $results[] = File::exists($reportPath)
            ? CheckResult::pass('install-report', 'Install report exists.', section: self::SECTION_CORE)
            : CheckResult::warn('install-report', 'Install report missing.', section: self::SECTION_CORE);

        $configPath = $basePath.'/'.config('owl-admin-kit.paths.config');
        $results[] = File::exists($configPath)
            ? CheckResult::pass('config', 'config/owl-admin.php exists.', section: self::SECTION_CORE)
            : CheckResult::fail('config', 'config/owl-admin.php missing.', section: self::SECTION_CORE);

        $results[] = $this->checkPublishedFiles($basePath, $preset);

        try {
            $hasKitHealth = Route::has('owl-admin.health');
            $results[] = $hasKitHealth
                ? CheckResult::pass('health-route', 'Kit health route owl-admin.health registered.', section: self::SECTION_CORE)
                : CheckResult::warn('health-route', 'routes/owl-admin-core.php not loaded yet.', section: self::SECTION_CORE);
        } catch (\Throwable) {
            $results[] = CheckResult::warn('health-route', 'Could not inspect routes.', section: self::SECTION_CORE);
        }

        $uiPath = config('owl-admin-kit.ui.canonical_path', 'resources/js/Components/ui');
        $results[] = File::isDirectory($basePath.'/'.$uiPath)
            ? CheckResult::pass('ui-path', "Canonical UI path exists: {$uiPath}.", section: self::SECTION_CORE)
            : CheckResult::fail('ui-path', "Missing canonical UI path: {$uiPath}.", section: self::SECTION_CORE);

        $legacyLower = $basePath.'/resources/js/components/ui';
        $results[] = File::isDirectory($legacyLower)
            ? CheckResult::warn('ui-duplicate', 'Legacy path resources/js/components/ui still exists — remove to avoid duplicates.', section: self::SECTION_CORE)
            : CheckResult::pass('ui-duplicate', 'No lowercase components/ui directory.', section: self::SECTION_CORE);

        $frontendState = (new FrontendSetupState(FrontendSetupState::pathFor($basePath)))->read();
        $frontendSetupCompleted = is_array($frontendState) && ($frontendState['completed'] ?? false) === true;

        if (! $frontendSetupCompleted) {
            $results[] = $this->checkViteManifest($basePath);
        }

        $expected = count($this->publishMap->copyEntriesForPreset($preset));
        $results[] = $expected > 0
            ? CheckResult::pass('publish-map', "Core publish map defines {$expected} copy target(s).", section: self::SECTION_CORE)
            : CheckResult::fail('publish-map', 'Core publish map defines 0 copy target(s).', section: self::SECTION_CORE);

        if ($preset === 'admin') {
            $results[] = $this->checkFrontendFile(
                'auth-login-page',
                $basePath.'/resources/js/Pages/Auth/Login.jsx',
                'resources/js/Pages/Auth/Login.jsx exists.',
                'resources/js/Pages/Auth/Login.jsx is missing.',
                self::SECTION_CORE,
            );
            $results[] = $this->checkFrontendFile(
                'auth-layout',
                $basePath.'/resources/js/Layouts/AuthLayout.jsx',
                'resources/js/Layouts/AuthLayout.jsx exists.',
                'resources/js/Layouts/AuthLayout.jsx is missing.',
                self::SECTION_CORE,
            );
            $results[] = $this->checkFrontendFile(
                'profile-page',
                $basePath.'/resources/js/Pages/Profile/Edit.jsx',
                'resources/js/Pages/Profile/Edit.jsx exists.',
                'resources/js/Pages/Profile/Edit.jsx is missing.',
                self::SECTION_CORE,
            );
            $results[] = $this->checkFrontendFile(
                'ai-settings-page',
                $basePath.'/resources/js/Pages/AiSettings/Index.jsx',
                'resources/js/Pages/AiSettings/Index.jsx exists.',
                'resources/js/Pages/AiSettings/Index.jsx is missing.',
                self::SECTION_CORE,
            );
            $results[] = $this->checkFrontendFile(
                'ai-settings-model',
                $basePath.'/app/Models/AiProviderSetting.php',
                'app/Models/AiProviderSetting.php exists.',
                'app/Models/AiProviderSetting.php is missing.',
                self::SECTION_CORE,
            );
            $aiMigration = glob($basePath.'/database/migrations/*create_ai_provider_settings_table*.php') ?: [];
            $results[] = $aiMigration !== []
                ? CheckResult::pass('ai-settings-migration', 'ai_provider_settings migration exists.', section: self::SECTION_CORE)
                : CheckResult::fail('ai-settings-migration', 'ai_provider_settings migration is missing.', section: self::SECTION_CORE);
            $results[] = class_exists(\OwlSolutions\CustomAdminKit\Commands\MakeAdminCommand::class)
                ? CheckResult::pass('make-admin-command', 'owl-admin:make-admin command class is available.', section: self::SECTION_CORE)
                : CheckResult::fail('make-admin-command', 'owl-admin:make-admin command class is missing.', section: self::SECTION_CORE);

            $hasUsersTable = false;
            try {
                $hasUsersTable = Schema::hasTable('users');
            } catch (\Throwable) {
                $hasUsersTable = false;
            }

            $results[] = $hasUsersTable
                ? CheckResult::pass('users-table', 'users table exists.', section: self::SECTION_CORE)
                : CheckResult::warn('users-table', 'users table missing (run migrations).', 'php artisan migrate', self::SECTION_CORE);

            $hasAiTable = false;
            try {
                $hasAiTable = Schema::hasTable('ai_provider_settings');
            } catch (\Throwable) {
                $hasAiTable = false;
            }
            $results[] = $hasAiTable
                ? CheckResult::pass('ai-provider-settings-table', 'ai_provider_settings table exists.', section: self::SECTION_CORE)
                : CheckResult::fail('ai-provider-settings-table', 'ai_provider_settings table missing.', 'Run php artisan migrate.', self::SECTION_CORE);

            $results[] = $this->checkFrontendFile(
                'admin-layout-ai-badge',
                $basePath.'/resources/js/Layouts/AdminLayout.jsx',
                'resources/js/Layouts/AdminLayout.jsx exists.',
                'resources/js/Layouts/AdminLayout.jsx is missing.',
                self::SECTION_CORE,
            );
            $layoutContents = File::exists($basePath.'/resources/js/Layouts/AdminLayout.jsx')
                ? (string) File::get($basePath.'/resources/js/Layouts/AdminLayout.jsx')
                : '';
            $results[] = str_contains($layoutContents, 'ai-settings.index')
                ? CheckResult::pass('admin-layout-ai-menu', 'AdminLayout contains AI Settings menu route.', section: self::SECTION_CORE)
                : CheckResult::warn('admin-layout-ai-menu', 'AdminLayout AI Settings menu route not detected.', section: self::SECTION_CORE);

            $results[] = $this->checkDashboardAuthMiddleware();
        }

        $results = array_merge($results, $this->checkFrontendSetup($basePath, $frontendState, $preset));

        return $results;
    }

    /**
     * @return list<CheckResult>
     */
    public function checkFrontendSetup(string $basePath, ?array $frontendState, string $preset = 'core'): array
    {
        $section = self::SECTION_FRONTEND_SETUP;

        if ($frontendState === null) {
            return [
                CheckResult::warn(
                    'frontend-setup-detected',
                    'Frontend setup not detected; admin page routes not checked.',
                    null,
                    $section,
                ),
            ];
        }

        if (($frontendState['completed'] ?? false) !== true) {
            $errors = $frontendState['errors'] ?? [];
            $hint = is_array($errors) && $errors !== []
                ? implode("\n", array_map(static fn (mixed $error): string => '- '.(string) $error, $errors))
                : 'Re-run owl-admin:frontend-setup --preset=core --backup.';

            return [
                CheckResult::warn(
                    'frontend-state',
                    'Frontend setup incomplete.',
                    $hint,
                    $section,
                ),
            ];
        }

        $results = [
            CheckResult::pass('frontend-state', 'completed', section: $section),
        ];

        foreach ($this->frontendRouteNamesForPreset($preset) as $routeName) {
            $results[] = $this->checkFrontendRoute($routeName, $section);
        }

        $results[] = $this->checkFrontendFile(
            'routes-file',
            $basePath.'/routes/owl-admin-pages.php',
            'routes/owl-admin-pages.php exists.',
            'routes/owl-admin-pages.php is missing.',
            $section,
        );

        $results[] = $this->checkWebRoutesInclude($basePath, $section, $preset);
        $results[] = $this->checkInertiaOwlAdminShare($basePath, $section);
        $results[] = $this->checkFrontendFile(
            'app-jsx',
            $basePath.'/resources/js/app.jsx',
            'resources/js/app.jsx exists.',
            'resources/js/app.jsx is missing.',
            $section,
        );
        $results[] = $this->checkFrontendViteManifest($basePath, $section);

        return $results;
    }

    private function checkFrontendRoute(string $routeName, string $section): CheckResult
    {
        try {
            if (Route::has($routeName)) {
                return CheckResult::pass('route:'.$routeName, 'registered.', section: $section);
            }
        } catch (\Throwable) {
            return CheckResult::fail(
                'route:'.$routeName,
                'Could not inspect routes.',
                'Ensure routes/owl-admin-pages.php is loaded from routes/web.php.',
                $section,
            );
        }

        return CheckResult::fail(
            'route:'.$routeName,
            'Route not registered.',
            'Run owl-admin:frontend-setup --preset=core --backup or merge routes manually.',
            $section,
        );
    }

    private function checkFrontendFile(
        string $name,
        string $absolutePath,
        string $passMessage,
        string $failMessage,
        string $section,
    ): CheckResult {
        return File::exists($absolutePath)
            ? CheckResult::pass($name, $passMessage, section: $section)
            : CheckResult::fail($name, $failMessage, section: $section);
    }

    private function checkWebRoutesInclude(string $basePath, string $section, string $preset): CheckResult
    {
        $webRoutesPath = $basePath.'/routes/web.php';

        if (! File::exists($webRoutesPath)) {
            return CheckResult::fail(
                'web-routes-include',
                'routes/web.php is missing.',
                'Add require __DIR__.\'/owl-admin-pages.php\'; to routes/web.php.',
                $section,
            );
        }

        $contents = (string) File::get($webRoutesPath);

        $missing = [];
        foreach ($this->requiredWebRouteIncludes($preset) as $include) {
            if (! str_contains($contents, $include)) {
                $missing[] = $include;
            }
        }

        if ($missing === []) {
            return CheckResult::pass(
                'web-routes-include',
                'routes/web.php includes required owl-admin route files.',
                section: $section,
            );
        }

        return CheckResult::fail(
            'web-routes-include',
            'routes/web.php does not include all required owl-admin route files.',
            "Add:\n".implode("\n", array_map(static fn (string $file): string => "require __DIR__.'/{$file}';", $missing)),
            $section,
        );
    }

    /**
     * @return list<string>
     */
    private function frontendRouteNamesForPreset(string $preset): array
    {
        $routes = self::FRONTEND_CORE_ROUTE_NAMES;

        if ($preset === 'admin') {
            $routes = array_merge($routes, [
                'login',
                'logout',
                'profile.edit',
                'ai-settings.index',
                'ai-settings.save-key',
                'ai-settings.check',
                'ai-settings.activate',
                'ai-settings.deactivate',
            ]);
        }

        return $routes;
    }

    /**
     * @return list<string>
     */
    private function requiredWebRouteIncludes(string $preset): array
    {
        $includes = ['owl-admin-pages.php'];

        if ($preset === 'admin') {
            $includes[] = 'owl-admin-auth.php';
        }

        return $includes;
    }

    private function checkDashboardAuthMiddleware(): CheckResult
    {
        try {
            $route = Route::getRoutes()->getByName('dashboard');
            if ($route === null) {
                return CheckResult::fail(
                    'dashboard-auth-middleware',
                    'Route dashboard not found.',
                    'Ensure routes/owl-admin-pages.php is loaded.',
                    self::SECTION_CORE,
                );
            }

            $middleware = $route->middleware();

            if (in_array('auth', $middleware, true)) {
                return CheckResult::pass(
                    'dashboard-auth-middleware',
                    'dashboard route uses auth middleware.',
                    section: self::SECTION_CORE,
                );
            }

            return CheckResult::fail(
                'dashboard-auth-middleware',
                'dashboard route is not protected by auth middleware.',
                section: self::SECTION_CORE,
            );
        } catch (\Throwable) {
            return CheckResult::warn(
                'dashboard-auth-middleware',
                'Could not inspect middleware for dashboard route.',
                section: self::SECTION_CORE,
            );
        }
    }

    private function checkInertiaOwlAdminShare(string $basePath, string $section): CheckResult
    {
        $middlewarePath = $basePath.'/app/Http/Middleware/HandleInertiaRequests.php';

        if (! File::exists($middlewarePath)) {
            return CheckResult::fail(
                'inertia-share',
                'HandleInertiaRequests middleware is missing.',
                'Run php artisan inertia:middleware, then owl-admin:frontend-setup --preset=core --backup.',
                $section,
            );
        }

        $contents = (string) File::get($middlewarePath);

        if (str_contains($contents, 'owlAdmin')) {
            return CheckResult::pass(
                'inertia-share',
                'owlAdmin shared.',
                section: $section,
            );
        }

        return CheckResult::fail(
            'inertia-share',
            'owlAdmin props are not shared in HandleInertiaRequests.',
            'Run owl-admin:frontend-setup --preset=core --backup or merge docs/merge-snippets/HandleInertiaRequests.php.',
            $section,
        );
    }

    private function checkFrontendViteManifest(string $basePath, string $section): CheckResult
    {
        $manifestPath = 'public/build/manifest.json';
        $absolutePath = $basePath.'/'.ltrim($manifestPath, '/');

        if (File::exists($absolutePath)) {
            return CheckResult::pass('vite-manifest', "{$manifestPath} exists.", section: $section);
        }

        return CheckResult::fail(
            'vite-manifest',
            'Frontend build not found.',
            'Run npm run build.',
            $section,
        );
    }

    public function checkPublishedFiles(string $basePath, string $preset): CheckResult
    {
        $targets = $this->publishTargetsForPreset($preset);
        $totalCount = count($targets);

        if ($totalCount === 0) {
            return CheckResult::fail(
                'published-files',
                '0/0 core file(s) on disk.',
                'Publish map is empty or failed to load for preset: '.$preset,
                self::SECTION_CORE,
            );
        }

        $missing = [];

        foreach ($targets as $target) {
            if (! File::exists($basePath.'/'.ltrim($target, '/'))) {
                $missing[] = $target;
            }
        }

        $existingCount = $totalCount - count($missing);

        if ($missing !== []) {
            $hint = "Missing:\n".implode("\n", array_map(static fn (string $path): string => '- '.$path, $missing));

            return CheckResult::fail(
                'published-files',
                "{$existingCount}/{$totalCount} core file(s) on disk.",
                $hint,
                self::SECTION_CORE,
            );
        }

        return CheckResult::pass(
            'published-files',
            "{$existingCount}/{$totalCount} core file(s) on disk.",
            section: self::SECTION_CORE,
        );
    }

    public function checkViteManifest(string $basePath): CheckResult
    {
        $manifestPath = 'public/build/manifest.json';
        $absolutePath = $basePath.'/'.ltrim($manifestPath, '/');

        if (File::exists($absolutePath)) {
            return CheckResult::pass('vite-manifest', "{$manifestPath} exists.", section: self::SECTION_CORE);
        }

        return CheckResult::warn(
            'vite-manifest',
            'Frontend build not found.',
            'Run npm run build.',
            self::SECTION_CORE,
        );
    }

    /**
     * @return list<string>
     */
    private function publishTargetsForPreset(string $preset): array
    {
        $targets = [];

        foreach ($this->publishMap->copyEntriesForPreset($preset) as $entry) {
            $target = (string) ($entry['target'] ?? '');

            if ($target !== '') {
                $targets[] = $target;
            }
        }

        return $targets;
    }
}
