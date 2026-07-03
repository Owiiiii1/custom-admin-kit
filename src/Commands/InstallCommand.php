<?php

namespace OwlSolutions\CustomAdminKit\Commands;

use Illuminate\Support\Facades\File;
use OwlSolutions\CustomAdminKit\Installers\StubPublishInstaller;
use OwlSolutions\CustomAdminKit\Support\AdminUserCreator;
use OwlSolutions\CustomAdminKit\Support\AdminUserCredentialResolver;
use OwlSolutions\CustomAdminKit\Support\EnvironmentChecker;
use OwlSolutions\CustomAdminKit\Support\FileConflictChecker;
use OwlSolutions\CustomAdminKit\Support\FrontendDependencyChecker;
use OwlSolutions\CustomAdminKit\Support\InstallReport;
use OwlSolutions\CustomAdminKit\Support\InstallState;
use OwlSolutions\CustomAdminKit\Support\PublishMapResolver;
use OwlSolutions\CustomAdminKit\Support\RequiredEnvChecker;
use OwlSolutions\CustomAdminKit\Support\VersionChecker;

class InstallCommand extends BaseKitCommand
{
    protected $signature = 'owl-admin:install
                            {--preset=core : Install preset (v0.1: core only)}
                            {--force : Overwrite existing published files}
                            {--backup : Backup existing files before overwrite}
                            {--dry-run : Show publish plan without writing files}
                            {--install-frontend-deps : Run npm install for missing frontend packages}
                            {--require-frontend-deps : Abort install when frontend npm packages are missing}
                            {--seed : Create admin user after install (requires OWL_ADMIN_EMAIL / OWL_ADMIN_PASSWORD or interactive mode)}
                            {--migrate : Run php artisan migrate after publishing stubs}
                            {--no-smoke : Skip post-install smoke checks}';

    protected $description = 'Install the core admin kit (v0.1) into this Laravel application';

    public function handle(
        VersionChecker $versions,
        EnvironmentChecker $environment,
        FileConflictChecker $conflicts,
        FrontendDependencyChecker $frontend,
        RequiredEnvChecker $seedEnv,
        AdminUserCreator $adminUserCreator,
        StubPublishInstaller $publisher,
        PublishMapResolver $publishMap,
    ): int {
        $this->printBanner('Install');

        $preset = (string) $this->option('preset');

        if (! $publishMap->isPresetAvailable($preset)) {
            $message = $publishMap->unavailablePresetMessage($preset)
                ?? "Preset [{$preset}] is not available in v0.1.";
            $this->error($message);

            return self::FAILURE;
        }

        $basePath = base_path();
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $backup = (bool) $this->option('backup');
        $installFrontendDeps = (bool) $this->option('install-frontend-deps');
        $requireFrontendDeps = (bool) $this->option('require-frontend-deps');
        $withSeed = (bool) $this->option('seed');
        $runMigrate = (bool) $this->option('migrate');
        $skipSmoke = (bool) $this->option('no-smoke');
        $interactive = ! $this->option('no-interaction');
        $credentialResolver = new AdminUserCredentialResolver($this);

        $this->line('  <fg=cyan>→</> Core preset does not install landing domain modules.');

        $preflight = [
            ...$versions->check(),
            ...$environment->check($basePath),
        ];

        $this->info('Preflight checks:');
        $failures = $this->renderCheckResults($preflight);

        $this->newLine();
        $this->info("Conflict checks (preset: {$preset}):");
        $failures += $this->renderCheckResults($conflicts->checkPublishPlan($basePath, $preset, $force || $backup));

        if ($failures > 0) {
            $this->error('Install aborted due to failed checks.');

            return self::FAILURE;
        }

        $frontendBlocked = $this->renderFrontendPreflight(
            $frontend,
            $basePath,
            $preset,
            $installFrontendDeps,
            strict: $requireFrontendDeps && ! $dryRun,
        );

        if ($frontendBlocked && ! $dryRun) {
            $this->error('Install aborted: missing frontend npm packages.');
            $this->line('Install manually or re-run with --install-frontend-deps.');

            return self::FAILURE;
        }

        $seedBlocked = $this->renderAdminSeedPreflight(
            $seedEnv,
            $credentialResolver,
            $basePath,
            $withSeed,
            $interactive,
            $dryRun,
        );

        if ($seedBlocked && ! $dryRun) {
            $this->error('Install aborted: --seed requires OWL_ADMIN_EMAIL and OWL_ADMIN_PASSWORD in non-interactive mode.');
            $this->line('Set env keys in .env or run without -n to be prompted.');

            return self::FAILURE;
        }

        if ($installFrontendDeps && ! $dryRun) {
            $missing = $frontend->missingPackages($basePath, $preset);

            if ($missing !== []) {
                $this->info('Installing missing npm packages...');
                $this->line('  <fg=gray>→ '.$frontend->buildInstallCommand($missing).'</>');

                $result = $frontend->installPackages($basePath, $missing);

                if ($result->output !== '') {
                    $this->line($result->output);
                }

                if (! $result->successful) {
                    $this->error('npm install failed — install aborted before publishing stubs.');

                    return self::FAILURE;
                }

                $stillMissing = $frontend->missingPackages($basePath, $preset);

                if ($stillMissing !== []) {
                    $this->error('Some npm packages are still missing: '.implode(', ', $stillMissing));

                    return self::FAILURE;
                }

                $this->info('Frontend npm packages installed.');
                $this->newLine();
            }
        }

        $stubsPath = dirname(__DIR__, 2).'/stubs';
        $report = new InstallReport();

        if ($dryRun) {
            $this->warn('Dry run — no files will be written.');
        }

        $this->info('Publishing core stubs...');
        $published = $publisher->install($stubsPath, $basePath, $preset, $force, $dryRun, $backup, $report);

        foreach ($report->steps() as $step) {
            $this->line("  <fg=green>→</> {$step}");
        }

        foreach ($report->warnings() as $warning) {
            $this->line("  <fg=yellow>!</> {$warning}");
        }

        if ($dryRun) {
            if ($withSeed && config('owl-admin-kit.admin_user.enabled', true)) {
                $this->info('Dry run — admin user would be created after install when credentials are available.');
            }

            $this->info('Dry run complete.');

            return self::SUCCESS;
        }

        $adminSeedResult = null;

        if ($withSeed && config('owl-admin-kit.admin_user.enabled', true)) {
            $resolved = $credentialResolver->resolve($interactive);

            if (! $resolved->success || $resolved->credentials === null) {
                $this->error($resolved->error ?? 'Admin user was not created.');

                return self::FAILURE;
            }

            try {
                $user = $adminUserCreator->create($resolved->credentials);
                $adminSeedResult = [
                    'email' => $user->email,
                    'id' => $user->getKey(),
                    'password_generated' => $resolved->credentials->passwordWasGenerated,
                ];
                $this->info('Admin user created: '.$user->email);

                if ($resolved->credentials->passwordWasGenerated) {
                    $this->warn('Generated password (local dev): '.$resolved->credentials->password);
                }
            } catch (\RuntimeException $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }
        }

        $statePath = $basePath.'/'.config('owl-admin-kit.state_file');
        $reportPath = $basePath.'/'.config('owl-admin-kit.report_file');

        $missingAfter = $frontend->missingPackages($basePath, $preset);

        (new InstallState($statePath))->write([
            'version' => config('owl-admin-kit.version', '0.1.0'),
            'installed_at' => now()->toIso8601String(),
            'package' => 'owlsolutions/custom-admin-kit',
            'preset' => $preset,
            'published_files' => $published,
            'published_count' => count($published),
            'admin_seeded' => $adminSeedResult !== null,
        ]);

        File::ensureDirectoryExists(dirname($reportPath));
        File::put($reportPath, json_encode([
            'installed_at' => now()->toIso8601String(),
            'preset' => $preset,
            'published_files' => $published,
            'steps' => $report->steps(),
            'warnings' => $report->warnings(),
            'frontend' => [
                'required' => $frontend->requiresFrontend($preset),
                'missing_after_install' => $missingAfter,
                'suggested_npm_command' => $missingAfter !== []
                    ? $frontend->buildInstallCommand($missingAfter)
                    : null,
            ],
            'admin_user' => $adminSeedResult,
            'merge_required' => [
                'resources/js/app.jsx',
                'vite.config.js',
                'package.json',
                'routes/web.php',
                'app/Models/User.php',
                'HandleInertiaRequests share owlAdmin props',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        $this->newLine();
        $this->info('Core admin kit installed successfully.');
        $this->line('Next: merge vite.config.js and app.jsx, share owlAdmin in HandleInertiaRequests, register host routes.');

        if (! $withSeed) {
            $this->line('Create admin: php artisan owl-admin:make-admin');
        }

        if ($runMigrate) {
            $this->newLine();
            $this->info('Running migrations...');
            $migrateCode = $this->call('migrate', ['--force' => true]);

            if ($migrateCode !== self::SUCCESS) {
                $this->error('Migrate failed after install.');

                return self::FAILURE;
            }
        }

        if (! $skipSmoke) {
            $this->newLine();
            $this->info('Running post-install smoke checks...');
            $smokeCode = $this->call('owl-admin:smoke', ['--preset' => $preset]);

            if ($smokeCode !== self::SUCCESS) {
                $this->error('Smoke checks failed after install.');

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
