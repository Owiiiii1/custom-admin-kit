<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Support\Facades\File;

class ViteConfigMerger
{
    /**
     * @return list<array{file: string, action: string, detail: string}>
     */
    public function plan(string $basePath): array
    {
        $relativePath = 'vite.config.js';
        $absolutePath = $basePath.'/'.$relativePath;

        if (! File::exists($absolutePath)) {
            return [[
                'file' => $relativePath,
                'action' => 'blocked',
                'detail' => 'vite.config.js not found.',
            ]];
        }

        $contents = (string) file_get_contents($absolutePath);
        $steps = [];

        if (! str_contains($contents, '@vitejs/plugin-react')) {
            $steps[] = [
                'file' => $relativePath,
                'action' => 'merge',
                'detail' => 'Import and register @vitejs/plugin-react.',
            ];
        }

        if (! str_contains($contents, 'laravel-vite-plugin')) {
            $steps[] = [
                'file' => $relativePath,
                'action' => 'merge',
                'detail' => 'Import and register laravel-vite-plugin.',
            ];
        }

        if (! str_contains($contents, "'@'") && ! str_contains($contents, '"@"')) {
            $steps[] = [
                'file' => $relativePath,
                'action' => 'merge',
                'detail' => 'Add resolve.alias @ -> resources/js for shadcn imports.',
            ];
        }

        if ($steps === []) {
            return [[
                'file' => $relativePath,
                'action' => 'skip',
                'detail' => 'React/Vite plugins and @ alias appear configured.',
            ]];
        }

        return $steps;
    }

    public function apply(string $basePath): bool
    {
        $path = $basePath.'/vite.config.js';
        $contents = (string) file_get_contents($path);

        if (! str_contains($contents, '@vitejs/plugin-react')) {
            if (! str_contains($contents, "import react from '@vitejs/plugin-react'")) {
                $contents = preg_replace(
                    "/(import defineConfig from 'vite';?\R)/",
                    "$1import react from '@vitejs/plugin-react';\n",
                    $contents,
                    1,
                ) ?? $contents;
            }
        }

        if (! str_contains($contents, 'laravel-vite-plugin')) {
            if (! str_contains($contents, "import laravel from 'laravel-vite-plugin'")) {
                $contents = preg_replace(
                    "/(import defineConfig from 'vite';?\R)/",
                    "$1import laravel from 'laravel-vite-plugin';\n",
                    $contents,
                    1,
                ) ?? $contents;
            }
        }

        if (! str_contains($contents, 'resolve:')) {
            $contents = str_replace(
                'export default defineConfig({',
                "export default defineConfig({\n    resolve: {\n        alias: {\n            '@': '/resources/js',\n        },\n    },",
                $contents,
            );
        }

        File::put($path, $contents);

        return true;
    }
}
