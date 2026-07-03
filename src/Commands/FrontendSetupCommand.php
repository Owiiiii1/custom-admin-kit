<?php

namespace OwlSolutions\CustomAdminKit\Commands;

use OwlSolutions\CustomAdminKit\Support\FileBackupManager;
use OwlSolutions\CustomAdminKit\Support\FrontendDependencyChecker;
use OwlSolutions\CustomAdminKit\Support\FrontendSetupPlanner;
use OwlSolutions\CustomAdminKit\Support\FrontendSetupResult;
use OwlSolutions\CustomAdminKit\Support\InertiaAppMerger;
use OwlSolutions\CustomAdminKit\Support\InertiaMiddlewareMerger;
use OwlSolutions\CustomAdminKit\Support\PackageJsonMerger;
use OwlSolutions\CustomAdminKit\Support\PublishMapResolver;
use OwlSolutions\CustomAdminKit\Support\ViteConfigMerger;

class FrontendSetupCommand extends BaseKitCommand
{
    protected $signature = 'owl-admin:frontend-setup
                            {--preset=core : Frontend setup preset (v0.2: core only)}
                            {--dry-run : Show merge plan without writing files}
                            {--backup : Backup host files before merge}
                            {--force : Apply merges even when files already exist}
                            {--install-npm : Install missing npm dependencies}
                            {--run-build : Run npm run build after setup}';

    protected $description = 'Prepare host Laravel frontend for published core admin stubs (v0.2)';

    public function handle(
        FrontendSetupPlanner $planner,
        PublishMapResolver $publishMap,
        FileBackupManager $backupManager,
        FrontendDependencyChecker $frontendDependencies,
        PackageJsonMerger $packageJsonMerger,
        ViteConfigMerger $viteConfigMerger,
        InertiaAppMerger $inertiaAppMerger,
        InertiaMiddlewareMerger $inertiaMiddlewareMerger,
    ): int {
        $this->printBanner('Frontend setup');

        $preset = (string) $this->option('preset');
        $dryRun = (bool) $this->option('dry-run');
        $backup = (bool) $this->option('backup');
        $force = (bool) $this->option('force');
        $installNpm = (bool) $this->option('install-npm');
        $runBuild = (bool) $this->option('run-build');
        $basePath = base_path();

        if (! $publishMap->isPresetAvailable($preset)) {
            $this->error($publishMap->unavailablePresetMessage($preset) ?? "Preset [{$preset}] unavailable.");

            return self::FAILURE;
        }

        $result = $planner->plan($basePath, $preset);

        $this->info('Prerequisites:');
        $failures = $this->renderCheckResults($result->prerequisites);

        if ($failures > 0) {
            $this->error('Frontend setup aborted due to failed prerequisites.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->renderPlan($result, $installNpm, $runBuild, $dryRun);

        if ($dryRun) {
            $this->info('Dry run complete — no files were modified.');

            return self::SUCCESS;
        }

        if ($result->requiresWrite() && ! $force && ! $backup) {
            $this->error('Refusing to modify host files without --backup or --force.');

            return self::FAILURE;
        }

        $filesToTouch = $this->mergeTargetFiles($result);

        if ($backup && $filesToTouch !== []) {
            $backupDir = $backupManager->backupDirectory($basePath);
            $backedUp = $backupManager->backupFiles($basePath, $backupDir, $filesToTouch);
            $this->info('Backup created: '.$backupDir);
            foreach ($backedUp as $file) {
                $this->line('  <fg=gray>→</> '.basename($file));
            }
            $this->newLine();
        }

        $this->info('Applying frontend setup (v0.2 skeleton merges)...');

        if ($this->shouldApply($result, 'package.json')) {
            $packageJsonMerger->apply($basePath, $result->missingNpm);
            $this->line('  <fg=green>→</> package.json updated.');
        }

        if ($this->shouldApply($result, 'vite.config.js')) {
            $viteConfigMerger->apply($basePath);
            $this->line('  <fg=green>→</> vite.config.js updated.');
        }

        if ($this->shouldApply($result, 'resources/css/app.css')) {
            $this->appendOwlAdminCssImport($basePath);
            $this->line('  <fg=green>→</> resources/css/app.css updated.');
        }

        if ($this->shouldApplyAppEntry($result)) {
            $inertiaAppMerger->apply($basePath);
            $this->line('  <fg=green>→</> Inertia app entry updated.');
        }

        if ($this->shouldApply($result, 'app/Http/Middleware/HandleInertiaRequests.php')) {
            $inertiaMiddlewareMerger->apply($basePath);
            $this->line('  <fg=green>→</> HandleInertiaRequests updated.');
        }

        if ($installNpm && $result->missingNpm !== []) {
            $this->info('Installing missing npm packages...');
            $this->line('  <fg=gray>→</> '.$result->npmInstallCommand);

            $installResult = $frontendDependencies->installPackages($basePath, $result->missingNpm);

            if ($installResult->output !== '') {
                $this->line($installResult->output);
            }

            if (! $installResult->successful) {
                $this->error('npm install failed.');

                return self::FAILURE;
            }
        }

        if ($runBuild) {
            $this->info('Running npm run build...');
            $process = new \Symfony\Component\Process\Process(['npm', 'run', 'build'], $basePath, null, null, 600);
            $process->run();

            if ($process->getOutput() !== '') {
                $this->line($process->getOutput());
            }

            if (! $process->isSuccessful()) {
                $this->error(trim($process->getErrorOutput()) ?: 'npm run build failed.');

                return self::FAILURE;
            }

            $this->info('npm run build completed.');
        }

        $this->newLine();
        $this->info('Frontend setup complete.');

        return self::SUCCESS;
    }

    private function renderPlan(FrontendSetupResult $result, bool $installNpm, bool $runBuild, bool $dryRun): void
    {
        $this->info('Frontend merge plan:');

        foreach ($result->planSteps as $step) {
            $icon = match ($step['action']) {
                'skip' => '<fg=green>→</>',
                'merge', 'create' => '<fg=cyan>→</>',
                'blocked' => '<fg=red>✗</>',
                default => '<fg=gray>→</>',
            };

            $this->line("  {$icon} [{$step['action']}] {$step['file']}: {$step['detail']}");
        }

        if ($result->missingNpm !== []) {
            $this->newLine();
            $this->line('  <fg=yellow>!</> Missing npm packages: '.implode(', ', $result->missingNpm));

            if ($result->npmInstallCommand !== null) {
                $this->line('  <fg=yellow>→</> '.$result->npmInstallCommand);
            }

            $this->line('  <fg=gray>→ install with --install-npm'.($dryRun ? ' (dry-run only shows plan)' : '').'</>');
        }

        if ($installNpm && ! $dryRun) {
            $this->line('  <fg=cyan>→</> --install-npm will run npm install for missing packages.');
        }

        if ($runBuild && ! $dryRun) {
            $this->line('  <fg=cyan>→</> --run-build will run npm run build after merges.');
        }

        $this->newLine();
    }

    /**
     * @return list<string>
     */
    private function mergeTargetFiles(FrontendSetupResult $result): array
    {
        $files = [];

        foreach ($result->planSteps as $step) {
            if (in_array($step['action'], ['merge', 'create'], true)) {
                $files[] = $step['file'];
            }
        }

        return array_values(array_unique($files));
    }

    private function shouldApply(FrontendSetupResult $result, string $file): bool
    {
        foreach ($result->planSteps as $step) {
            if ($step['file'] === $file && $step['action'] === 'merge') {
                return true;
            }
        }

        return false;
    }

    private function shouldApplyAppEntry(FrontendSetupResult $result): bool
    {
        foreach ($result->planSteps as $step) {
            if (in_array($step['file'], ['resources/js/app.jsx', 'resources/js/app.js'], true) && $step['action'] === 'merge') {
                return true;
            }
        }

        return false;
    }

    private function appendOwlAdminCssImport(string $basePath): void
    {
        $path = $basePath.'/resources/css/app.css';
        $contents = (string) file_get_contents($path);

        if (! str_contains($contents, 'owl-admin.css')) {
            file_put_contents($path, "@import './owl-admin.css';\n\n".$contents);
        }
    }
}
