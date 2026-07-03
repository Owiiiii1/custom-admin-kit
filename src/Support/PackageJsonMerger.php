<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Support\Facades\File;

class PackageJsonMerger
{
    private const PACKAGE_JSON = 'package.json';

    public function analyze(string $basePath, string $preset = 'core'): PackageJsonMergePlan
    {
        $path = $basePath.'/'.self::PACKAGE_JSON;

        if (! File::exists($path)) {
            return new PackageJsonMergePlan;
        }

        $decoded = $this->decodePackageJson($path);

        if ($decoded === null) {
            return new PackageJsonMergePlan;
        }

        return $this->buildPlan($decoded, $basePath);
    }

    /**
     * @return list<array{file: string, action: string, detail: string, merge?: PackageJsonMergePlan}>
     */
    public function plan(string $basePath, string $preset = 'core'): array
    {
        $path = $basePath.'/'.self::PACKAGE_JSON;

        if (! File::exists($path)) {
            return [[
                'file' => self::PACKAGE_JSON,
                'action' => 'blocked',
                'detail' => 'package.json not found.',
            ]];
        }

        $mergePlan = $this->analyze($basePath, $preset);

        if (! $mergePlan->hasChanges()) {
            return [[
                'file' => self::PACKAGE_JSON,
                'action' => 'skip',
                'detail' => 'All required npm packages already listed in package.json.',
                'merge' => $mergePlan,
            ]];
        }

        return [[
            'file' => self::PACKAGE_JSON,
            'action' => 'merge',
            'detail' => $this->summarizePlan($mergePlan),
            'merge' => $mergePlan,
        ]];
    }

    public function apply(string $basePath, PackageJsonMergePlan $plan, bool $dryRun = false): bool
    {
        if (! $plan->hasChanges()) {
            return true;
        }

        if ($dryRun) {
            return true;
        }

        $path = $basePath.'/'.self::PACKAGE_JSON;
        $decoded = $this->decodePackageJson($path);

        if ($decoded === null) {
            return false;
        }

        $originalJson = (string) file_get_contents($path);

        if ($plan->missingDependencies !== []) {
            $decoded['dependencies'] ??= [];

            foreach ($plan->missingDependencies as $name => $version) {
                if ($this->packageExistsInManifest($decoded, $name)) {
                    continue;
                }

                $decoded['dependencies'][$name] = $version;
            }

            ksort($decoded['dependencies']);
        }

        if ($plan->missingDevDependencies !== []) {
            $decoded['devDependencies'] ??= [];

            foreach ($plan->missingDevDependencies as $name => $version) {
                if ($this->packageExistsInManifest($decoded, $name)) {
                    continue;
                }

                $decoded['devDependencies'][$name] = $version;
            }

            ksort($decoded['devDependencies']);
        }

        $encoded = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (! is_string($encoded)) {
            return false;
        }

        $encoded .= "\n";

        if ($encoded === $originalJson) {
            return true;
        }

        File::put($path, $encoded);

        return true;
    }

    /**
     * @return array<string, string>
     */
    public function configuredDependencies(): array
    {
        return $this->packageListWithVersions('dependencies');
    }

    /**
     * @return array<string, string>
     */
    public function configuredDevDependencies(string $basePath): array
    {
        $packages = $this->packageListWithVersions('dev_dependencies');
        $conditional = $this->packageListWithVersions('conditional_dev_dependencies');

        foreach ($conditional as $name => $version) {
            if ($this->shouldIncludeConditionalDevDependency($basePath, $name)) {
                $packages[$name] = $version;
            }
        }

        ksort($packages);

        return $packages;
    }

    private function buildPlan(array $decoded, string $basePath): PackageJsonMergePlan
    {
        $missingDependencies = [];
        $missingDevDependencies = [];

        foreach ($this->configuredDependencies() as $name => $version) {
            if (! $this->packageExistsInManifest($decoded, $name)) {
                $missingDependencies[$name] = $version;
            }
        }

        foreach ($this->configuredDevDependencies($basePath) as $name => $version) {
            if (! $this->packageExistsInManifest($decoded, $name)) {
                $missingDevDependencies[$name] = $version;
            }
        }

        ksort($missingDependencies);
        ksort($missingDevDependencies);

        return new PackageJsonMergePlan($missingDependencies, $missingDevDependencies);
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private function packageExistsInManifest(array $decoded, string $name): bool
    {
        foreach (['dependencies', 'devDependencies', 'peerDependencies', 'optionalDependencies'] as $section) {
            if (isset($decoded[$section][$name])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string>|null
     */
    private function decodePackageJson(string $path): ?array
    {
        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @return array<string, string>
     */
    private function packageListWithVersions(string $configKey): array
    {
        $names = config("owl-admin-kit.frontend_dependencies.{$configKey}", []);
        $versions = config('owl-admin-kit.frontend_dependencies.default_versions', []);
        $packages = [];

        foreach ($names as $name) {
            if (! is_string($name) || $name === '') {
                continue;
            }

            $packages[$name] = (string) ($versions[$name] ?? '*');
        }

        return $packages;
    }

    private function shouldIncludeConditionalDevDependency(string $basePath, string $name): bool
    {
        if ($name === '@tailwindcss/vite') {
            $viteConfig = $basePath.'/vite.config.js';

            return File::exists($viteConfig)
                && str_contains((string) file_get_contents($viteConfig), '@tailwindcss/vite');
        }

        return true;
    }

    private function summarizePlan(PackageJsonMergePlan $plan): string
    {
        $parts = [];

        if ($plan->missingDependencies !== []) {
            $parts[] = 'dependencies: '.implode(', ', array_keys($plan->missingDependencies));
        }

        if ($plan->missingDevDependencies !== []) {
            $parts[] = 'devDependencies: '.implode(', ', array_keys($plan->missingDevDependencies));
        }

        return 'Add missing npm packages ('.implode('; ', $parts).')';
    }
}
