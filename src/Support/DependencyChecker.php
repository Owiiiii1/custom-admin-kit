<?php

namespace OwlSolutions\CustomAdminKit\Support;

class DependencyChecker
{
    /**
     * @return list<CheckResult>
     */
    public function check(string $basePath, bool $strict = true): array
    {
        $results = [];

        foreach (config('owl-admin-kit.host_dependencies.required', []) as $name => $meta) {
            $results[] = $this->checkPackage($basePath, $name, $meta, hardFail: $strict);
        }

        foreach (config('owl-admin-kit.host_dependencies.recommended', []) as $name => $meta) {
            $results[] = $this->checkPackage($basePath, $name, $meta, hardFail: false);
        }

        foreach (config('owl-admin-kit.host_dependencies.optional', []) as $name => $meta) {
            $results[] = $this->checkPackage($basePath, $name, $meta, hardFail: false, optional: true);
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function checkPackage(
        string $basePath,
        string $name,
        array $meta,
        bool $hardFail,
        bool $optional = false,
    ): CheckResult {
        $probe = $meta['probe'] ?? null;
        $installed = false;

        if (is_string($probe) && $probe !== '') {
            if (str_starts_with($probe, 'class:')) {
                $installed = class_exists(substr($probe, 6));
            } elseif (str_starts_with($probe, 'path:')) {
                $installed = file_exists($basePath.'/'.substr($probe, 5));
            } elseif (str_starts_with($probe, 'file:')) {
                $installed = file_exists(substr($probe, 5));
            }
        }

        if (! $installed && isset($meta['composer_path'])) {
            $installed = file_exists($basePath.'/vendor/'.str_replace('/', DIRECTORY_SEPARATOR, (string) $meta['composer_path']));
        }

        $label = "dep:{$name}";

        if ($installed) {
            return CheckResult::pass($label, "Package available: {$name}");
        }

        $hint = (string) ($meta['install_hint'] ?? "composer require {$name}");

        if ($optional) {
            return CheckResult::warn($label, "Optional package not installed: {$name}", $hint);
        }

        if ($hardFail) {
            return CheckResult::fail($label, "Required host dependency missing: {$name}", $hint);
        }

        return CheckResult::warn($label, "Recommended dependency missing: {$name}", $hint);
    }
}
