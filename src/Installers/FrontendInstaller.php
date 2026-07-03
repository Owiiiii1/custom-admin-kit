<?php

namespace OwlSolutions\CustomAdminKit\Installers;

use OwlSolutions\CustomAdminKit\Support\FilePublisher;
use OwlSolutions\CustomAdminKit\Support\InstallReport;

class FrontendInstaller
{
    public function __construct(
        private readonly FilePublisher $publisher,
    ) {}

    public function install(string $stubsPath, string $basePath, bool $force, InstallReport $report): void
    {
        $map = [
            'resources/js/app.jsx.stub' => 'resources/js/vendor/owl-admin/app.jsx',
            'resources/css/app.css.stub' => 'resources/css/vendor/owl-admin/app.css',
        ];

        $published = $this->publisher->publish($stubsPath, $basePath, $map, $force);

        if ($published === []) {
            $report->addWarning('Frontend: no asset stubs published yet (skeleton installer).');
        } else {
            foreach ($published as $path) {
                $report->addStep("Frontend published: {$path}");
            }
        }
    }
}
