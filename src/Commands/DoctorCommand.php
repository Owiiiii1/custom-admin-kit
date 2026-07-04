<?php

namespace OwlSolutions\CustomAdminKit\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
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
use OwlSolutions\CustomAdminKit\Support\WebRoutesMerger;

class DoctorCommand extends BaseKitCommand
{
    protected $signature = 'owl-admin:doctor
                            {--preset=core : Preset to evaluate (core|admin)}';

    protected $description = 'Check environment and preset install readiness';

    public function handle(
        VersionChecker $versions,
        EnvironmentChecker $environment,
        DependencyChecker $dependencies,
        FrontendDependencyChecker $frontend,
        EmailVerificationChecker $emailVerification,
        EnvKeyChecker $envKeys,
        FileConflictChecker $conflicts,
        PublishMapResolver $publishMap,
        WebRoutesMerger $webRoutesMerger,
    ): int {
        $this->printBanner('Doctor');

        $preset = (string) $this->option('preset');

        if (! $publishMap->isPresetAvailable($preset)) {
            $this->error($publishMap->unavailablePresetMessage($preset) ?? "Preset [{$preset}] unavailable.");

            return self::FAILURE;
        }

        $basePath = base_path();

        $this->line(
            $preset === 'admin'
                ? '  <fg=cyan>→</> Admin preset installs generic auth/admin shell (no business domain modules).'
                : '  <fg=cyan>→</> Core preset does not install landing domain modules.'
        );
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
        if ($preset === 'admin') {
            $this->info('Admin preset checks:');
            $failures += $this->renderCheckResults($this->checkAdminPrerequisites($basePath));
            $this->newLine();
        }

        $this->info('Route include checks:');
        $routesAnalysis = $webRoutesMerger->analyze($basePath, $preset);
        $routeChecks = [];
        if ($routesAnalysis->action === \OwlSolutions\CustomAdminKit\Support\WebRoutesAnalysis::ACTION_MANUAL) {
            $routeChecks[] = \OwlSolutions\CustomAdminKit\Support\CheckResult::warn(
                'routes-web',
                'routes/web.php is non-standard for automatic owl-admin include.',
                'Use docs/merge-snippets/web.php',
            );
        } else {
            $routeChecks[] = \OwlSolutions\CustomAdminKit\Support\CheckResult::pass(
                'routes-web',
                'routes/web.php can be processed automatically.',
            );
        }
        $failures += $this->renderCheckResults($routeChecks);

        $this->newLine();
        $this->info('Required env keys:');
        $failures += $this->renderCheckResults($envKeys->check($basePath));

        $this->newLine();
        $this->info("Publish conflict checks (preset: {$preset}):");
        $failures += $this->renderCheckResults($conflicts->checkPublishPlan($basePath, $preset, false));

        $copyCount = count($publishMap->copyEntriesForPreset($preset));
        $this->line("  <fg=gray>→ {$copyCount} stub(s) in preset [{$preset}] publish map</>");

        $state = new InstallState($basePath.'/'.config('owl-admin-kit.state_file'));
        $this->newLine();
        $this->info('Install state:');
        if ($state->exists()) {
            $data = $state->read();
            $this->line('  <fg=green>✓</> Installed at '.($data['installed_at'] ?? 'unknown'));
        } else {
            $this->line("  <fg=yellow>!</> Not installed. Run: php artisan owl-admin:install --preset={$preset}</>");
        }

        $this->newLine();

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return list<\OwlSolutions\CustomAdminKit\Support\CheckResult>
     */
    private function checkAdminPrerequisites(string $basePath): array
    {
        $results = [];
        $usersMigration = glob($basePath.'/database/migrations/*create_users_table*.php') ?: [];

        $hasUsersTable = false;
        try {
            $hasUsersTable = Schema::hasTable('users');
        } catch (\Throwable) {
            $hasUsersTable = false;
        }

        if ($hasUsersTable) {
            $results[] = \OwlSolutions\CustomAdminKit\Support\CheckResult::pass('users-table', 'users table exists.');
        } elseif ($usersMigration !== []) {
            $results[] = \OwlSolutions\CustomAdminKit\Support\CheckResult::warn(
                'users-table',
                'users table not migrated yet.',
                'Run php artisan migrate before smoke checks.',
            );
        } else {
            $results[] = \OwlSolutions\CustomAdminKit\Support\CheckResult::fail(
                'users-table',
                'users table migration is missing.',
                'Restore default Laravel users migration.',
            );
        }

        $loginPage = $basePath.'/resources/js/Pages/Auth/Login.jsx';
        $results[] = File::exists($loginPage)
            ? \OwlSolutions\CustomAdminKit\Support\CheckResult::pass('auth-login-page', 'Auth/Login.jsx exists.')
            : \OwlSolutions\CustomAdminKit\Support\CheckResult::warn(
                'auth-login-page',
                'Auth/Login.jsx not found yet.',
                'Will be published by owl-admin:install --preset=admin.',
            );

        $results[] = class_exists(\Illuminate\Support\Facades\Http::class)
            ? \OwlSolutions\CustomAdminKit\Support\CheckResult::pass('http-client', 'Laravel HTTP client facade is available.')
            : \OwlSolutions\CustomAdminKit\Support\CheckResult::fail('http-client', 'Laravel HTTP client facade is missing.');

        $aiModelPath = $basePath.'/app/Models/AiProviderSetting.php';
        $results[] = File::exists($aiModelPath)
            ? \OwlSolutions\CustomAdminKit\Support\CheckResult::pass('ai-model', 'app/Models/AiProviderSetting.php exists.')
            : \OwlSolutions\CustomAdminKit\Support\CheckResult::warn(
                'ai-model',
                'app/Models/AiProviderSetting.php not found yet.',
                'Will be published by owl-admin:install --preset=admin.',
            );

        $aiMigration = glob($basePath.'/database/migrations/*create_ai_provider_settings_table*.php') ?: [];
        $results[] = $aiMigration !== []
            ? \OwlSolutions\CustomAdminKit\Support\CheckResult::pass('ai-migration', 'ai_provider_settings migration exists.')
            : \OwlSolutions\CustomAdminKit\Support\CheckResult::warn(
                'ai-migration',
                'ai_provider_settings migration not found yet.',
                'Will be published by owl-admin:install --preset=admin.',
            );

        if (File::exists($basePath.'/routes/owl-admin-pages.php')) {
            $results[] = Route::has('ai-settings.index')
                ? \OwlSolutions\CustomAdminKit\Support\CheckResult::pass('ai-route', 'ai-settings.index route is registered.')
                : \OwlSolutions\CustomAdminKit\Support\CheckResult::warn(
                    'ai-route',
                    'ai-settings.index route is not registered yet.',
                    'Run owl-admin:frontend-setup --preset=admin.',
                );
        } else {
            $results[] = \OwlSolutions\CustomAdminKit\Support\CheckResult::warn(
                'ai-route',
                'routes/owl-admin-pages.php is missing, route check skipped.',
                'Run owl-admin:frontend-setup --preset=admin.',
            );
        }

        return $results;
    }
}
