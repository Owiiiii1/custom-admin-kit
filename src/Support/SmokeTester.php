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

        $results[] = $this->checkViteManifest($basePath);

        $expected = count($this->publishMap->copyEntriesForPreset($preset));
        $results[] = $expected > 0
            ? CheckResult::pass('publish-map', "Core publish map defines {$expected} copy target(s).")
            : CheckResult::fail('publish-map', 'Core publish map defines 0 copy target(s).');

        return $results;
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
