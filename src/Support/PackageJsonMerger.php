<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Support\Facades\File;

class PackageJsonMerger
{
    public function __construct(
        private readonly FrontendDependencyChecker $dependencies,
    ) {}

    /**
     * @return list<array{file: string, action: string, detail: string}>
     */
    public function plan(string $basePath, string $preset): array
    {
        $relativePath = 'package.json';
        $absolutePath = $basePath.'/'.$relativePath;

        if (! File::exists($absolutePath)) {
            return [[
                'file' => $relativePath,
                'action' => 'blocked',
                'detail' => 'package.json not found.',
            ]];
        }

        $missing = $this->dependencies->missingPackages($basePath, $preset);

        if ($missing === []) {
            return [[
                'file' => $relativePath,
                'action' => 'skip',
                'detail' => 'All required npm packages already listed in package.json.',
            ]];
        }

        return [[
            'file' => $relativePath,
            'action' => 'merge',
            'detail' => 'Add missing npm packages: '.implode(', ', $missing),
        ]];
    }

    /**
     * @param  list<string>  $packages
     */
    public function apply(string $basePath, array $packages): bool
    {
        if ($packages === []) {
            return true;
        }

        $path = $basePath.'/package.json';
        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            return false;
        }

        $decoded['devDependencies'] ??= [];

        foreach ($packages as $package) {
            if (! isset($decoded['dependencies'][$package]) && ! isset($decoded['devDependencies'][$package])) {
                $decoded['devDependencies'][$package] = '*';
            }
        }

        ksort($decoded['devDependencies']);

        File::put($path, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        return true;
    }
}
