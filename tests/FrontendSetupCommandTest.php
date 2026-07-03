<?php

namespace OwlSolutions\CustomAdminKit\Tests;

use OwlSolutions\CustomAdminKit\Support\FrontendSetupPlanner;
use OwlSolutions\CustomAdminKit\Support\PublishMapResolver;

class FrontendSetupCommandTest extends PackageTestCase
{
    public function test_frontend_setup_command_is_registered(): void
    {
        $this->artisan('owl-admin:frontend-setup --help')
            ->assertExitCode(0);
    }

    public function test_dry_run_shows_merge_plan_when_prerequisites_exist(): void
    {
        $this->seedHostFrontendFiles();

        $this->artisan('owl-admin:frontend-setup --preset=core --dry-run')
            ->expectsOutputToContain('Frontend merge plan:')
            ->expectsOutputToContain('package.json merge:')
            ->expectsOutputToContain('vite.config.js:')
            ->expectsOutputToContain('app.jsx:')
            ->expectsOutputToContain('HandleInertiaRequests:')
            ->expectsOutputToContain('owlAdmin share:')
            ->expectsOutputToContain('missing inputs:')
            ->expectsOutputToContain('action:')
            ->expectsOutputToContain('missing dependencies:')
            ->expectsOutputToContain('missing devDependencies:')
            ->expectsOutputToContain('will write:')
            ->expectsOutputToContain('Dry run complete')
            ->assertExitCode(0);
    }

    public function test_refuses_package_json_changes_without_backup_or_force(): void
    {
        $this->seedHostFrontendFiles();

        $this->artisan('owl-admin:frontend-setup --preset=core')
            ->expectsOutputToContain('package.json changes require --backup or --force.')
            ->assertExitCode(1);
    }

    public function test_applies_package_json_merge_with_backup(): void
    {
        $this->seedHostFrontendFiles();

        $original = (string) file_get_contents(base_path('package.json'));

        $this->artisan('owl-admin:frontend-setup --preset=core --backup --no-interaction')
            ->expectsOutputToContain('Backup created:')
            ->expectsOutputToContain('package.json updated.')
            ->assertExitCode(0);

        $updated = (string) file_get_contents(base_path('package.json'));
        $this->assertNotSame($original, $updated);

        $decoded = json_decode($updated, true);
        $this->assertArrayHasKey('react', $decoded['dependencies']);
    }

    public function test_refuses_vite_config_changes_without_backup_or_force(): void
    {
        $files = $this->hostFixtureFiles();
        $files['package.json'] = $this->completePackageJson();
        $this->seedHostFiles($files);
        file_put_contents(base_path('resources/css/owl-admin.css'), '/* kit */');

        $this->artisan('owl-admin:frontend-setup --preset=core')
            ->expectsOutputToContain('vite.config.js changes require --backup or --force.')
            ->assertExitCode(1);
    }

    public function test_creates_missing_app_jsx_with_backup(): void
    {
        $files = $this->hostFixtureFiles();
        unset($files['resources/js/app.jsx']);
        $files['package.json'] = $this->completePackageJson();
        $files['vite.config.js'] = <<<'JS'
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.jsx'],
            refresh: true,
        }),
    ],
});
JS;
        $this->seedHostFiles($files);
        $this->removeAppJsxIfPresent();
        file_put_contents(base_path('resources/css/owl-admin.css'), '/* kit */');
        file_put_contents(base_path('resources/js/bootstrap.js'), "import axios from 'axios';\n");

        $this->artisan('owl-admin:frontend-setup --preset=core --backup --no-interaction')
            ->expectsOutputToContain('resources/js/app.jsx created.')
            ->assertExitCode(0);

        $contents = (string) file_get_contents(base_path('resources/js/app.jsx'));
        $this->assertStringContainsString('createInertiaApp', $contents);
    }

    public function test_refuses_app_jsx_creation_without_backup_or_force(): void
    {
        $files = $this->hostFixtureFiles();
        unset($files['resources/js/app.jsx']);
        $files['package.json'] = $this->completePackageJson();
        $files['vite.config.js'] = <<<'JS'
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.jsx'],
            refresh: true,
        }),
    ],
});
JS;
        $this->seedHostFiles($files);
        $this->removeAppJsxIfPresent();
        file_put_contents(base_path('resources/css/owl-admin.css'), '/* kit */');

        $this->artisan('owl-admin:frontend-setup --preset=core')
            ->expectsOutputToContain('app.jsx creation requires --backup or --force.')
            ->assertExitCode(1);
    }

    private function removeAppJsxIfPresent(): void
    {
        $path = base_path('resources/js/app.jsx');

        if (is_file($path)) {
            unlink($path);
        }
    }

    public function test_planner_fails_when_package_json_missing(): void
    {
        $basePath = $this->createTempHost([]);
        $planner = app(FrontendSetupPlanner::class);
        $result = $planner->plan($basePath, 'core');

        $this->assertFalse($result->ready);
        $this->assertTrue($result->hasBlockingFailures());
    }

    public function test_planner_builds_plan_for_core_host(): void
    {
        $basePath = $this->createTempHost($this->hostFixtureFiles());
        $planner = new FrontendSetupPlanner(
            new PublishMapResolver(),
            app(\OwlSolutions\CustomAdminKit\Support\DependencyChecker::class),
            app(\OwlSolutions\CustomAdminKit\Support\FrontendDependencyChecker::class),
            app(\OwlSolutions\CustomAdminKit\Support\PackageJsonMerger::class),
            app(\OwlSolutions\CustomAdminKit\Support\ViteConfigMerger::class),
            app(\OwlSolutions\CustomAdminKit\Support\InertiaAppMerger::class),
            app(\OwlSolutions\CustomAdminKit\Support\InertiaMiddlewareMerger::class),
        );

        $result = $planner->plan($basePath, 'core');

        $this->assertTrue($result->ready);
        $this->assertNotEmpty($result->planSteps);
        $this->assertNotEmpty($result->missingNpm);
    }

    private function seedHostFrontendFiles(): void
    {
        $this->seedHostFiles($this->hostFixtureFiles());
        file_put_contents(base_path('resources/css/owl-admin.css'), '/* kit */');
    }

    /**
     * @param  array<string, string>  $files
     */
    private function seedHostFiles(array $files): void
    {
        foreach ($files as $path => $contents) {
            $fullPath = base_path($path);
            $dir = dirname($fullPath);

            if (! is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            file_put_contents($fullPath, $contents);
        }
    }

    private function completePackageJson(): string
    {
        return json_encode([
            'private' => true,
            'dependencies' => [
                '@inertiajs/react' => '^2.0.0',
                'react' => '^18.2.0',
                'react-dom' => '^18.2.0',
                'lucide-react' => '^1.16.0',
                'radix-ui' => '^1.4.3',
                'class-variance-authority' => '^0.7.1',
                'clsx' => '^2.1.1',
                'tailwind-merge' => '^3.6.0',
            ],
            'devDependencies' => [
                '@vitejs/plugin-react' => '^6.0.0',
                'postcss' => '^8.4.31',
                'autoprefixer' => '^10.4.12',
                'tailwindcss-animate' => '^1.0.7',
                'vite' => '^8.0.0',
                'laravel-vite-plugin' => '^3.1',
                'tailwindcss' => '^3.2.1',
            ],
        ], JSON_PRETTY_PRINT);
    }

    /**
     * @param  array<string, string>  $files
     */
    private function createTempHost(array $files): string
    {
        $path = sys_get_temp_dir().'/owl-frontend-setup-'.uniqid('', true);
        mkdir($path, 0777, true);

        foreach ($files as $relative => $contents) {
            $full = $path.'/'.$relative;
            $dir = dirname($full);

            if (! is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            file_put_contents($full, $contents);
        }

        return $path;
    }

    /**
     * @return array<string, string>
     */
    private function hostFixtureFiles(): array
    {
        return [
            'package.json' => json_encode(['private' => true, 'devDependencies' => ['vite' => '^8.0.0']], JSON_PRETTY_PRINT),
            'vite.config.js' => <<<'JS'
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css'],
            refresh: true,
        }),
    ],
});
JS,
            'resources/js/app.jsx' => "import '../css/app.css';\n",
            'resources/css/app.css' => "body {}\n",
            'app/Http/Middleware/HandleInertiaRequests.php' => <<<'PHP'
<?php
namespace App\Http\Middleware;
use Illuminate\Http\Request;
use Inertia\Middleware;
class HandleInertiaRequests extends Middleware {
    public function share(Request $request): array {
        return array_merge(parent::share($request), []);
    }
}
PHP,
        ];
    }
}
