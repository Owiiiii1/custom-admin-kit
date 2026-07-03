<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Support\Facades\File;

class RequiredEnvChecker
{
    /**
     * Validate admin seed env keys. Optional unless install uses --seed.
     *
     * @return list<CheckResult>
     */
    public function checkAdminSeed(string $basePath, bool $forSeed, bool $strict, bool $interactive): array
    {
        if (! config('owl-admin-kit.admin_user.enabled', true)) {
            return [
                CheckResult::pass('seed-env', 'Admin user seeding disabled (admin_user.enabled = false).'),
            ];
        }

        $values = $this->readEnv($basePath.'/.env');
        $results = [];

        foreach (config('owl-admin-kit.admin_seed_env', []) as $entry) {
            $key = (string) $entry['key'];
            $description = (string) ($entry['description'] ?? $key);
            $value = $values[$key] ?? env($key);
            $present = is_string($value) && $value !== '';
            $label = "seed-env:{$key}";

            if ($key === (string) config('owl-admin-kit.admin_user.password_env', 'OWL_ADMIN_PASSWORD')) {
                $present = $present || ($forSeed && $this->generatedPasswordAllowed($basePath));
            }

            if ($present) {
                $results[] = CheckResult::pass($label, "{$key} is set.");

                continue;
            }

            if (! $forSeed) {
                $results[] = CheckResult::warn(
                    $label,
                    "Optional (required only with --seed): {$key} ({$description})",
                    'Set in .env before php artisan owl-admin:install --seed',
                );

                continue;
            }

            if ($key === 'OWL_ADMIN_PASSWORD' && ($interactive || $this->generatedPasswordAllowed($basePath))) {
                $results[] = CheckResult::warn(
                    $label,
                    "{$key} not set — will be prompted or auto-generated in local dev.",
                    'Set OWL_ADMIN_PASSWORD in .env for non-interactive installs.',
                );

                continue;
            }

            if ($key === 'OWL_ADMIN_EMAIL' && $interactive) {
                $results[] = CheckResult::warn(
                    $label,
                    "{$key} not set — interactive install will prompt for email.",
                    'Set OWL_ADMIN_EMAIL in .env for non-interactive installs.',
                );

                continue;
            }

            $hint = 'Set '.$key.' in .env — installer never writes credentials automatically.';

            if ($strict) {
                $results[] = CheckResult::fail(
                    $label,
                    "Required for --seed (non-interactive): {$key} ({$description})",
                    $hint,
                );
            } else {
                $results[] = CheckResult::warn(
                    $label,
                    "Missing for --seed: {$key} ({$description})",
                    $hint,
                );
            }
        }

        return $results;
    }

    private function generatedPasswordAllowed(string $basePath): bool
    {
        $values = $this->readEnv($basePath.'/.env');
        $allow = filter_var(
            $values['OWL_ADMIN_ALLOW_DEFAULT_PASSWORD'] ?? env('OWL_ADMIN_ALLOW_DEFAULT_PASSWORD', false),
            FILTER_VALIDATE_BOOL,
        );
        $appEnv = $values['APP_ENV'] ?? env('APP_ENV', 'production');

        return $allow && $appEnv === 'local';
    }

    /**
     * @return array<string, string>
     */
    private function readEnv(string $path): array
    {
        if (! File::exists($path)) {
            return [];
        }

        $values = [];

        foreach (File::lines($path) as $line) {
            $line = trim((string) $line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $values[trim($key)] = trim($value, " \t\"'");
        }

        return $values;
    }
}
