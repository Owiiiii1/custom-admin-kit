<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class SmokeTester
{
    private const SECTION_FRONTEND_SETUP = 'frontend-setup';

    /** @var list<string> */
    private const FRONTEND_ROUTE_NAMES = [
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
            );
        } else {
            $installedVersion = (string) ($installState['version'] ?? '?');
            $statePreset = (string) ($installState['preset'] ?? 'core');

            $results[] = CheckResult::pass(
                'install-state',
                'Install state present ('.PackageVersion::display($installedVersion).', preset: '.$statePreset.').'
            );

            $currentVersion = PackageVersion::current();

            if ($installedVersion !== '?' && ! PackageVersion::equals($installedVersion, $currentVersion)) {
                $results[] = CheckResult::warn(
                    'install-state-version',
                    'Install state version '.PackageVersion::display($installedVersion).' differs from current package version '.PackageVersion::display($currentVersion).'.',
                    'Run owl-admin:install --preset=core --repair or reinstall if needed.',
                );
            }
        }

        $results[] = CheckResult::pass(
            'core-scope',
            'Core preset does not install landing domain modules.'
        );

        $reportPath = $basePath.'/'.config('owl-admin-kit.report_file');
        $results[] = File::exists($reportPath)
            ? CheckResult::pass('install-report', 'Install report exists.')
            : CheckResult::warn('install-report', 'Install report missing.');

        $configPath = $basePath.'/'.config('owl-admin-kit.paths.config');
        $results[] = File::exists($configPath)
            ? CheckResult::pass('config', 'config/owl-admin.php exists.')
            : CheckResult::fail('config', 'config/owl-admin.php missing.');

        $results[] = $this->checkPublishedFiles($basePath, $preset);

        try {
            $hasKitHealth = Route::has('owl-admin.health');
            $results[] = $hasKitHealth
                ? CheckResult::pass('health-route', 'Kit health route owl-admin.health registered.')
                : CheckResult::warn('health-route', 'routes/owl-admin-core.php not loaded yet.');
        } catch (\Throwable) {
            $results[] = CheckResult::warn('health-route', 'Could not inspect routes.');
        }

        $uiPath = config('owl-admin-kit.ui.canonical_path', 'resources/js/Components/ui');
        $results[] = File::isDirectory($basePath.'/'.$uiPath)
            ? CheckResult::pass('ui-path', "Canonical UI path exists: {$uiPath}.")
            : CheckResult::fail('ui-path', "Missing canonical UI path: {$uiPath}.");

        $legacyLower = $basePath.'/resources/js/components/ui';
        $results[] = File::isDirectory($legacyLower)
            ? CheckResult::warn('ui-duplicate', 'Legacy path resources/js/components/ui still exists — remove to avoid duplicates.')
            : CheckResult::pass('ui-duplicate', 'No lowercase components/ui directory.');

        $frontendState = (new FrontendSetupState(FrontendSetupState::pathFor($basePath)))->read();
        $frontendSetupCompleted = is_array($frontendState) && ($frontendState['completed'] ?? false) === true;

        if (! $frontendSetupCompleted) {
            $results[] = $this->checkViteManifest($basePath);
        }

        $expected = count($this->publishMap->copyEntriesForPreset($preset));
        $results[] = $expected > 0
            ? CheckResult::pass('publish-map', "Core publish map defines {$expected} copy target(s).")
            : CheckResult::fail('publish-map', 'Core publish map defines 0 copy target(s).');

        $results = array_merge($results, $this->checkFrontendSetup($basePath, $frontendState));

        return $results;
    }

    /**
     * @return list<CheckResult>
     */
    public function checkFrontendSetup(string $basePath, ?array $frontendState): array
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
            CheckResult::pass('frontend-state', 'completed', null, $section),
        ];

        foreach (self::FRONTEND_ROUTE_NAMES as $routeName) {
            $results[] = $this->checkFrontendRoute($routeName, $section);
        }

        $results[] = $this->checkFrontendFile(
            'routes-file',
            $basePath.'/routes/owl-admin-pages.php',
            'routes/owl-admin-pages.php exists.',
            'routes/owl-admin-pages.php is missing.',
            $section,
        );

        $results[] = $this->checkWebRoutesInclude($basePath, $section);
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
                return CheckResult::pass('route:'.$routeName, 'registered.', null, $section);
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
            ? CheckResult::pass($name, $passMessage, null, $section)
            : CheckResult::fail($name, $failMessage, null, $section);
    }

    private function checkWebRoutesInclude(string $basePath, string $section): CheckResult
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

        if (str_contains($contents, 'owl-admin-pages.php')) {
            return CheckResult::pass(
                'web-routes-include',
                'routes/web.php includes owl-admin-pages.php.',
                null,
                $section,
            );
        }

        return CheckResult::fail(
            'web-routes-include',
            'routes/web.php does not include owl-admin-pages.php.',
            "Add:\nrequire __DIR__.'/owl-admin-pages.php';",
            $section,
        );
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
                null,
                $section,
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
            return CheckResult::pass('vite-manifest', "{$manifestPath} exists.", null, $section);
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
            );
        }

        return CheckResult::pass(
            'published-files',
            "{$existingCount}/{$totalCount} core file(s) on disk.",
        );
    }

    public function checkViteManifest(string $basePath): CheckResult
    {
        $manifestPath = 'public/build/manifest.json';
        $absolutePath = $basePath.'/'.ltrim($manifestPath, '/');

        if (File::exists($absolutePath)) {
            return CheckResult::pass('vite-manifest', "{$manifestPath} exists.");
        }

        return CheckResult::warn(
            'vite-manifest',
            'Frontend build not found.',
            'Run npm run build.',
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
