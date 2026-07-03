<?php

namespace OwlSolutions\CustomAdminKit\Tests;

use OwlSolutions\CustomAdminKit\Support\FrontendDependencyChecker;
use OwlSolutions\CustomAdminKit\Support\PublishMapResolver;

class FrontendDependencyCheckerTest extends PackageTestCase
{
    public function test_core_preset_requires_packages_from_stub_imports(): void
    {
        $checker = new FrontendDependencyChecker(new PublishMapResolver());

        $this->assertTrue($checker->requiresFrontend('core'));

        $required = $checker->requiredPackages('core');

        $this->assertContains('react', $required);
        $this->assertContains('react-dom', $required);
        $this->assertContains('@inertiajs/react', $required);
        $this->assertContains('lucide-react', $required);
        $this->assertContains('radix-ui', $required);
        $this->assertContains('class-variance-authority', $required);
        $this->assertContains('clsx', $required);
        $this->assertContains('tailwind-merge', $required);
        $this->assertContains('vite', $required);
        $this->assertContains('@vitejs/plugin-react', $required);
        $this->assertContains('laravel-vite-plugin', $required);
        $this->assertContains('tailwindcss', $required);
        $this->assertNotContains('ziggy-js', $required);
        $this->assertNotContains('@tailwindcss/vite', $required);
    }

    public function test_detects_missing_packages_on_fresh_laravel_package_json(): void
    {
        $basePath = sys_get_temp_dir().'/owl-admin-kit-frontend-'.uniqid('', true);
        mkdir($basePath, 0777, true);
        file_put_contents($basePath.'/package.json', json_encode([
            'private' => true,
            'devDependencies' => [
                'vite' => '^8.0.0',
            ],
        ], JSON_PRETTY_PRINT));

        $checker = new FrontendDependencyChecker(new PublishMapResolver());
        $missing = $checker->missingPackages($basePath, 'core');

        $this->assertContains('react', $missing);
        $this->assertNotContains('vite', $missing);

        $command = $checker->buildInstallCommand($missing);
        $this->assertStringStartsWith('npm install ', $command);
        $this->assertStringContainsString('react', $command);

        @unlink($basePath.'/package.json');
        @rmdir($basePath);
    }

    public function test_strict_check_fails_and_non_strict_warns(): void
    {
        $basePath = sys_get_temp_dir().'/owl-admin-kit-frontend-'.uniqid('', true);
        mkdir($basePath, 0777, true);
        file_put_contents($basePath.'/package.json', '{}');

        $checker = new FrontendDependencyChecker(new PublishMapResolver());

        $strict = $checker->check($basePath, 'core', strict: true);
        $this->assertFalse($strict[0]->passed);
        $this->assertSame('fail', $strict[0]->severity);

        $warn = $checker->check($basePath, 'core', strict: false);
        $this->assertTrue($warn[0]->passed);
        $this->assertSame('warn', $warn[0]->severity);

        @unlink($basePath.'/package.json');
        @rmdir($basePath);
    }
}
