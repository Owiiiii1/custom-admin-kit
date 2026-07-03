<?php

namespace OwlSolutions\CustomAdminKit\Installers;

use OwlSolutions\CustomAdminKit\Support\FilePublisher;
use OwlSolutions\CustomAdminKit\Support\InstallReport;

class DatabaseInstaller
{
    public function __construct(
        private readonly FilePublisher $publisher,
    ) {}

    public function install(string $stubsPath, string $basePath, bool $force, InstallReport $report): void
    {
        $map = [
            'database/migrations/.gitkeep' => 'database/migrations/owl-admin/.gitkeep',
        ];

        $published = $this->publisher->publish($stubsPath, $basePath, $map, $force);

        if ($published === []) {
            $report->addWarning('Database: migration stub directory not published (skeleton installer).');
        } else {
            foreach ($published as $path) {
                $report->addStep("Database published: {$path}");
            }
        }
    }
}
