<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Support\Facades\File;

class InertiaMiddlewareMerger
{
    private const MIDDLEWARE = 'app/Http/Middleware/HandleInertiaRequests.php';

    private const SNIPPET = 'docs/merge-snippets/HandleInertiaRequests.php';

    public function analyze(string $basePath): InertiaMiddlewareAnalysis
    {
        $path = $basePath.'/'.self::MIDDLEWARE;

        if (! File::exists($path)) {
            return new InertiaMiddlewareAnalysis(
                status: InertiaMiddlewareAnalysis::STATUS_MISSING,
                hasOwlAdminShare: false,
                action: InertiaMiddlewareAnalysis::ACTION_BLOCKED,
                reason: 'HandleInertiaRequests middleware is missing.',
                installHint: $this->installHint(),
            );
        }

        $contents = (string) file_get_contents($path);
        $propKey = $this->inertiaPropKey();
        $hasOwlAdminShare = $this->containsOwlAdminShare($contents, $propKey);
        $hasStandardShareMethod = $this->hasStandardShareMethod($contents);

        if ($hasOwlAdminShare) {
            return new InertiaMiddlewareAnalysis(
                status: InertiaMiddlewareAnalysis::STATUS_EXISTS,
                hasOwlAdminShare: true,
                action: InertiaMiddlewareAnalysis::ACTION_OK,
                reason: "Shared Inertia prop [{$propKey}] already present.",
                hasStandardShareMethod: $hasStandardShareMethod,
            );
        }

        if (! $hasStandardShareMethod) {
            return new InertiaMiddlewareAnalysis(
                status: InertiaMiddlewareAnalysis::STATUS_EXISTS,
                hasOwlAdminShare: false,
                action: InertiaMiddlewareAnalysis::ACTION_MANUAL,
                reason: 'Non-standard HandleInertiaRequests::share() method. Manual merge required.',
                hasStandardShareMethod: false,
                manualSnippetPath: $this->manualSnippetPath(),
            );
        }

        return new InertiaMiddlewareAnalysis(
            status: InertiaMiddlewareAnalysis::STATUS_EXISTS,
            hasOwlAdminShare: false,
            action: InertiaMiddlewareAnalysis::ACTION_AUTO_MERGE,
            reason: "Add shared Inertia prop [{$propKey}] from owl-admin config.",
            hasStandardShareMethod: true,
        );
    }

    public function hasOwlAdminShare(string $basePath): bool
    {
        return $this->analyze($basePath)->hasOwlAdminShare;
    }

    public function canAutoMerge(string $basePath): bool
    {
        return $this->analyze($basePath)->canAutoMerge();
    }

    public function dryRun(string $basePath): InertiaMiddlewareAnalysis
    {
        return $this->analyze($basePath);
    }

    public function apply(string $basePath, ?InertiaMiddlewareAnalysis $analysis = null, bool $dryRun = false): bool
    {
        $analysis ??= $this->analyze($basePath);

        if (! $analysis->canAutoMerge()) {
            return false;
        }

        if ($dryRun) {
            return true;
        }

        $path = $basePath.'/'.self::MIDDLEWARE;
        $contents = (string) file_get_contents($path);
        $updated = $this->injectOwlAdminShare($contents);

        if ($updated === null || $updated === $contents) {
            return false;
        }

        File::put($path, $updated);

        return true;
    }

    /**
     * @return list<array{file: string, action: string, detail: string, analysis?: InertiaMiddlewareAnalysis}>
     */
    public function plan(string $basePath): array
    {
        $analysis = $this->analyze($basePath);

        $stepAction = match ($analysis->action) {
            InertiaMiddlewareAnalysis::ACTION_AUTO_MERGE => 'merge',
            InertiaMiddlewareAnalysis::ACTION_BLOCKED => 'blocked',
            InertiaMiddlewareAnalysis::ACTION_MANUAL => 'blocked',
            default => 'skip',
        };

        return [[
            'file' => self::MIDDLEWARE,
            'action' => $stepAction,
            'detail' => $analysis->reason,
            'analysis' => $analysis,
        ]];
    }

    public function manualSnippetPath(): string
    {
        return dirname(__DIR__, 2).'/'.self::SNIPPET;
    }

    public function manualSnippetRelativePath(): string
    {
        return self::SNIPPET;
    }

    public function installHint(): string
    {
        return 'composer require inertiajs/inertia-laravel && php artisan inertia:middleware';
    }

    private function containsOwlAdminShare(string $contents, string $propKey): bool
    {
        return str_contains($contents, "'{$propKey}'")
            || str_contains($contents, "\"{$propKey}\"");
    }

    private function inertiaPropKey(): string
    {
        return (string) config('owl-admin-kit.branding.inertia_prop_key', 'owlAdmin');
    }

    private function hasStandardShareMethod(string $contents): bool
    {
        if (! preg_match('/function\s+share\s*\(\s*Request\s+\$request\s*\)\s*:\s*array\s*\{/s', $contents)) {
            return false;
        }

        if (preg_match('/return\s+array_merge\s*\(\s*parent::share\s*\(\s*\$request\s*\)\s*,\s*\[/s', $contents)) {
            return true;
        }

        return (bool) preg_match('/return\s*\[\s*\.\.\.\s*parent::share\s*\(\s*\$request\s*\)/s', $contents);
    }

    private function injectOwlAdminShare(string $contents): ?string
    {
        $propKey = $this->inertiaPropKey();
        $shareLine = $this->shareLine($propKey);

        if (preg_match('/return\s+array_merge\s*\(\s*parent::share\s*\(\s*\$request\s*\)\s*,\s*\[\s*/s', $contents)) {
            $updated = preg_replace(
                '/(return\s+array_merge\s*\(\s*parent::share\s*\(\s*\$request\s*\)\s*,\s*\[\s*)/s',
                "$1\n{$shareLine}",
                $contents,
                1,
            );

            return is_string($updated) ? $updated : null;
        }

        if (preg_match('/return\s*\[\s*\.\.\.\s*parent::share\s*\(\s*\$request\s*\)\s*,?\s*/s', $contents)) {
            $updated = preg_replace(
                '/(return\s*\[\s*\.\.\.\s*parent::share\s*\(\s*\$request\s*\)\s*,?\s*)/s',
                "$1\n{$shareLine}",
                $contents,
                1,
            );

            return is_string($updated) ? $updated : null;
        }

        return null;
    }

    private function shareLine(string $propKey): string
    {
        return <<<PHP
            '{$propKey}' => fn () => config('owl-admin.branding', [
                'brand_name' => config('owl-admin.brand_name', config('owl-admin.name', 'Service Admin')),
                'logo_path' => config('owl-admin.logo_path', '/images/company-logo.svg'),
            ]),

PHP;
    }
}
