<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Support\Facades\File;

class InertiaAppMerger
{
    /**
     * @return list<array{file: string, action: string, detail: string}>
     */
    public function plan(string $basePath): array
    {
        $relativePath = $this->resolveAppEntry($basePath);

        if ($relativePath === null) {
            return [[
                'file' => 'resources/js/app.jsx',
                'action' => 'blocked',
                'detail' => 'Neither resources/js/app.jsx nor resources/js/app.js exists.',
            ]];
        }

        $contents = (string) file_get_contents($basePath.'/'.$relativePath);
        $steps = [];

        if (! str_contains($contents, '@inertiajs/react')) {
            $steps[] = [
                'file' => $relativePath,
                'action' => 'merge',
                'detail' => 'Ensure createInertiaApp from @inertiajs/react is configured.',
            ];
        }

        if (! str_contains($contents, 'resolvePageComponent')) {
            $steps[] = [
                'file' => $relativePath,
                'action' => 'merge',
                'detail' => 'Ensure resolvePageComponent glob includes ./Pages/**/*.jsx.',
            ];
        }

        if (! str_contains($contents, 'owl-admin.css') && File::exists($basePath.'/resources/css/owl-admin.css')) {
            $steps[] = [
                'file' => $relativePath,
                'action' => 'merge',
                'detail' => "Import resources/css/owl-admin.css in the app entry.",
            ];
        }

        if ($steps === []) {
            return [[
                'file' => $relativePath,
                'action' => 'skip',
                'detail' => 'Inertia React bootstrap appears configured.',
            ]];
        }

        return $steps;
    }

    public function apply(string $basePath): bool
    {
        $relativePath = $this->resolveAppEntry($basePath);

        if ($relativePath === null) {
            return false;
        }

        $path = $basePath.'/'.$relativePath;
        $contents = (string) file_get_contents($path);

        if (! str_contains($contents, 'owl-admin.css') && File::exists($basePath.'/resources/css/owl-admin.css')) {
            $contents = "import '../css/owl-admin.css';\n".$contents;
        }

        File::put($path, $contents);

        return true;
    }

    private function resolveAppEntry(string $basePath): ?string
    {
        foreach (['resources/js/app.jsx', 'resources/js/app.js'] as $candidate) {
            if (File::exists($basePath.'/'.$candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
