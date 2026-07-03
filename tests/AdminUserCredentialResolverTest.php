<?php

namespace OwlSolutions\CustomAdminKit\Tests;

use OwlSolutions\CustomAdminKit\Support\AdminUserCredentialResolver;
use OwlSolutions\CustomAdminKit\Support\RequiredEnvChecker;

class AdminUserCredentialResolverTest extends PackageTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'owl-admin-kit.admin_user.enabled' => true,
            'owl-admin-kit.admin_user.name' => 'Admin',
            'owl-admin-kit.admin_user.email' => null,
            'owl-admin-kit.admin_user.password_env' => 'OWL_ADMIN_PASSWORD',
            'owl-admin-kit.admin_user.default_password_allowed' => false,
        ]);
    }

    public function test_rejects_audit_hardcoded_credentials(): void
    {
        config(['owl-admin-kit.admin_user.email' => 'admin@admin.com']);

        putenv('OWL_ADMIN_PASSWORD=secret123');
        $_ENV['OWL_ADMIN_PASSWORD'] = 'secret123';

        $resolver = new AdminUserCredentialResolver;
        $result = $resolver->resolve(interactive: false);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('admin@admin.com', (string) $result->error);
    }

    public function test_rejects_password_admin_literal(): void
    {
        config(['owl-admin-kit.admin_user.email' => 'owner@example.com']);
        putenv('OWL_ADMIN_PASSWORD=admin');
        $_ENV['OWL_ADMIN_PASSWORD'] = 'admin';

        $resolver = new AdminUserCredentialResolver;
        $result = $resolver->resolve(interactive: false);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('"admin"', (string) $result->error);
    }

    public function test_resolves_from_env(): void
    {
        config(['owl-admin-kit.admin_user.email' => 'owner@example.com']);
        putenv('OWL_ADMIN_PASSWORD=secret123');
        $_ENV['OWL_ADMIN_PASSWORD'] = 'secret123';

        $resolver = new AdminUserCredentialResolver;
        $result = $resolver->resolve(interactive: false);

        $this->assertTrue($result->success);
        $this->assertSame('owner@example.com', $result->credentials?->email);
    }

    public function test_required_env_checker_optional_without_seed(): void
    {
        $dir = sys_get_temp_dir().'/owl-seed-env-'.uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir.'/.env', "APP_KEY=base64:test\n");

        config([
            'owl-admin-kit.admin_user.enabled' => true,
            'owl-admin-kit.admin_seed_env' => [
                ['key' => 'OWL_ADMIN_EMAIL', 'description' => 'email'],
                ['key' => 'OWL_ADMIN_PASSWORD', 'description' => 'password'],
            ],
        ]);

        $checker = new RequiredEnvChecker;
        $results = $checker->checkAdminSeed($dir, forSeed: false, strict: false, interactive: true);

        $this->assertCount(2, $results);
        $this->assertTrue($results[0]->isWarning());

        @unlink($dir.'/.env');
        @rmdir($dir);
    }

    public function test_required_env_checker_fails_strict_non_interactive(): void
    {
        $dir = sys_get_temp_dir().'/owl-seed-env-'.uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir.'/.env', "APP_KEY=base64:test\n");

        config([
            'owl-admin-kit.admin_user.enabled' => true,
            'owl-admin-kit.admin_seed_env' => [
                ['key' => 'OWL_ADMIN_EMAIL', 'description' => 'email'],
                ['key' => 'OWL_ADMIN_PASSWORD', 'description' => 'password'],
            ],
        ]);

        $checker = new RequiredEnvChecker;
        $results = $checker->checkAdminSeed($dir, forSeed: true, strict: true, interactive: false);

        $this->assertFalse($results[0]->passed);

        @unlink($dir.'/.env');
        @rmdir($dir);
    }
}
