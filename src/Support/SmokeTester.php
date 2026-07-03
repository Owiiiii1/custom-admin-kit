<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class SmokeTester
{
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
            return [
                CheckResult::fail('install-state', 'Install state not found.', 'Run owl-admin:install --preset=core'),
            ];
        }

        $results[] = CheckResult::pass(
            'install-state',
            'Install state present (v'.($installState['version'] ?? '?').', preset: '.($installState['preset'] ?? 'core').').'
        );

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

        $published = $installState['published_files'] ?? [];
        $missing = array_filter($published, fn ($p) => ! File::exists($basePath.'/'.ltrim((string) $p, '/')));

        $results[] = $missing === []
            ? CheckResult::pass('published-files', count($published).' core file(s) on disk.')
            : CheckResult::fail('published-files', count($missing).' published file(s) missing.');

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

        $results[] = CheckResult::warn(
            'vite-manifest',
            'Frontend build not verified in core v0.1.',
            'Merge package.json and run npm run build in host app.'
        );

        $expected = count($this->publishMap->copyEntriesForPreset($preset));
        $results[] = CheckResult::pass('publish-map', "Core publish map defines {$expected} copy target(s).");

        return $results;
    }
}
