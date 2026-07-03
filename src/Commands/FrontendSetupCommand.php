<?php

namespace OwlSolutions\CustomAdminKit\Commands;

use OwlSolutions\CustomAdminKit\Support\FileBackupManager;
use OwlSolutions\CustomAdminKit\Support\FrontendDependencyChecker;
use OwlSolutions\CustomAdminKit\Support\FrontendSetupPlanner;
use OwlSolutions\CustomAdminKit\Support\FrontendSetupResult;
use OwlSolutions\CustomAdminKit\Support\InertiaAppAnalysis;
use OwlSolutions\CustomAdminKit\Support\InertiaAppMerger;
use OwlSolutions\CustomAdminKit\Support\InertiaMiddlewareMerger;
use OwlSolutions\CustomAdminKit\Support\PackageJsonMergePlan;
use OwlSolutions\CustomAdminKit\Support\PackageJsonMerger;
use OwlSolutions\CustomAdminKit\Support\PublishMapResolver;
use OwlSolutions\CustomAdminKit\Support\ViteConfigAnalysis;
use OwlSolutions\CustomAdminKit\Support\ViteConfigMerger;

class FrontendSetupCommand extends BaseKitCommand
{
    protected $signature = 'owl-admin:frontend-setup
                            {--preset=core : Frontend setup preset (v0.2: core only)}
                            {--dry-run : Show merge plan without writing files}
                            {--backup : Backup host files before merge}
                            {--force : Apply merges even when files already exist}
                            {--install-npm : Install missing npm dependencies}
                            {--run-build : Run npm run build after setup}
                            {--strict : Fail when vite.config.js is missing the Inertia app entry and cannot auto-merge}';

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
        $strict = (bool) $this->option('strict');
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
        $this->renderPlan($result, $installNpm, $runBuild, $dryRun, $backup, $force);
        $this->renderPackageJsonMerge($result->packageJsonMerge, $dryRun, $backup, $force);
        $this->renderViteConfigMerge($result->viteConfigAnalysis, $dryRun, $backup, $force);
        $this->renderAppJsxMerge($result->inertiaAppAnalysis, $dryRun, $backup, $force);

        if ($dryRun) {
            $this->info('Dry run complete — no files were modified.');

            return self::SUCCESS;
        }

        if ($this->packageJsonRequiresProtection($result) && ! $backup && ! $force) {
            $this->error('package.json changes require --backup or --force.');

            return self::FAILURE;
        }

        if ($this->viteConfigRequiresProtection($result) && ! $backup && ! $force) {
            $this->error('vite.config.js changes require --backup or --force.');

            return self::FAILURE;
        }

        if ($this->appJsxRequiresProtection($result) && ! $backup && ! $force) {
            $this->error('app.jsx creation requires --backup or --force.');

            return self::FAILURE;
        }

        if ($strict && $result->viteConfigAnalysis instanceof ViteConfigAnalysis && $result->viteConfigAnalysis->failsStrictCheck()) {
            $this->error('vite.config.js is missing the Inertia app entry and cannot be auto-merged. Use the manual snippet or fix inputs manually.');

            return self::FAILURE;
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

        if ($this->shouldApply($result, 'package.json') && $result->packageJsonMerge instanceof PackageJsonMergePlan) {
            if (! $packageJsonMerger->apply($basePath, $result->packageJsonMerge)) {
                $this->error('package.json merge failed.');

                return self::FAILURE;
            }

            $this->line('  <fg=green>→</> package.json updated.');
        }

        if ($this->shouldApply($result, 'vite.config.js') && $result->viteConfigAnalysis instanceof ViteConfigAnalysis) {
            if (! $viteConfigMerger->apply($basePath, $result->viteConfigAnalysis)) {
                $this->error('vite.config.js merge failed.');

                return self::FAILURE;
            }

            $this->line('  <fg=green>→</> vite.config.js updated.');
        }

        if ($this->shouldApply($result, 'resources/css/app.css')) {
            $this->appendOwlAdminCssImport($basePath);
            $this->line('  <fg=green>→</> resources/css/app.css updated.');
        }

        if ($this->shouldApplyCreate($result, 'resources/js/app.jsx') && $result->inertiaAppAnalysis instanceof InertiaAppAnalysis) {
            if (! $inertiaAppMerger->apply($basePath, $result->inertiaAppAnalysis)) {
                $this->error('app.jsx creation failed.');

                return self::FAILURE;
            }

            $this->line('  <fg=green>→</> resources/js/app.jsx created.');
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

    private function renderPlan(
        FrontendSetupResult $result,
        bool $installNpm,
        bool $runBuild,
        bool $dryRun,
        bool $backup,
        bool $force,
    ): void {
        $this->info('Frontend merge plan:');

        foreach ($result->planSteps as $step) {
            if (in_array($step['file'], ['package.json', 'vite.config.js', 'resources/js/app.jsx'], true)) {
                continue;
            }

            $icon = match ($step['action']) {
                'skip' => '<fg=green>→</>',
                'merge', 'create' => '<fg=cyan>→</>',
                'blocked' => '<fg=red>✗</>',
                default => '<fg=gray>→</>',
            };

            $this->line("  {$icon} [{$step['action']}] {$step['file']}: {$step['detail']}");
        }

        if ($installNpm && ! $dryRun && ($backup || $force)) {
            $this->line('  <fg=cyan>→</> --install-npm will run npm install for missing packages.');
        }

        if ($runBuild && ! $dryRun && ($backup || $force)) {
            $this->line('  <fg=cyan>→</> --run-build will run npm run build after merges.');
        }

        $this->newLine();
    }

    private function renderPackageJsonMerge(
        ?PackageJsonMergePlan $plan,
        bool $dryRun,
        bool $backup,
        bool $force,
    ): void {
        $this->info('package.json merge:');

        if (! $plan instanceof PackageJsonMergePlan) {
            $this->line('  <fg=gray>→</> unavailable');

            return;
        }

        if (! $plan->hasChanges()) {
            $this->line('  <fg=green>→</> missing dependencies: none');
            $this->line('  <fg=green>→</> missing devDependencies: none');
            $this->line('  <fg=gray>→</> will write: no');

            return;
        }

        $this->line('  <fg=yellow>→</> missing dependencies: '.($plan->missingDependencies === []
            ? 'none'
            : implode(', ', array_keys($plan->missingDependencies))));
        $this->line('  <fg=yellow>→</> missing devDependencies: '.($plan->missingDevDependencies === []
            ? 'none'
            : implode(', ', array_keys($plan->missingDevDependencies))));

        $willWrite = ! $dryRun && ($backup || $force);
        $this->line('  <fg=gray>→</> will write: '.($dryRun ? 'no (dry-run)' : ($willWrite ? 'yes' : 'no')));

        if (! $dryRun && ! $backup && ! $force && $plan->hasChanges()) {
            $this->line('  <fg=red>→</> package.json changes require --backup or --force.');
        }

        $this->newLine();
    }

    private function renderViteConfigMerge(
        ?ViteConfigAnalysis $analysis,
        bool $dryRun,
        bool $backup,
        bool $force,
    ): void {
        $this->info('vite.config.js:');

        if (! $analysis instanceof ViteConfigAnalysis) {
            $this->line('  <fg=gray>→</> unavailable');

            return;
        }

        $this->line('  <fg=gray>→</> status: '.$analysis->status);
        $this->line('  <fg=gray>→</> missing inputs: '.($analysis->missingInputs === []
            ? 'none'
            : implode(', ', $analysis->missingInputs)));
        $this->line('  <fg=gray>→</> action: '.$analysis->action);

        if ($analysis->requiresManualMerge() && $analysis->manualSnippetPath !== null) {
            $this->line('  <fg=yellow>→</> manual snippet: '.$this->relativeManualSnippetPath());
        }

        if ($analysis->canAutoMerge()) {
            $willWrite = ! $dryRun && ($backup || $force);
            $this->line('  <fg=gray>→</> will write: '.($dryRun ? 'no (dry-run)' : ($willWrite ? 'yes' : 'no')));

            if (! $dryRun && ! $backup && ! $force) {
                $this->line('  <fg=red>→</> vite.config.js changes require --backup or --force.');
            }
        } elseif ($analysis->action === ViteConfigAnalysis::ACTION_SKIP) {
            $this->line('  <fg=gray>→</> will write: no');
        } else {
            $this->line('  <fg=gray>→</> will write: no (manual merge required)');
        }

        $this->newLine();
    }

    private function renderAppJsxMerge(
        ?InertiaAppAnalysis $analysis,
        bool $dryRun,
        bool $backup,
        bool $force,
    ): void {
        $this->info('app.jsx:');

        if (! $analysis instanceof InertiaAppAnalysis) {
            $this->line('  <fg=gray>→</> unavailable');

            return;
        }

        $this->line('  <fg=gray>→</> status: '.$analysis->status);
        $this->line('  <fg=gray>→</> action: '.$analysis->action);
        $this->line('  <fg=gray>→</> reason: '.$analysis->reason);

        if ($analysis->requiresManualMerge()) {
            $this->line('  <fg=yellow>→</> manual snippet: docs/merge-snippets/app.jsx');
        }

        if ($analysis->canAutoCreate()) {
            $willWrite = ! $dryRun && ($backup || $force);
            $this->line('  <fg=gray>→</> will write: '.($dryRun ? 'no (dry-run)' : ($willWrite ? 'yes' : 'no')));

            if (! $dryRun && ! $backup && ! $force) {
                $this->line('  <fg=red>→</> app.jsx creation requires --backup or --force.');
            }
        } elseif ($analysis->action === InertiaAppAnalysis::ACTION_OK) {
            $this->line('  <fg=gray>→</> will write: no');
        } else {
            $this->line('  <fg=gray>→</> will write: no (manual merge required)');
        }

        $this->newLine();
    }

    private function relativeManualSnippetPath(): string
    {
        return 'docs/merge-snippets/vite.config.js';
    }

    private function packageJsonRequiresProtection(FrontendSetupResult $result): bool
    {
        return $result->packageJsonMerge instanceof PackageJsonMergePlan
            && $result->packageJsonMerge->hasChanges();
    }

    private function viteConfigRequiresProtection(FrontendSetupResult $result): bool
    {
        return $result->viteConfigAnalysis instanceof ViteConfigAnalysis
            && $result->viteConfigAnalysis->canAutoMerge();
    }

    private function appJsxRequiresProtection(FrontendSetupResult $result): bool
    {
        return $result->inertiaAppAnalysis instanceof InertiaAppAnalysis
            && $result->inertiaAppAnalysis->canAutoCreate();
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

    private function shouldApplyCreate(FrontendSetupResult $result, string $file): bool
    {
        foreach ($result->planSteps as $step) {
            if ($step['file'] === $file && $step['action'] === 'create') {
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
