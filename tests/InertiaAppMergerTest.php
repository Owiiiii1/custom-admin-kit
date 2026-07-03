<?php

namespace OwlSolutions\CustomAdminKit\Tests;

use OwlSolutions\CustomAdminKit\Support\InertiaAppAnalysis;
use OwlSolutions\CustomAdminKit\Support\InertiaAppMerger;

class InertiaAppMergerTest extends PackageTestCase
{
    private InertiaAppMerger $merger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merger = new InertiaAppMerger;
    }

    public function test_missing_app_jsx_can_be_created_from_snippet(): void
    {
        $basePath = $this->createTempHost([
            'resources/css/app.css' => 'body {}',
            'resources/js/bootstrap.js' => "import axios from 'axios';\n",
        ]);

        $analysis = $this->merger->analyze($basePath);

        $this->assertSame(InertiaAppAnalysis::STATUS_MISSING, $analysis->status);
        $this->assertTrue($analysis->canAutoCreate());
        $this->assertTrue($this->merger->apply($basePath, $analysis));

        $contents = (string) file_get_contents($basePath.'/resources/js/app.jsx');
        $this->assertStringContainsString('createInertiaApp', $contents);
        $this->assertStringContainsString('resolvePageComponent', $contents);
        $this->assertStringContainsString("import '../css/app.css'", $contents);
        $this->assertStringContainsString("import './bootstrap'", $contents);

        $this->removeTempHost($basePath);
    }

    public function test_existing_app_jsx_is_not_overwritten(): void
    {
        $original = "import '../css/app.css';\nconsole.log('custom');\n";

        $basePath = $this->createTempHost([
            'resources/js/app.jsx' => $original,
        ]);

        $analysis = $this->merger->analyze($basePath);

        $this->assertFalse($analysis->canAutoCreate());
        $this->assertFalse($this->merger->apply($basePath, $analysis));
        $this->assertSame($original, (string) file_get_contents($basePath.'/resources/js/app.jsx'));

        $this->removeTempHost($basePath);
    }

    public function test_dry_run_does_not_write(): void
    {
        $basePath = $this->createTempHost([
            'resources/css/app.css' => 'body {}',
        ]);

        $analysis = $this->merger->analyze($basePath);
        $this->assertTrue($this->merger->apply($basePath, $analysis, dryRun: true));
        $this->assertFileDoesNotExist($basePath.'/resources/js/app.jsx');

        $this->removeTempHost($basePath);
    }

    public function test_app_js_only_is_detected(): void
    {
        $basePath = $this->createTempHost([
            'resources/js/app.js' => "import './bootstrap';\n",
        ]);

        $analysis = $this->merger->analyze($basePath);

        $this->assertSame(InertiaAppAnalysis::STATUS_APP_JS_ONLY, $analysis->status);
        $this->assertSame(InertiaAppAnalysis::ACTION_CREATE, $analysis->action);
        $this->assertTrue($analysis->canAutoCreate());
        $this->assertStringContainsString('app.js exists', $analysis->reason);

        $this->removeTempHost($basePath);
    }

    public function test_non_standard_existing_app_jsx_requires_manual_merge(): void
    {
        $basePath = $this->createTempHost([
            'resources/js/app.jsx' => "import '../css/app.css';\nconsole.log('custom');\n",
        ]);

        $analysis = $this->merger->analyze($basePath);

        $this->assertSame(InertiaAppAnalysis::STATUS_EXISTS, $analysis->status);
        $this->assertSame(InertiaAppAnalysis::ACTION_MANUAL, $analysis->action);
        $this->assertTrue($analysis->requiresManualMerge());
        $this->assertFalse($analysis->canAutoCreate());
        $this->assertFalse($analysis->canAutoMerge());

        $this->removeTempHost($basePath);
    }

    public function test_standard_existing_app_jsx_is_ok(): void
    {
        $basePath = $this->createTempHost([
            'resources/js/app.jsx' => (string) file_get_contents(
                dirname(__DIR__).'/docs/merge-snippets/app.jsx',
            ),
            'resources/js/bootstrap.js' => "import axios from 'axios';\n",
        ]);

        $analysis = $this->merger->analyze($basePath);

        $this->assertSame(InertiaAppAnalysis::ACTION_OK, $analysis->action);
        $this->assertTrue($analysis->isStandard());
        $this->assertFalse($analysis->canAutoCreate());

        $this->removeTempHost($basePath);
    }

    public function test_snippet_omits_bootstrap_import_when_bootstrap_missing(): void
    {
        $basePath = $this->createTempHost([
            'resources/css/app.css' => 'body {}',
        ]);

        $analysis = $this->merger->analyze($basePath);
        $this->assertTrue($this->merger->apply($basePath, $analysis));

        $contents = (string) file_get_contents($basePath.'/resources/js/app.jsx');
        $this->assertStringNotContainsString("import './bootstrap'", $contents);
        $this->assertStringContainsString("import '../css/app.css'", $contents);

        $this->removeTempHost($basePath);
    }

    /**
     * @param  array<string, string>  $files
     */
    private function createTempHost(array $files): string
    {
        $path = sys_get_temp_dir().'/owl-inertia-app-merger-'.uniqid('', true);
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
