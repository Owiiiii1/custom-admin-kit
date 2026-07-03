<?php

namespace OwlSolutions\CustomAdminKit\Tests;

use OwlSolutions\CustomAdminKit\Support\FileBackupManager;
use OwlSolutions\CustomAdminKit\Support\PackageJsonMerger;

class PackageJsonMergerTest extends PackageTestCase
{
    private PackageJsonMerger $merger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merger = new PackageJsonMerger;
    }

    public function test_adds_missing_dependencies(): void
    {
        $basePath = $this->createTempHost([
            'package.json' => json_encode([
                'private' => true,
                'devDependencies' => [
                    'vite' => '^8.0.0',
                    'laravel-vite-plugin' => '^3.1',
                    'tailwindcss' => '^3.2.1',
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n",
        ]);

        $plan = $this->merger->analyze($basePath);

        $this->assertContains('react', array_keys($plan->missingDependencies));
        $this->assertContains('@inertiajs/react', array_keys($plan->missingDependencies));
        $this->assertContains('@vitejs/plugin-react', array_keys($plan->missingDevDependencies));
        $this->assertNotContains('vite', array_keys($plan->missingDevDependencies));

        $this->assertTrue($this->merger->apply($basePath, $plan));

        $decoded = json_decode((string) file_get_contents($basePath.'/package.json'), true);

        $this->assertArrayHasKey('react', $decoded['dependencies']);
        $this->assertArrayHasKey('@vitejs/plugin-react', $decoded['devDependencies']);
        $this->assertArrayHasKey('vite', $decoded['devDependencies']);

        $this->removeTempHost($basePath);
    }

    public function test_preserves_existing_dependency_versions(): void
    {
        $basePath = $this->createTempHost([
            'package.json' => json_encode([
                'private' => true,
                'dependencies' => [
                    'react' => '^17.0.0',
                    'react-dom' => '17.0.2',
                ],
                'devDependencies' => [
                    'vite' => '^8.0.0',
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n",
        ]);

        $plan = $this->merger->analyze($basePath);

        $this->assertArrayNotHasKey('react', $plan->missingDependencies);
        $this->assertArrayNotHasKey('react-dom', $plan->missingDependencies);

        $this->assertTrue($this->merger->apply($basePath, $plan));

        $decoded = json_decode((string) file_get_contents($basePath.'/package.json'), true);

        $this->assertSame('^17.0.0', $decoded['dependencies']['react']);
        $this->assertSame('17.0.2', $decoded['dependencies']['react-dom']);

        $this->removeTempHost($basePath);
    }

    public function test_does_not_remove_unrelated_dependencies(): void
    {
        $basePath = $this->createTempHost([
            'package.json' => json_encode([
                'private' => true,
                'dependencies' => [
                    'lodash' => '^4.17.21',
                ],
                'devDependencies' => [
                    'vite' => '^8.0.0',
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n",
        ]);

        $plan = $this->merger->analyze($basePath);
        $this->assertTrue($this->merger->apply($basePath, $plan));

        $decoded = json_decode((string) file_get_contents($basePath.'/package.json'), true);

        $this->assertArrayHasKey('lodash', $decoded['dependencies']);
        $this->assertSame('^4.17.21', $decoded['dependencies']['lodash']);

        $this->removeTempHost($basePath);
    }

    public function test_dry_run_does_not_write(): void
    {
        $original = json_encode([
            'private' => true,
            'devDependencies' => [
                'vite' => '^8.0.0',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";

        $basePath = $this->createTempHost([
            'package.json' => $original,
        ]);

        $plan = $this->merger->analyze($basePath);

        $this->assertTrue($plan->hasChanges());
        $this->assertTrue($this->merger->apply($basePath, $plan, dryRun: true));
        $this->assertSame($original, (string) file_get_contents($basePath.'/package.json'));

        $this->removeTempHost($basePath);
    }

    public function test_backup_creates_backup_before_write(): void
    {
        $original = json_encode([
            'private' => true,
            'devDependencies' => [
                'vite' => '^8.0.0',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";

        $basePath = $this->createTempHost([
            'package.json' => $original,
        ]);

        $backupManager = new FileBackupManager;
        $backupDir = $backupManager->backupDirectory($basePath);
        $backedUp = $backupManager->backupFiles($basePath, $backupDir, ['package.json']);

        $this->assertCount(1, $backedUp);
        $this->assertSame($original, (string) file_get_contents($backedUp[0]));

        $plan = $this->merger->analyze($basePath);
        $this->assertTrue($this->merger->apply($basePath, $plan));
        $this->assertNotSame($original, (string) file_get_contents($basePath.'/package.json'));

        $this->removeTempHost($basePath);
    }

    /**
     * @param  array<string, string>  $files
     */
    private function createTempHost(array $files): string
    {
        $path = sys_get_temp_dir().'/owl-package-json-merger-'.uniqid('', true);
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
