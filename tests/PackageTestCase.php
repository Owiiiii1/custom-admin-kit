<?php

namespace OwlSolutions\CustomAdminKit\Tests;

use Orchestra\Testbench\TestCase;
use OwlSolutions\CustomAdminKit\CustomAdminKitServiceProvider;

abstract class PackageTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            CustomAdminKitServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('owl-admin-kit.state_file', 'storage/app/owl-admin-kit.json');
        $app['config']->set('owl-admin-kit.paths.config', 'config/owl-admin.php');
    }
}
