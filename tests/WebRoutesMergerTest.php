<?php

namespace OwlSolutions\CustomAdminKit\Tests;

use OwlSolutions\CustomAdminKit\Support\FileBackupManager;
use OwlSolutions\CustomAdminKit\Support\WebRoutesAnalysis;
use OwlSolutions\CustomAdminKit\Support\WebRoutesMerger;

class WebRoutesMergerTest extends PackageTestCase
{
    private WebRoutesMerger $merger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merger = new WebRoutesMerger;
    }

    public function test_creates_routes_owl_admin_pages_file(): void
    {
        $basePath = $this->createTempHost([
            'routes/web.php' => $this->standardWebRoutes(),
        ]);
        $this->seedInertiaVendor($basePath);

        $analysis = $this->merger->analyze($basePath);

        $this->assertTrue($analysis->shouldCreatePagesFile);
        $this->assertTrue($this->merger->apply($basePath, $analysis));

        $contents = (string) file_get_contents($basePath.'/routes/owl-admin-pages.php');
        $this->assertStringContainsString("->name('dashboard')", $contents);
        $this->assertStringContainsString("Inertia::render('Settings/Index')", $contents);
        $this->assertStringContainsString('AdminRouteMiddleware::stack()', $contents);

        $this->removeTempHost($basePath);
    }

    public function test_detects_existing_include(): void
    {
        $basePath = $this->createTempHost([
            'routes/web.php' => $this->standardWebRoutes()."\nrequire __DIR__.'/owl-admin-pages.php';\n",
            'routes/owl-admin-pages.php' => "<?php\n",
        ]);
        $this->seedInertiaVendor($basePath);

        $analysis = $this->merger->analyze($basePath);

        $this->assertTrue($this->merger->hasInclude($basePath));
        $this->assertTrue($analysis->hasInclude);
        $this->assertSame(WebRoutesAnalysis::ACTION_OK, $analysis->action);
        $this->assertFalse($analysis->hasChanges());

        $this->removeTempHost($basePath);
    }

    public function test_adds_include_to_standard_web_routes(): void
    {
        $basePath = $this->createTempHost([
            'routes/web.php' => $this->standardWebRoutes()."\nrequire __DIR__.'/auth.php';\n",
            'routes/owl-admin-pages.php' => "<?php\n",
        ]);
        $this->seedInertiaVendor($basePath);

        $analysis = $this->merger->analyze($basePath);

        $this->assertTrue($analysis->shouldMergeWebInclude);
        $this->assertTrue($this->merger->apply($basePath, $analysis));

        $updated = (string) file_get_contents($basePath.'/routes/web.php');
        $this->assertStringContainsString("require __DIR__.'/owl-admin-pages.php';", $updated);
        $this->assertStringContainsString("require __DIR__.'/auth.php';", $updated);
        $this->assertLessThan(
            strpos($updated, "require __DIR__.'/auth.php';"),
            strpos($updated, "require __DIR__.'/owl-admin-pages.php';"),
        );

        $this->removeTempHost($basePath);
    }

    public function test_does_not_alter_non_standard_web_routes(): void
    {
        $original = <<<'PHP'
<?php
return [
    'routes' => [],
];
PHP;

        $basePath = $this->createTempHost([
            'routes/web.php' => $original,
        ]);
        $this->seedInertiaVendor($basePath);

        $analysis = $this->merger->analyze($basePath);

        $this->assertSame(WebRoutesAnalysis::ACTION_MANUAL, $analysis->action);
        $this->assertFalse($analysis->shouldMergeWebInclude);
        $this->assertTrue($analysis->shouldCreatePagesFile);
        $this->assertTrue($this->merger->apply($basePath, $analysis));
        $this->assertSame($original, (string) file_get_contents($basePath.'/routes/web.php'));
        $this->assertFileExists($basePath.'/routes/owl-admin-pages.php');

        $this->removeTempHost($basePath);
    }

    public function test_dry_run_does_not_write(): void
    {
        $basePath = $this->createTempHost([
            'routes/web.php' => $this->standardWebRoutes(),
        ]);
        $this->seedInertiaVendor($basePath);

        $analysis = $this->merger->analyze($basePath);
        $this->assertTrue($this->merger->apply($basePath, $analysis, dryRun: true));
        $this->assertFileDoesNotExist($basePath.'/routes/owl-admin-pages.php');

        $this->removeTempHost($basePath);
    }

    public function test_backup_creates_backup_before_write(): void
    {
        $webRoutes = $this->standardWebRoutes();

        $basePath = $this->createTempHost([
            'routes/web.php' => $webRoutes,
        ]);
        $this->seedInertiaVendor($basePath);

        $backupManager = new FileBackupManager;
        $backupDir = $backupManager->backupDirectory($basePath);
        $backedUp = $backupManager->backupFiles($basePath, $backupDir, ['routes/web.php']);

        $this->assertCount(1, $backedUp);
        $this->assertSame($webRoutes, (string) file_get_contents($backedUp[0]));

        $analysis = $this->merger->analyze($basePath);
        $this->assertTrue($this->merger->apply($basePath, $analysis));
        $this->assertNotSame($webRoutes, (string) file_get_contents($basePath.'/routes/web.php'));

        $this->removeTempHost($basePath);
    }

    public function test_blocks_when_inertia_dependency_missing(): void
    {
        $basePath = $this->createTempHost([
            'routes/web.php' => $this->standardWebRoutes(),
        ]);

        $analysis = $this->merger->analyze($basePath);

        $this->assertFalse($analysis->hasInertiaDependency);
        $this->assertSame(WebRoutesAnalysis::ACTION_BLOCKED, $analysis->action);
        $this->assertStringContainsString('composer require inertiajs/inertia-laravel', $analysis->inertiaInstallHint ?? '');

        $this->removeTempHost($basePath);
    }

    private function standardWebRoutes(): string
    {
        return <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
PHP;
    }

    private function seedInertiaVendor(string $basePath): void
    {
        $dir = $basePath.'/vendor/inertiajs/inertia-laravel';

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($dir.'/.gitkeep', '');
    }

    /**
     * @param  array<string, string>  $files
     */
    private function createTempHost(array $files): string
    {
        $path = sys_get_temp_dir().'/owl-web-routes-merger-'.uniqid('', true);
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

    private function removeTempHost(string $basePath): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($basePath);
    }
}
