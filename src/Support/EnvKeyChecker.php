<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Support\Facades\File;

class EnvKeyChecker
{
    /**
     * @return list<CheckResult>
     */
    public function check(string $basePath): array
    {
        $results = [];
        $values = $this->readEnv($basePath.'/.env');

        foreach (config('owl-admin-kit.required_env', []) as $entry) {
            $key = (string) $entry['key'];
            $required = (bool) ($entry['required'] ?? false);
            $value = $values[$key] ?? null;
            $present = $value !== null && $value !== '';
            $label = "env:{$key}";

            if ($present) {
                $results[] = CheckResult::pass($label, "{$key} is set.");
                continue;
            }

            $description = (string) ($entry['description'] ?? $key);

            if ($required) {
                $results[] = CheckResult::fail(
                    $label,
                    "Required env key missing or empty: {$key} ({$description})",
                    'Set the key in .env — installer never writes .env automatically.'
                );
            } else {
                $results[] = CheckResult::warn(
                    $label,
                    "Optional env key not set: {$key} ({$description})",
                    'Feature may be limited until configured.'
                );
            }
        }

        return $results;
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
