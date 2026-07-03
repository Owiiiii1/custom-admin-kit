<?php

namespace OwlSolutions\CustomAdminKit\Tests;

use OwlSolutions\CustomAdminKit\Support\FileBackupManager;
use OwlSolutions\CustomAdminKit\Support\InertiaMiddlewareAnalysis;
use OwlSolutions\CustomAdminKit\Support\InertiaMiddlewareMerger;

class InertiaMiddlewareMergerTest extends PackageTestCase
{
    private InertiaMiddlewareMerger $merger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merger = new InertiaMiddlewareMerger;
    }

    public function test_detects_existing_owl_admin_share(): void
    {
        $basePath = $this->createTempHost([
            'app/Http/Middleware/HandleInertiaRequests.php' => $this->middlewareWithOwlAdmin(),
        ]);

        $analysis = $this->merger->analyze($basePath);

        $this->assertTrue($this->merger->hasOwlAdminShare($basePath));
        $this->assertTrue($analysis->hasOwlAdminShare);
        $this->assertSame(InertiaMiddlewareAnalysis::ACTION_OK, $analysis->action);
        $this->assertFalse($analysis->canAutoMerge());

        $this->removeTempHost($basePath);
    }

    public function test_auto_merges_standard_share_method(): void
    {
        $basePath = $this->createTempHost([
            'app/Http/Middleware/HandleInertiaRequests.php' => $this->standardMiddleware(),
        ]);

        $analysis = $this->merger->analyze($basePath);

        $this->assertTrue($analysis->canAutoMerge());
        $this->assertTrue($this->merger->apply($basePath, $analysis));

        $updated = (string) file_get_contents($basePath.'/app/Http/Middleware/HandleInertiaRequests.php');
        $this->assertStringContainsString("'owlAdmin'", $updated);
        $this->assertStringContainsString("config('owl-admin.branding'", $updated);

        $this->removeTempHost($basePath);
    }

    public function test_does_not_alter_non_standard_middleware(): void
    {
        $original = <<<'PHP'
<?php
namespace App\Http\Middleware;
use Illuminate\Http\Request;
use Inertia\Middleware;
class HandleInertiaRequests extends Middleware {
    public function share(Request $request) {
        return ['custom' => true];
    }
}
PHP;

        $basePath = $this->createTempHost([
            'app/Http/Middleware/HandleInertiaRequests.php' => $original,
        ]);

        $analysis = $this->merger->analyze($basePath);

        $this->assertSame(InertiaMiddlewareAnalysis::ACTION_MANUAL, $analysis->action);
        $this->assertFalse($this->merger->apply($basePath, $analysis));
        $this->assertSame($original, (string) file_get_contents($basePath.'/app/Http/Middleware/HandleInertiaRequests.php'));

        $this->removeTempHost($basePath);
    }

    public function test_dry_run_does_not_write(): void
    {
        $original = $this->standardMiddleware();

        $basePath = $this->createTempHost([
            'app/Http/Middleware/HandleInertiaRequests.php' => $original,
        ]);

        $analysis = $this->merger->analyze($basePath);
        $this->assertTrue($this->merger->apply($basePath, $analysis, dryRun: true));
        $this->assertSame($original, (string) file_get_contents($basePath.'/app/Http/Middleware/HandleInertiaRequests.php'));

        $this->removeTempHost($basePath);
    }

    public function test_backup_creates_backup_before_write(): void
    {
        $original = $this->standardMiddleware();

        $basePath = $this->createTempHost([
            'app/Http/Middleware/HandleInertiaRequests.php' => $original,
        ]);

        $backupManager = new FileBackupManager;
        $backupDir = $backupManager->backupDirectory($basePath);
        $backedUp = $backupManager->backupFiles($basePath, $backupDir, ['app/Http/Middleware/HandleInertiaRequests.php']);

        $this->assertCount(1, $backedUp);
        $this->assertSame($original, (string) file_get_contents($backedUp[0]));

        $analysis = $this->merger->analyze($basePath);
        $this->assertTrue($this->merger->apply($basePath, $analysis));
        $this->assertNotSame($original, (string) file_get_contents($basePath.'/app/Http/Middleware/HandleInertiaRequests.php'));

        $this->removeTempHost($basePath);
    }

    public function test_missing_middleware_is_blocked(): void
    {
        $basePath = $this->createTempHost([]);

        $analysis = $this->merger->analyze($basePath);

        $this->assertSame(InertiaMiddlewareAnalysis::STATUS_MISSING, $analysis->status);
        $this->assertSame(InertiaMiddlewareAnalysis::ACTION_BLOCKED, $analysis->action);
        $this->assertStringContainsString('inertia:middleware', $analysis->installHint ?? '');

        $this->removeTempHost($basePath);
    }

    private function standardMiddleware(): string
    {
        return <<<'PHP'
<?php
namespace App\Http\Middleware;
use Illuminate\Http\Request;
use Inertia\Middleware;
class HandleInertiaRequests extends Middleware {
    public function share(Request $request): array {
        return array_merge(parent::share($request), []);
    }
}
PHP;
    }

    private function middlewareWithOwlAdmin(): string
    {
        return <<<'PHP'
<?php
namespace App\Http\Middleware;
use Illuminate\Http\Request;
use Inertia\Middleware;
class HandleInertiaRequests extends Middleware {
    public function share(Request $request): array {
        return array_merge(parent::share($request), [
            'owlAdmin' => fn () => config('owl-admin.branding'),
        ]);
    }
}
PHP;
    }

    /**
     * @param  array<string, string>  $files
     */
    private function createTempHost(array $files): string
    {
        $path = sys_get_temp_dir().'/owl-inertia-middleware-'.uniqid('', true);
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
