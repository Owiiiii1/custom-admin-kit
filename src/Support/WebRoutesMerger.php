<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Support\Facades\File;

class WebRoutesMerger
{
    public const PAGES_ROUTES = 'routes/owl-admin-pages.php';

    public const WEB_ROUTES = 'routes/web.php';

    private const PAGES_STUB = 'stubs/routes/owl-admin-pages.php';

    private const WEB_SNIPPET = 'docs/merge-snippets/web.php';

    private const INCLUDE_LINE = "require __DIR__.'/owl-admin-pages.php';";

    public function analyze(string $basePath): WebRoutesAnalysis
    {
        $hasInertiaDependency = $this->hasInertiaDependency($basePath);
        $inertiaHint = 'composer require inertiajs/inertia-laravel';
        $pagesFileExists = File::exists($basePath.'/'.self::PAGES_ROUTES);
        $pagesFileStatus = $pagesFileExists ? WebRoutesAnalysis::STATUS_EXISTS : WebRoutesAnalysis::STATUS_MISSING;

        $webPath = $basePath.'/'.self::WEB_ROUTES;

        if (! File::exists($webPath)) {
            return new WebRoutesAnalysis(
                pagesFileStatus: $pagesFileStatus,
                webRoutesStatus: WebRoutesAnalysis::STATUS_MISSING,
                hasInclude: false,
                hasInertiaDependency: $hasInertiaDependency,
                action: WebRoutesAnalysis::ACTION_BLOCKED,
                reason: 'routes/web.php is missing.',
                shouldCreatePagesFile: ! $pagesFileExists && $hasInertiaDependency,
                inertiaInstallHint: $hasInertiaDependency ? null : $inertiaHint,
            );
        }

        $webContents = (string) file_get_contents($webPath);
        $hasInclude = $this->hasInclude($webContents);
        $isStandardWebRoutes = $this->isStandardWebRoutes($webContents);
        $webRoutesStatus = $isStandardWebRoutes
            ? WebRoutesAnalysis::STATUS_EXISTS
            : WebRoutesAnalysis::STATUS_NON_STANDARD;

        if ($hasInclude && $pagesFileExists) {
            return new WebRoutesAnalysis(
                pagesFileStatus: $pagesFileStatus,
                webRoutesStatus: $webRoutesStatus,
                hasInclude: true,
                hasInertiaDependency: $hasInertiaDependency,
                action: WebRoutesAnalysis::ACTION_OK,
                reason: 'owl-admin-pages.php is included from routes/web.php.',
            );
        }

        if (! $hasInertiaDependency) {
            return new WebRoutesAnalysis(
                pagesFileStatus: $pagesFileStatus,
                webRoutesStatus: $webRoutesStatus,
                hasInclude: $hasInclude,
                hasInertiaDependency: false,
                action: WebRoutesAnalysis::ACTION_BLOCKED,
                reason: 'inertiajs/inertia-laravel is required for core admin page routes.',
                shouldCreatePagesFile: false,
                shouldMergeWebInclude: false,
                inertiaInstallHint: $inertiaHint,
            );
        }

        if (! $isStandardWebRoutes) {
            return new WebRoutesAnalysis(
                pagesFileStatus: $pagesFileStatus,
                webRoutesStatus: $webRoutesStatus,
                hasInclude: $hasInclude,
                hasInertiaDependency: true,
                action: WebRoutesAnalysis::ACTION_MANUAL,
                reason: 'Non-standard routes/web.php. Add the include manually.',
                shouldCreatePagesFile: ! $pagesFileExists,
                manualSnippetPath: $this->manualSnippetPath(),
            );
        }

        $shouldCreatePages = ! $pagesFileExists;
        $shouldMergeInclude = ! $hasInclude;

        if (! $shouldCreatePages && ! $shouldMergeInclude) {
            return new WebRoutesAnalysis(
                pagesFileStatus: $pagesFileStatus,
                webRoutesStatus: $webRoutesStatus,
                hasInclude: true,
                hasInertiaDependency: true,
                action: WebRoutesAnalysis::ACTION_OK,
                reason: 'Core admin routes file and web.php include are configured.',
            );
        }

        return new WebRoutesAnalysis(
            pagesFileStatus: $pagesFileStatus,
            webRoutesStatus: $webRoutesStatus,
            hasInclude: $hasInclude,
            hasInertiaDependency: true,
            action: $shouldMergeInclude
                ? WebRoutesAnalysis::ACTION_AUTO_MERGE
                : WebRoutesAnalysis::ACTION_CREATE,
            reason: $this->buildReason($shouldCreatePages, $shouldMergeInclude),
            shouldCreatePagesFile: $shouldCreatePages,
            shouldMergeWebInclude: $shouldMergeInclude,
        );
    }

    public function hasInclude(string $basePathOrContents): bool
    {
        $contents = $this->resolveWebContents($basePathOrContents);

        return str_contains($contents, 'owl-admin-pages.php');
    }

    public function canAutoCreatePages(string $basePath): bool
    {
        return $this->analyze($basePath)->canAutoCreatePages();
    }

    public function canAutoMergeInclude(string $basePath): bool
    {
        return $this->analyze($basePath)->canAutoMergeInclude();
    }

    public function dryRun(string $basePath): WebRoutesAnalysis
    {
        return $this->analyze($basePath);
    }

    public function apply(string $basePath, ?WebRoutesAnalysis $analysis = null, bool $dryRun = false): bool
    {
        $analysis ??= $this->analyze($basePath);

        if (! $analysis->hasChanges()) {
            return true;
        }

        if ($dryRun) {
            return true;
        }

        if ($analysis->shouldCreatePagesFile) {
            $stub = $this->pagesStubContents();

            if ($stub === null) {
                return false;
            }

            File::ensureDirectoryExists($basePath.'/routes');
            File::put($basePath.'/'.self::PAGES_ROUTES, $stub);
        }

        if ($analysis->shouldMergeWebInclude) {
            $path = $basePath.'/'.self::WEB_ROUTES;
            $contents = (string) file_get_contents($path);
            $updated = $this->injectInclude($contents);

            if ($updated === null) {
                return false;
            }

            File::put($path, $updated);
        }

        return true;
    }

    /**
     * @return list<array{file: string, action: string, detail: string, analysis?: WebRoutesAnalysis}>
     */
    public function plan(string $basePath): array
    {
        $analysis = $this->analyze($basePath);
        $steps = [];

        if ($analysis->shouldCreatePagesFile) {
            $steps[] = [
                'file' => self::PAGES_ROUTES,
                'action' => 'create',
                'detail' => 'Create core admin page routes from package stub.',
                'analysis' => $analysis,
            ];
        }

        if ($analysis->shouldMergeWebInclude) {
            $steps[] = [
                'file' => self::WEB_ROUTES,
                'action' => 'merge',
                'detail' => "Add require __DIR__.'/owl-admin-pages.php'; to routes/web.php.",
                'analysis' => $analysis,
            ];
        }

        if ($steps === []) {
            $stepAction = match ($analysis->action) {
                WebRoutesAnalysis::ACTION_MANUAL, WebRoutesAnalysis::ACTION_BLOCKED => 'blocked',
                default => 'skip',
            };

            return [[
                'file' => self::PAGES_ROUTES,
                'action' => $stepAction,
                'detail' => $analysis->reason,
                'analysis' => $analysis,
            ]];
        }

        return $steps;
    }

    public function manualSnippetPath(): string
    {
        return dirname(__DIR__, 2).'/'.self::WEB_SNIPPET;
    }

    public function manualSnippetRelativePath(): string
    {
        return self::WEB_SNIPPET;
    }

    private function hasInertiaDependency(string $basePath): bool
    {
        return class_exists(\Inertia\Inertia::class)
            || file_exists($basePath.'/vendor/inertiajs/inertia-laravel');
    }

    private function isStandardWebRoutes(string $contents): bool
    {
        if (! str_contains($contents, '<?php')) {
            return false;
        }

        if (! preg_match('/\bRoute::/', $contents)) {
            return false;
        }

        if (str_contains($contents, 'eval(')) {
            return false;
        }

        return true;
    }

    private function buildReason(bool $shouldCreatePages, bool $shouldMergeInclude): string
    {
        $parts = [];

        if ($shouldCreatePages) {
            $parts[] = 'create routes/owl-admin-pages.php';
        }

        if ($shouldMergeInclude) {
            $parts[] = 'add include to routes/web.php';
        }

        return 'Core admin routes setup: '.implode('; ', $parts).'.';
    }

    private function injectInclude(string $contents): ?string
    {
        if ($this->hasInclude($contents)) {
            return $contents;
        }

        $includeBlock = "\n// Owl Admin core pages (v0.2)\n".self::INCLUDE_LINE."\n";

        if (preg_match("/\nrequire __DIR__\.'\/auth\.php';/", $contents)) {
            $updated = preg_replace(
                "/(\nrequire __DIR__\.'\/auth\.php';)/",
                $includeBlock.'$1',
                $contents,
                1,
            );

            return is_string($updated) ? $updated : null;
        }

        return rtrim($contents).$includeBlock;
    }

    private function pagesStubContents(): ?string
    {
        $path = dirname(__DIR__, 2).'/'.self::PAGES_STUB;

        if (! is_file($path)) {
            return null;
        }

        $contents = (string) file_get_contents($path);

        if (! str_starts_with(trim($contents), '<?php')) {
            $contents = "<?php\n\n".$contents;
        }

        if (! str_ends_with($contents, "\n")) {
            $contents .= "\n";
        }

        return $contents;
    }

    private function resolveWebContents(string $basePathOrContents): string
    {
        if (str_contains($basePathOrContents, 'Route::') || str_contains($basePathOrContents, '<?php')) {
            return $basePathOrContents;
        }

        $path = str_ends_with($basePathOrContents, '.php')
            ? $basePathOrContents
            : $basePathOrContents.'/'.self::WEB_ROUTES;

        return is_file($path) ? (string) file_get_contents($path) : '';
    }
}
