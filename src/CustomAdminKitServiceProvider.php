<?php

namespace OwlSolutions\CustomAdminKit;

use Illuminate\Support\ServiceProvider;
use OwlSolutions\CustomAdminKit\Commands\DoctorCommand;
use OwlSolutions\CustomAdminKit\Commands\FrontendSetupCommand;
use OwlSolutions\CustomAdminKit\Commands\InstallCommand;
use OwlSolutions\CustomAdminKit\Commands\MakeAdminCommand;
use OwlSolutions\CustomAdminKit\Commands\RepairCommand;
use OwlSolutions\CustomAdminKit\Commands\SmokeCommand;
use OwlSolutions\CustomAdminKit\Commands\UninstallCommand;

class CustomAdminKitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/owl-admin-kit.php',
            'owl-admin-kit'
        );
    }

    public function boot(): void
    {
        $this->loadHostRoutesIfPresent();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/owl-admin-kit.php' => config_path('owl-admin-kit.php'),
            ], 'owl-admin-kit-config');

            $this->commands([
                DoctorCommand::class,
                FrontendSetupCommand::class,
                InstallCommand::class,
                MakeAdminCommand::class,
                RepairCommand::class,
                SmokeCommand::class,
                UninstallCommand::class,
            ]);
        }
    }

    protected function loadHostRoutesIfPresent(): void
    {
        $relative = config('owl-admin-kit.paths.routes_core', 'routes/owl-admin-core.php');
        $path = base_path((string) $relative);

        if (is_file($path)) {
            $this->loadRoutesFrom($path);
        }
    }
}
