<?php

namespace OwlSolutions\CustomAdminKit\Installers;

use OwlSolutions\CustomAdminKit\Support\InstallReport;

class FilamentInstaller
{
    public function install(InstallReport $report): void
    {
        if (! config('owl-admin-kit.features.filament', false)) {
            $report->addStep('Filament: skipped (feature disabled in config).');

            return;
        }

        $report->addWarning('Filament: installer skeleton — not implemented yet.');
    }
}
