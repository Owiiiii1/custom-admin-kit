<?php

namespace OwlSolutions\CustomAdminKit\Installers;

use OwlSolutions\CustomAdminKit\Support\FilePublisher;
use OwlSolutions\CustomAdminKit\Support\InstallReport;

class AuthInstaller
{
    public function __construct(
        private readonly FilePublisher $publisher,
    ) {}

    public function install(string $stubsPath, string $basePath, bool $force, InstallReport $report): void
    {
        $map = [
            'routes/auth.php' => 'routes/owl-admin-auth.php',
        ];

        $published = $this->publisher->publish($stubsPath, $basePath, $map, $force);

        if ($published === []) {
            $report->addWarning('Auth: no route stubs published yet (skeleton installer).');
        } else {
            foreach ($published as $path) {
                $report->addStep("Auth published: {$path}");
            }
        }
    }
}
