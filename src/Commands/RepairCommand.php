<?php

namespace OwlSolutions\CustomAdminKit\Commands;

use OwlSolutions\CustomAdminKit\Support\InstallState;
use OwlSolutions\CustomAdminKit\Support\PublishMapResolver;

class RepairCommand extends BaseKitCommand
{
    protected $signature = 'owl-admin:repair
                            {--preset=core : Preset to republish (v0.1: core only)}
                            {--force : Overwrite existing files}
                            {--backup : Backup before overwrite}';

    protected $description = 'Republish core admin kit stubs';

    public function handle(PublishMapResolver $publishMap): int
    {
        $this->printBanner('Repair');

        $preset = (string) $this->option('preset');

        if (! $publishMap->isPresetAvailable($preset)) {
            $this->error($publishMap->unavailablePresetMessage($preset) ?? 'Preset unavailable.');

            return self::FAILURE;
        }

        $state = new InstallState(base_path(config('owl-admin-kit.state_file')));

        if (! $state->exists()) {
            $this->warn('Install state not found — running install.');
        }

        return $this->call('owl-admin:install', [
            '--preset' => $preset,
            '--force' => $this->option('force') ?: true,
            '--backup' => $this->option('backup'),
        ]);
    }
}
