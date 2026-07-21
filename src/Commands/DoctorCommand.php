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
                ? '  <fg=cyan>→</> Admin preset installs generic auth/admin shell with starter CRM modules (no client-specific domain modules).'
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

        $results[] = class_exists(\SergiX44\Nutgram\Nutgram::class)
            ? \OwlSolutions\CustomAdminKit\Support\CheckResult::pass('nutgram', 'nutgram/nutgram is installed.')
            : \OwlSolutions\CustomAdminKit\Support\CheckResult::warn(
                'nutgram',
                'nutgram/nutgram is not installed in the host app yet.',
                'Require owlsolutions/custom-admin-kit (pulls nutgram) or composer require nutgram/nutgram.',
            );

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

        $telegramModelPath = $basePath.'/app/Models/TelegramBotSetting.php';
        $results[] = File::exists($telegramModelPath)
            ? \OwlSolutions\CustomAdminKit\Support\CheckResult::pass('telegram-model', 'app/Models/TelegramBotSetting.php exists.')
            : \OwlSolutions\CustomAdminKit\Support\CheckResult::warn(
                'telegram-model',
                'app/Models/TelegramBotSetting.php not found yet.',
                'Will be published by owl-admin:install --preset=admin.',
            );

        $telegramMigration = glob($basePath.'/database/migrations/*create_telegram_bot_settings_table*.php') ?: [];
        $results[] = $telegramMigration !== []
            ? \OwlSolutions\CustomAdminKit\Support\CheckResult::pass('telegram-migration', 'telegram_bot_settings migration exists.')
            : \OwlSolutions\CustomAdminKit\Support\CheckResult::warn(
                'telegram-migration',
                'telegram_bot_settings migration not found yet.',
                'Will be published by owl-admin:install --preset=admin.',
            );

        $telegramTokenConfigured = false;
        try {
            if (File::exists($telegramModelPath) && Schema::hasTable('telegram_bot_settings')) {
                $telegramTokenConfigured = \App\Models\TelegramBotSetting::query()
                    ->whereNotNull('bot_token')
                    ->exists();
            }
        } catch (\Throwable) {
            $telegramTokenConfigured = false;
        }
        $results[] = $telegramTokenConfigured
            ? \OwlSolutions\CustomAdminKit\Support\CheckResult::pass('telegram-token', 'Telegram bot token is configured.')
            : \OwlSolutions\CustomAdminKit\Support\CheckResult::warn(
                'telegram-token',
                'Telegram bot token is not configured (optional on clean install).',
                'Configure token in Settings → Telegram after install.',
            );

        foreach ([
            'app/Models/Customer.php' => 'crm-customer-model',
            'app/Models/Service.php' => 'crm-service-model',
            'app/Models/Staff.php' => 'crm-staff-model',
            'app/Models/Order.php' => 'crm-order-model',
            'resources/js/Pages/Customers/Index.jsx' => 'crm-customers-page',
            'resources/js/Pages/Services/Index.jsx' => 'crm-services-page',
            'resources/js/Pages/Staff/Index.jsx' => 'crm-staff-page',
            'resources/js/Pages/Orders/Index.jsx' => 'crm-orders-page',
            'resources/js/Pages/Calendar/Index.jsx' => 'crm-calendar-page',
        ] as $relativePath => $checkName) {
            $absolutePath = $basePath.'/'.$relativePath;
            $results[] = File::exists($absolutePath)
                ? \OwlSolutions\CustomAdminKit\Support\CheckResult::pass($checkName, "{$relativePath} exists.")
                : \OwlSolutions\CustomAdminKit\Support\CheckResult::warn(
                    $checkName,
                    "{$relativePath} not found yet.",
                    'Will be published by owl-admin:install --preset=admin.',
                );
        }

        foreach ([
            'customers',
            'services',
            'staff',
            'orders',
            'order_staff',
        ] as $tableName) {
            $exists = false;
            try {
                $exists = Schema::hasTable($tableName);
            } catch (\Throwable) {
                $exists = false;
            }

            $results[] = $exists
                ? \OwlSolutions\CustomAdminKit\Support\CheckResult::pass("crm-table-{$tableName}", "{$tableName} table exists.")
                : \OwlSolutions\CustomAdminKit\Support\CheckResult::warn(
                    "crm-table-{$tableName}",
                    "{$tableName} table not migrated yet.",
                    'Run php artisan migrate before smoke checks.',
                );
        }

        if (File::exists($basePath.'/routes/owl-admin-pages.php')) {
            $results[] = Route::has('ai-settings.index')
                ? \OwlSolutions\CustomAdminKit\Support\CheckResult::pass('ai-route', 'ai-settings.index route is registered.')
                : \OwlSolutions\CustomAdminKit\Support\CheckResult::warn(
                    'ai-route',
                    'ai-settings.index route is not registered yet.',
                    'Run owl-admin:frontend-setup --preset=admin.',
                );

            $results[] = Route::has('telegram.webhook')
                ? \OwlSolutions\CustomAdminKit\Support\CheckResult::pass('telegram-webhook-route', 'telegram.webhook route is registered.')
                : \OwlSolutions\CustomAdminKit\Support\CheckResult::warn(
                    'telegram-webhook-route',
                    'telegram.webhook route is not registered yet.',
                    'Run owl-admin:frontend-setup --preset=admin.',
                );

            foreach ([
                'customers.index',
                'services.index',
                'staff.index',
                'orders.index',
                'calendar.index',
            ] as $routeName) {
                $results[] = Route::has($routeName)
                    ? \OwlSolutions\CustomAdminKit\Support\CheckResult::pass("crm-route-{$routeName}", "{$routeName} route is registered.")
                    : \OwlSolutions\CustomAdminKit\Support\CheckResult::warn(
                        "crm-route-{$routeName}",
                        "{$routeName} route is not registered yet.",
                        'Run owl-admin:frontend-setup --preset=admin.',
                    );
            }
        } else {
            $results[] = \OwlSolutions\CustomAdminKit\Support\CheckResult::warn(
                'ai-route',
                'routes/owl-admin-pages.php is missing, route check skipped.',
                'Run owl-admin:frontend-setup --preset=admin.',
            );
            $results[] = \OwlSolutions\CustomAdminKit\Support\CheckResult::warn(
                'crm-routes',
                'routes/owl-admin-pages.php is missing, CRM route checks skipped.',
                'Run owl-admin:frontend-setup --preset=admin.',
            );
        }

        return $results;
    }
}
