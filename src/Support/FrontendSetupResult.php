<?php

namespace OwlSolutions\CustomAdminKit\Support;

class FrontendSetupResult
{
    /**
     * @param  list<CheckResult>  $prerequisites
     * @param  list<array{file: string, action: string, detail: string}>  $planSteps
     * @param  list<string>  $missingNpm
     * @param  list<string>  $warnings
     */
    public function __construct(
        public readonly bool $ready,
        public readonly array $prerequisites,
        public readonly array $planSteps,
        public readonly array $missingNpm = [],
        public readonly array $warnings = [],
        public readonly ?string $npmInstallCommand = null,
        public readonly ?PackageJsonMergePlan $packageJsonMerge = null,
    ) {}

    public function hasBlockingFailures(): bool
    {
        foreach ($this->prerequisites as $result) {
            if ($result->isHardFailure()) {
                return true;
            }
        }

        return false;
    }

    public function requiresWrite(): bool
    {
        foreach ($this->planSteps as $step) {
            if (in_array($step['action'], ['merge', 'create'], true)) {
                return true;
            }
        }

        return false;
    }
}
