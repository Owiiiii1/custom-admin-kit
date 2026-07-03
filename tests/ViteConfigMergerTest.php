<?php

namespace OwlSolutions\CustomAdminKit\Tests;

use OwlSolutions\CustomAdminKit\Support\FileBackupManager;
use OwlSolutions\CustomAdminKit\Support\ViteConfigAnalysis;
use OwlSolutions\CustomAdminKit\Support\ViteConfigMerger;

class ViteConfigMergerTest extends PackageTestCase
{
    private ViteConfigMerger $merger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merger = new ViteConfigMerger;
    }

    public function test_standard_vite_config_with_missing_app_entry_gets_merged(): void
    {
        $config = <<<'JS'
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
JS;

        $basePath = $this->createTempHost([
            'vite.config.js' => $config,
            'resources/js/app.jsx' => "import './bootstrap';\n",
        ]);

        $analysis = $this->merger->analyze($basePath);

        $this->assertSame(ViteConfigAnalysis::STATUS_STANDARD, $analysis->status);
        $this->assertTrue($analysis->canAutoMerge());
        $this->assertContains('resources/js/app.jsx', $analysis->missingInputs);

        $this->assertTrue($this->merger->apply($basePath, $analysis));

        $updated = (string) file_get_contents($basePath.'/vite.config.js');
        $this->assertStringContainsString("'resources/css/app.css'", $updated);
        $this->assertStringContainsString("'resources/js/app.jsx'", $updated);

        $this->removeTempHost($basePath);
    }

    public function test_existing_inputs_are_preserved(): void
    {
        $config = <<<'JS'
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/legacy.js'],
            refresh: true,
        }),
    ],
});
JS;

        $basePath = $this->createTempHost([
            'vite.config.js' => $config,
            'resources/js/app.jsx' => "import './bootstrap';\n",
        ]);

        $analysis = $this->merger->analyze($basePath);
        $this->assertTrue($this->merger->apply($basePath, $analysis));

        $updated = (string) file_get_contents($basePath.'/vite.config.js');
        $this->assertStringContainsString("'resources/js/legacy.js'", $updated);
        $this->assertStringContainsString("'resources/js/app.jsx'", $updated);

        $this->removeTempHost($basePath);
    }

    public function test_non_standard_config_requires_manual_merge(): void
    {
        $config = <<<'JS'
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [],
});
JS;

        $basePath = $this->createTempHost([
            'vite.config.js' => $config,
            'resources/js/app.jsx' => "import './bootstrap';\n",
        ]);

        $analysis = $this->merger->analyze($basePath);

        $this->assertSame(ViteConfigAnalysis::STATUS_NON_STANDARD, $analysis->status);
        $this->assertFalse($analysis->canAutoMerge());
        $this->assertSame(ViteConfigAnalysis::ACTION_MANUAL, $analysis->action);
        $this->assertFalse($this->merger->apply($basePath, $analysis));

        $this->removeTempHost($basePath);
    }

    public function test_dry_run_does_not_write(): void
    {
        $config = <<<'JS'
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
JS;

        $basePath = $this->createTempHost([
            'vite.config.js' => $config,
            'resources/js/app.jsx' => "import './bootstrap';\n",
        ]);

        $analysis = $this->merger->analyze($basePath);
        $this->assertTrue($this->merger->apply($basePath, $analysis, dryRun: true));
        $this->assertSame($config, (string) file_get_contents($basePath.'/vite.config.js'));

        $this->removeTempHost($basePath);
    }

    public function test_backup_creates_backup_before_write(): void
    {
        $config = <<<'JS'
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
JS;

        $basePath = $this->createTempHost([
            'vite.config.js' => $config,
            'resources/js/app.jsx' => "import './bootstrap';\n",
        ]);

        $backupManager = new FileBackupManager;
        $backupDir = $backupManager->backupDirectory($basePath);
        $backedUp = $backupManager->backupFiles($basePath, $backupDir, ['vite.config.js']);

        $this->assertCount(1, $backedUp);
        $this->assertSame($config, (string) file_get_contents($backedUp[0]));

        $analysis = $this->merger->analyze($basePath);
        $this->assertTrue($this->merger->apply($basePath, $analysis));
        $this->assertNotSame($config, (string) file_get_contents($basePath.'/vite.config.js'));

        $this->removeTempHost($basePath);
    }

    public function test_standard_config_with_app_entry_is_ok(): void
    {
        $config = <<<'JS'
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

        $basePath = $this->createTempHost([
            'vite.config.js' => $config,
            'resources/js/app.jsx' => "import './bootstrap';\n",
        ]);

        $analysis = $this->merger->analyze($basePath);

        $this->assertSame(ViteConfigAnalysis::STATUS_STANDARD, $analysis->status);
        $this->assertTrue($analysis->hasAppEntry);
        $this->assertFalse($analysis->canAutoMerge());
        $this->assertSame(ViteConfigAnalysis::ACTION_SKIP, $analysis->action);

        $this->removeTempHost($basePath);
    }

    /**
     * @param  array<string, string>  $files
     */
    private function createTempHost(array $files): string
    {
        $path = sys_get_temp_dir().'/owl-vite-config-merger-'.uniqid('', true);
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
