<?php

namespace OwlSolutions\CustomAdminKit\Installers;

use OwlSolutions\CustomAdminKit\Support\FilePublisher;
use OwlSolutions\CustomAdminKit\Support\InstallReport;
use OwlSolutions\CustomAdminKit\Support\PublishMapResolver;

class StubPublishInstaller
{
    public function __construct(
        private readonly FilePublisher $publisher,
        private readonly PublishMapResolver $publishMap,
    ) {}

    public function install(
        string $stubsPath,
        string $basePath,
        string $preset,
        bool $force,
        bool $dryRun,
        bool $backup,
        InstallReport $report,
    ): array {
        $map = $this->publishMap->copyMapForPreset($preset);

        if ($map === []) {
            $report->addWarning("No stubs mapped for preset [{$preset}].");

            return [];
        }

        $published = $this->publisher->publish($stubsPath, $basePath, $map, $force, $dryRun, $backup);

        foreach ($published as $path) {
            $prefix = $dryRun ? '[dry-run]' : 'Published';
            $report->addStep("{$prefix}: {$path}");
        }

        $skipped = count($map) - count($published);
        if ($skipped > 0 && ! $dryRun && ! $force) {
            $report->addWarning("{$skipped} stub(s) skipped (destination exists — use --force --backup).");
        }

        return $published;
    }
}
