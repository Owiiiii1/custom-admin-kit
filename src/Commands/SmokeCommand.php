<?php

namespace OwlSolutions\CustomAdminKit\Commands;

use OwlSolutions\CustomAdminKit\Support\InstallState;
use OwlSolutions\CustomAdminKit\Support\PublishMapResolver;
use OwlSolutions\CustomAdminKit\Support\SmokeTester;

class SmokeCommand extends BaseKitCommand
{
    protected $signature = 'owl-admin:smoke
                            {--preset=core : Preset context (v0.1: core only)}';

    protected $description = 'Run post-install smoke checks for core admin kit';

    public function handle(SmokeTester $tester, PublishMapResolver $publishMap): int
    {
        $this->printBanner('Smoke test');

        $preset = (string) $this->option('preset');

        if (! $publishMap->isPresetAvailable($preset)) {
            $this->error($publishMap->unavailablePresetMessage($preset) ?? "Preset unavailable.");

            return self::FAILURE;
        }

        $state = new InstallState(base_path(config('owl-admin-kit.state_file')));
        $results = $tester->run(base_path(), $state->read(), $preset);

        $coreResults = [];
        $frontendResults = [];

        foreach ($results as $result) {
            if ($result->section === SmokeTester::SECTION_FRONTEND_SETUP) {
                $frontendResults[] = $result;
            } else {
                $coreResults[] = $result;
            }
        }

        $this->info('Core:');
        $failures = $this->renderCheckResults($coreResults);

        $this->newLine();
        $this->info('Frontend setup:');
        $failures += $this->renderCheckResults($frontendResults);

        $this->newLine();

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}
