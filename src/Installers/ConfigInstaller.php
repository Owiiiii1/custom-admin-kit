<?php

namespace OwlSolutions\CustomAdminKit\Installers;

use OwlSolutions\CustomAdminKit\Support\FilePublisher;
use OwlSolutions\CustomAdminKit\Support\InstallReport;

class ConfigInstaller
{
    public function __construct(
        private readonly FilePublisher $publisher,
    ) {}

    public function install(string $stubsPath, string $basePath, bool $force, InstallReport $report): void
    {
        $map = [
            'config/owl-admin.php' => config('owl-admin-kit.paths.config', 'config/owl-admin.php'),
        ];

        $published = $this->publisher->publish($stubsPath, $basePath, $map, $force);

        if ($published === []) {
            $report->addWarning('Config: nothing published (files may already exist; use --force).');
        } else {
            foreach ($published as $path) {
                $report->addStep("Config published: {$path}");
            }
        }
    }
}
