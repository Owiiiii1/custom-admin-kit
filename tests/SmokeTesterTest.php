<?php

namespace OwlSolutions\CustomAdminKit\Tests;

use Illuminate\Support\Facades\Route;
use OwlSolutions\CustomAdminKit\Support\FrontendSetupState;
use OwlSolutions\CustomAdminKit\Support\PackageVersion;
use OwlSolutions\CustomAdminKit\Support\PublishMapResolver;
use OwlSolutions\CustomAdminKit\Support\SmokeTester;

class SmokeTesterTest extends PackageTestCase
{
    public function test_published_files_fails_when_publish_map_is_empty(): void
    {
        config(['owl-admin-kit.publish_map' => []]);

        $tester = new SmokeTester(new PublishMapResolver());
        $result = $tester->checkPublishedFiles($this->tempBasePath(), 'core');

        $this->assertFalse($result->passed);
        $this->assertSame('published-files', $result->name);
        $this->assertStringContainsString('0/0', $result->message);
    }

    public function test_published_files_fails_when_targets_are_missing(): void
    {
        $basePath = $this->tempBasePath();
        $tester = new SmokeTester(new PublishMapResolver());
        $result = $tester->checkPublishedFiles($basePath, 'core');

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('/23', $result->message);
        $this->assertStringStartsWith('Missing:', (string) $result->hint);
    }

    public function test_published_files_passes_when_all_targets_exist(): void
    {
        $basePath = $this->tempBasePath();
        $resolver = new PublishMapResolver();

        foreach ($resolver->copyEntriesForPreset('core') as $entry) {
            $target = $basePath.'/'.ltrim((string) $entry['target'], '/');
            $dir = dirname($target);
            if (! is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($target, 'stub');
        }

        $tester = new SmokeTester($resolver);
        $result = $tester->checkPublishedFiles($basePath, 'core');

        $this->assertTrue($result->passed);
        $this->assertSame('published-files: 23/23 core file(s) on disk.', $result->name.': '.$result->message);
    }

    public function test_vite_manifest_ok_when_manifest_exists(): void
    {
        $basePath = $this->tempBasePath();
        mkdir($basePath.'/public/build', 0777, true);
        file_put_contents($basePath.'/public/build/manifest.json', '{}');

        $tester = new SmokeTester(new PublishMapResolver());
        $result = $tester->checkViteManifest($basePath);

        $this->assertTrue($result->passed);
        $this->assertStringContainsString('public/build/manifest.json exists', $result->message);
    }

    public function test_vite_manifest_warns_when_manifest_missing(): void
    {
        $tester = new SmokeTester(new PublishMapResolver());
        $result = $tester->checkViteManifest($this->tempBasePath());

        $this->assertTrue($result->passed);
        $this->assertTrue($result->isWarning());
        $this->assertStringContainsString('Frontend build not found', $result->message);
    }

    public function test_run_does_not_pass_zero_published_files_when_state_is_empty(): void
    {
        $basePath = $this->tempBasePath();
        $tester = new SmokeTester(new PublishMapResolver());
        $results = $tester->run($basePath, ['published_files' => []], 'core');

        $published = collect($results)->firstWhere('name', 'published-files');

        $this->assertNotNull($published);
        $this->assertFalse($published->passed);
    }

    public function test_run_warns_when_install_state_version_differs_from_current_package_version(): void
    {
        config(['owl-admin-kit.version' => '0.2.0']);

        $basePath = $this->tempBasePath();
        $tester = new SmokeTester(new PublishMapResolver());
        $results = $tester->run($basePath, [
            'version' => '0.1.1',
            'preset' => 'core',
            'published_files' => [],
        ], 'core');

        $installState = collect($results)->firstWhere('name', 'install-state');
        $versionWarning = collect($results)->firstWhere('name', 'install-state-version');

        $this->assertNotNull($installState);
        $this->assertTrue($installState->passed);
        $this->assertStringContainsString('v0.1.1', $installState->message);

        if (PackageVersion::equals('0.1.1', PackageVersion::current())) {
            $this->markTestSkipped('Current package version matches 0.1.1 in this environment.');
        }

        $this->assertNotNull($versionWarning);
        $this->assertTrue($versionWarning->isWarning());
        $this->assertStringContainsString('Install state version v0.1.1 differs from current package version', $versionWarning->message);
        $this->assertStringContainsString('owl-admin:install --preset=core --repair', (string) $versionWarning->hint);
    }

    public function test_run_does_not_warn_when_install_state_version_matches_current_package_version(): void
    {
        $currentVersion = PackageVersion::current();
        $basePath = $this->tempBasePath();
        $tester = new SmokeTester(new PublishMapResolver());
        $results = $tester->run($basePath, [
            'version' => $currentVersion,
            'preset' => 'core',
            'published_files' => [],
        ], 'core');

        $versionWarning = collect($results)->firstWhere('name', 'install-state-version');

        $this->assertNull($versionWarning);
    }

    public function test_run_warns_when_frontend_setup_not_detected(): void
    {
        $basePath = $this->tempBasePath();
        $tester = new SmokeTester(new PublishMapResolver());
        $results = $tester->run($basePath, null, 'core');

        $frontendWarning = collect($results)->firstWhere('name', 'frontend-setup-detected');

        $this->assertNotNull($frontendWarning);
        $this->assertTrue($frontendWarning->isWarning());
        $this->assertSame(SmokeTester::SECTION_FRONTEND_SETUP, $frontendWarning->section);
        $this->assertStringContainsString('Frontend setup not detected', $frontendWarning->message);
    }

    public function test_core_checks_use_core_section(): void
    {
        $basePath = $this->tempBasePath();
        $tester = new SmokeTester(new PublishMapResolver());
        $results = $tester->run($basePath, null, 'core');

        $coreChecks = collect($results)->filter(
            static fn ($result) => $result->section === SmokeTester::SECTION_CORE,
        );

        $this->assertGreaterThanOrEqual(8, $coreChecks->count());

        foreach ($coreChecks as $check) {
            $this->assertSame(SmokeTester::SECTION_CORE, $check->section, $check->name);
        }
    }

    public function test_completed_frontend_checks_use_frontend_setup_section(): void
    {
        $basePath = $this->tempBasePath();
        $this->mockFrontendRoutesExist();
        $this->seedCompletedFrontendSetup($basePath, withOwlAdminShare: true);

        $tester = new SmokeTester(new PublishMapResolver());
        $state = (new FrontendSetupState(FrontendSetupState::pathFor($basePath)))->read();
        $results = $tester->checkFrontendSetup($basePath, $state);

        $this->assertCount(10, $results);

        foreach ($results as $check) {
            $this->assertSame(
                SmokeTester::SECTION_FRONTEND_SETUP,
                $check->section,
                'Check '.$check->name.' should be in Frontend setup section.',
            );
        }
    }

    public function test_check_frontend_setup_passes_when_state_and_routes_exist(): void
    {
        $basePath = $this->tempBasePath();
        $this->mockFrontendRoutesExist();
        $this->seedCompletedFrontendSetup($basePath, withOwlAdminShare: true);

        $tester = new SmokeTester(new PublishMapResolver());
        $state = (new FrontendSetupState(FrontendSetupState::pathFor($basePath)))->read();
        $results = $tester->checkFrontendSetup($basePath, $state);

        foreach (['frontend-state', 'route:dashboard', 'route:settings.index', 'route:app-settings.index', 'route:statistics.logs', 'routes-file', 'web-routes-include', 'inertia-share', 'app-jsx', 'vite-manifest'] as $name) {
            $check = collect($results)->firstWhere('name', $name);
            $this->assertNotNull($check, "Missing check: {$name}");
            $this->assertTrue($check->passed, $name.': '.$check->message);
            $this->assertSame(SmokeTester::SECTION_FRONTEND_SETUP, $check->section, $name);
        }
    }

    public function test_check_frontend_setup_fails_when_dashboard_route_missing(): void
    {
        $basePath = $this->tempBasePath();
        Route::shouldReceive('has')->andReturn(false);
        $this->seedCompletedFrontendSetup($basePath, withOwlAdminShare: true);

        $tester = new SmokeTester(new PublishMapResolver());
        $state = (new FrontendSetupState(FrontendSetupState::pathFor($basePath)))->read();
        $results = $tester->checkFrontendSetup($basePath, $state);

        $dashboard = collect($results)->firstWhere('name', 'route:dashboard');

        $this->assertNotNull($dashboard);
        $this->assertFalse($dashboard->passed);
    }

    public function test_check_frontend_setup_fails_when_owl_admin_share_missing(): void
    {
        $basePath = $this->tempBasePath();
        $this->mockFrontendRoutesExist();
        $this->seedCompletedFrontendSetup($basePath, withOwlAdminShare: false);

        $tester = new SmokeTester(new PublishMapResolver());
        $state = (new FrontendSetupState(FrontendSetupState::pathFor($basePath)))->read();
        $results = $tester->checkFrontendSetup($basePath, $state);

        $share = collect($results)->firstWhere('name', 'inertia-share');

        $this->assertNotNull($share);
        $this->assertFalse($share->passed);
        $this->assertStringContainsString('owlAdmin', $share->message);
    }

    private function mockFrontendRoutesExist(): void
    {
        Route::shouldReceive('has')
            ->andReturnUsing(static fn (string $name): bool => in_array($name, [
                'dashboard',
                'settings.index',
                'app-settings.index',
                'statistics.logs',
            ], true));
    }

    /**
     * @return array<string, mixed>
     */
    private function completedFrontendState(): array
    {
        return [
            'completed' => true,
            'completed_at' => now()->toIso8601String(),
            'package_version' => PackageVersion::current(),
            'preset' => 'core',
            'package_json_updated' => true,
            'vite_config_updated' => true,
            'app_css_updated' => true,
            'app_jsx_created' => true,
            'inertia_middleware_updated' => true,
            'routes_file_created' => true,
            'web_routes_included' => true,
            'npm_install_ran' => false,
            'build_ran' => true,
        ];
    }

    private function seedCompletedFrontendSetup(string $basePath, bool $withOwlAdminShare): void
    {
        $stateDir = $basePath.'/storage/app/owl-admin-kit';
        mkdir($stateDir, 0777, true);
        file_put_contents(
            $stateDir.'/frontend-setup-state.json',
            json_encode($this->completedFrontendState(), JSON_PRETTY_PRINT),
        );

        mkdir($basePath.'/routes', 0777, true);
        file_put_contents($basePath.'/routes/owl-admin-pages.php', "<?php\n");
        file_put_contents(
            $basePath.'/routes/web.php',
            "<?php\nrequire __DIR__.'/owl-admin-pages.php';\n",
        );

        mkdir($basePath.'/resources/js', 0777, true);
        file_put_contents($basePath.'/resources/js/app.jsx', "import '../css/app.css';\n");

        mkdir($basePath.'/public/build', 0777, true);
        file_put_contents($basePath.'/public/build/manifest.json', '{}');

        mkdir($basePath.'/app/Http/Middleware', 0777, true);
        $middleware = $withOwlAdminShare
            ? "<?php\nclass HandleInertiaRequests {\n public function share() { return ['owlAdmin' => []]; }\n}\n"
            : "<?php\nclass HandleInertiaRequests {\n public function share() { return []; }\n}\n";
        file_put_contents($basePath.'/app/Http/Middleware/HandleInertiaRequests.php', $middleware);
    }

    private function tempBasePath(): string
    {
        $path = sys_get_temp_dir().'/owl-smoke-'.uniqid('', true);
        mkdir($path, 0777, true);

        $this->tempPaths[] = $path;

        return $path;
    }

    /** @var list<string> */
    private array $tempPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->tempPaths as $path) {
            $this->deleteDirectory($path);
        }

        parent::tearDown();
    }

    private function deleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $items = scandir($path) ?: [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path.'/'.$item;

            if (is_dir($fullPath)) {
                $this->deleteDirectory($fullPath);
            } else {
                @unlink($fullPath);
            }
        }

        @rmdir($path);
    }
}
