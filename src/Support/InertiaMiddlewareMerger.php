<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Support\Facades\File;

class InertiaMiddlewareMerger
{
    /**
     * @return list<array{file: string, action: string, detail: string}>
     */
    public function plan(string $basePath): array
    {
        $relativePath = 'app/Http/Middleware/HandleInertiaRequests.php';
        $absolutePath = $basePath.'/'.$relativePath;

        if (! File::exists($absolutePath)) {
            return [[
                'file' => $relativePath,
                'action' => 'blocked',
                'detail' => 'HandleInertiaRequests middleware not found. Install inertiajs/inertia-laravel first.',
            ]];
        }

        $contents = (string) file_get_contents($absolutePath);
        $propKey = (string) config('owl-admin-kit.branding.inertia_prop_key', 'owlAdmin');

        if (str_contains($contents, $propKey)) {
            return [[
                'file' => $relativePath,
                'action' => 'skip',
                'detail' => "Shared Inertia prop [{$propKey}] already present.",
            ]];
        }

        return [[
            'file' => $relativePath,
            'action' => 'merge',
            'detail' => "Share {$propKey}.brand_name and {$propKey}.logo_path from config('owl-admin').",
        ]];
    }

    public function apply(string $basePath): bool
    {
        $relativePath = 'app/Http/Middleware/HandleInertiaRequests.php';
        $path = $basePath.'/'.$relativePath;
        $contents = (string) file_get_contents($path);
        $propKey = (string) config('owl-admin-kit.branding.inertia_prop_key', 'owlAdmin');

        if (str_contains($contents, $propKey)) {
            return true;
        }

        $shareBlock = <<<PHP
            '{$propKey}' => [
                'brand_name' => config('owl-admin.brand_name', config('owl-admin.name')),
                'logo_path' => config('owl-admin.logo_path'),
            ],

PHP;

        if (preg_match('/function share\(.*?\).*?\R\s*return \[\R/s', $contents)) {
            $contents = preg_replace(
                '/(return \[)\R/',
                "$1\n{$shareBlock}",
                $contents,
                1,
            ) ?? $contents;
        }

        File::put($path, $contents);

        return true;
    }
}
