<?php

namespace OwlSolutions\CustomAdminKit\Tests;

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
