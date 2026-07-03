<?php

namespace OwlSolutions\CustomAdminKit\Commands;

use Illuminate\Console\Command;
use OwlSolutions\CustomAdminKit\Support\CheckResult;
use OwlSolutions\CustomAdminKit\Support\FrontendDependencyChecker;
use OwlSolutions\CustomAdminKit\Support\AdminUserCredentialResolver;
use OwlSolutions\CustomAdminKit\Support\RequiredEnvChecker;

abstract class BaseKitCommand extends Command
{
    protected function printBanner(string $title): void
    {
        $this->newLine();
        $this->line('<fg=cyan;options=bold>OwlSolutions Custom Admin Kit</>');
        $this->line("<fg=gray>{$title}</>");
        $this->newLine();
    }

    /**
     * @param  list<CheckResult>  $results
     */
    protected function renderCheckResults(array $results): int
    {
        $failures = 0;
        $warnings = 0;

        foreach ($results as $result) {
            if ($result->passed && $result->isWarning()) {
                $warnings++;
                $this->line("  <fg=yellow>!</> {$result->name}: {$result->message}");
                if ($result->hint) {
                    foreach (explode("\n", $result->hint) as $hintLine) {
                        if ($hintLine === '') {
                            continue;
                        }

                        $this->line('    '.$hintLine);
                    }
                }
                continue;
            }

            if ($result->passed) {
                $this->line("  <fg=green>✓</> {$result->name}: {$result->message}");
            } else {
                $failures++;
                $this->line("  <fg=red>✗</> {$result->name}: {$result->message}");
                if ($result->hint) {
                    foreach (explode("\n", $result->hint) as $hintLine) {
                        if ($hintLine === '') {
                            continue;
                        }

                        $this->line('    '.$hintLine);
                    }
                }
            }
        }

        if ($warnings > 0) {
            $this->line("  <fg=yellow>{$warnings} warning(s)</>");
        }

        return $failures;
    }

    protected function renderFrontendPreflight(
        FrontendDependencyChecker $frontend,
        string $basePath,
        string $preset,
        bool $willInstallDeps,
        bool $strict = false,
    ): bool {
        $required = $frontend->requiresFrontend($preset);
        $missing = $required ? $frontend->missingPackages($basePath, $preset) : [];
        $blocked = $strict && $required && $missing !== [] && ! $willInstallDeps;

        $this->info('Frontend preflight:');
        $this->line('  <fg=gray>→ frontend required: '.($required ? 'yes' : 'no').'</>');

        if ($required) {
            if ($missing === []) {
                $this->line('  <fg=green>✓</> All required npm packages are installed.');
            } else {
                $this->line('  <fg=yellow>!</> Missing npm packages: '.implode(', ', $missing));
                $this->line('  <fg=yellow>→</> '.$frontend->buildInstallCommand($missing));
                if (! $strict) {
                    $this->line('  <fg=gray>→ install will continue (stubs only); run npm install before build</>');
                }
            }
        }

        $this->line('  <fg=gray>→ installation will be blocked: '.($blocked ? 'yes' : 'no').'</>');

        if ($willInstallDeps && $missing !== []) {
            $this->line('  <fg=cyan>→</> --install-frontend-deps will run npm install for missing packages.');
        }

        $this->newLine();

        return $blocked;
    }

    protected function renderAdminSeedPreflight(
        RequiredEnvChecker $seedEnv,
        AdminUserCredentialResolver $credentials,
        string $basePath,
        bool $forSeed,
        bool $interactive,
        bool $dryRun,
    ): bool {
        if (! $forSeed) {
            return false;
        }

        if (! config('owl-admin-kit.admin_user.enabled', true)) {
            $this->warn('  --seed ignored: admin_user.enabled is false.');
            $this->newLine();

            return false;
        }

        $this->info('Admin user seed preflight:');

        $strict = ! $interactive && ! $dryRun;
        $failures = $this->renderCheckResults(
            $seedEnv->checkAdminSeed($basePath, true, $strict, $interactive),
        );

        $blocked = $failures > 0;

        if ($blocked) {
            $this->line('  <fg=gray>→ admin seed will be blocked in non-interactive mode</>');
        } elseif (! $interactive && $credentials->canResolveWithoutInteraction()) {
            $this->line('  <fg=green>✓</> Credentials available from env/config.');
        } elseif ($interactive) {
            $this->line('  <fg=cyan>→</> Interactive mode can prompt for missing credentials.');
        }

        $this->newLine();

        return $blocked;
    }
}
