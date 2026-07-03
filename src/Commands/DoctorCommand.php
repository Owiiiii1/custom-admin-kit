<?php

namespace OwlSolutions\CustomAdminKit\Commands;

use OwlSolutions\CustomAdminKit\Support\DependencyChecker;
use OwlSolutions\CustomAdminKit\Support\EmailVerificationChecker;
use OwlSolutions\CustomAdminKit\Support\EnvironmentChecker;
use OwlSolutions\CustomAdminKit\Support\EnvKeyChecker;
use OwlSolutions\CustomAdminKit\Support\FileConflictChecker;
use OwlSolutions\CustomAdminKit\Support\FrontendDependencyChecker;
use OwlSolutions\CustomAdminKit\Support\InstallState;
use OwlSolutions\CustomAdminKit\Support\PublishMapResolver;
use OwlSolutions\CustomAdminKit\Support\RequiredEnvChecker;
use OwlSolutions\CustomAdminKit\Support\VersionChecker;

class DoctorCommand extends BaseKitCommand
{
    protected $signature = 'owl-admin:doctor
                            {--preset=core : Preset to evaluate (v0.1: core only)}';

    protected $description = 'Check environment and core kit install readiness';

    public function handle(
        VersionChecker $versions,
        EnvironmentChecker $environment,
        DependencyChecker $dependencies,
        FrontendDependencyChecker $frontend,
        EmailVerificationChecker $emailVerification,
        EnvKeyChecker $envKeys,
        FileConflictChecker $conflicts,
        PublishMapResolver $publishMap,
    ): int {
        $this->printBanner('Doctor');

        $preset = (string) $this->option('preset');

        if (! $publishMap->isPresetAvailable($preset)) {
            $this->error($publishMap->unavailablePresetMessage($preset) ?? "Preset [{$preset}] unavailable.");

            return self::FAILURE;
        }

        $basePath = base_path();

        $this->line('  <fg=cyan>→</> Core preset does not install landing domain modules.');
        $this->newLine();

        $this->info('Version checks:');
        $failures = $this->renderCheckResults($versions->check());

        $this->newLine();
        $this->info('Environment checks:');
        $failures += $this->renderCheckResults($environment->check($basePath));

        $this->newLine();
        $this->info('Recommended host dependencies (core JSX pages):');
        $failures += $this->renderCheckResults($dependencies->check($basePath, strict: false));

        if ($frontend->requiresFrontend($preset)) {
            $this->newLine();
            $this->info('Frontend npm dependencies (core preset):');
            $failures += $this->renderCheckResults($frontend->check($basePath, $preset, strict: false));
        }

        $this->newLine();
        $this->info('Admin user env (required only with --seed):');
        $failures += $this->renderCheckResults(
            app(RequiredEnvChecker::class)->checkAdminSeed($basePath, false, false, true),
        );

        if (config('owl-admin-kit.email_verification.enabled')) {
            $this->newLine();
            $this->info('Email verification (enabled):');
            $failures += $this->renderCheckResults($emailVerification->check());
        }

        $this->newLine();
        $this->info('Required env keys:');
        $failures += $this->renderCheckResults($envKeys->check($basePath));

        $this->newLine();
        $this->info("Publish conflict checks (preset: {$preset}):");
        $failures += $this->renderCheckResults($conflicts->checkPublishPlan($basePath, $preset, false));

        $copyCount = count($publishMap->copyEntriesForPreset($preset));
        $this->line("  <fg=gray>→ {$copyCount} core stub(s) in publish map</>");

        $state = new InstallState($basePath.'/'.config('owl-admin-kit.state_file'));
        $this->newLine();
        $this->info('Install state:');
        if ($state->exists()) {
            $data = $state->read();
            $this->line('  <fg=green>✓</> Installed at '.($data['installed_at'] ?? 'unknown'));
        } else {
            $this->line('  <fg=yellow>!</> Not installed. Run: php artisan owl-admin:install --preset=core</>');
        }

        $this->newLine();

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}
