<?php

namespace OwlSolutions\CustomAdminKit\Commands;

use OwlSolutions\CustomAdminKit\Support\InstallState;

class UninstallCommand extends BaseKitCommand
{
    protected $signature = 'owl-admin:uninstall
                            {--keep-files : Remove install state only, keep published files}';

    protected $description = 'Remove custom admin kit install state (optionally keep published files)';

    public function handle(): int
    {
        $this->printBanner('Uninstall');

        $state = new InstallState(base_path(config('owl-admin-kit.state_file', 'storage/app/owl-admin-kit.json')));

        if (! $state->exists()) {
            $this->warn('Nothing to uninstall — install state file not found.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Remove custom admin kit install state?', false)) {
            $this->line('Uninstall cancelled.');

            return self::SUCCESS;
        }

        $state->delete();
        $this->info('Install state removed.');

        if ($this->option('keep-files')) {
            $this->line('Published files were kept (--keep-files).');
        } else {
            $this->warn('Published files were NOT deleted automatically.');
            $this->line('Remove published config, routes, and assets manually if needed.');
        }

        return self::SUCCESS;
    }
}
