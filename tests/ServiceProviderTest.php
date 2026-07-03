<?php

namespace OwlSolutions\CustomAdminKit\Tests;

class ServiceProviderTest extends PackageTestCase
{
    public function test_artisan_commands_are_registered(): void
    {
        $this->artisan('owl-admin:doctor --help')
            ->assertExitCode(0);

        $this->artisan('owl-admin:install --help')
            ->assertExitCode(0);

        $this->artisan('owl-admin:make-admin --help')
            ->assertExitCode(0);

        $this->artisan('owl-admin:repair --help')
            ->assertExitCode(0);

        $this->artisan('owl-admin:smoke --help')
            ->assertExitCode(0);

        $this->artisan('owl-admin:uninstall --help')
            ->assertExitCode(0);

        $this->artisan('owl-admin:frontend-setup --help')
            ->assertExitCode(0);
    }
}
