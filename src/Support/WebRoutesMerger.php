<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Support\Facades\File;

class WebRoutesMerger
{
    public const PAGES_ROUTES = 'routes/owl-admin-pages.php';
    public const AUTH_ROUTES = 'routes/owl-admin-auth.php';

    public const WEB_ROUTES = 'routes/web.php';

    private const PAGES_STUB = 'stubs/routes/owl-admin-pages.php';
    private const ADMIN_PAGES_STUB = 'stubs/presets/admin/routes/owl-admin-pages.php';
    private const AUTH_STUB = 'stubs/presets/admin/routes/owl-admin-auth.php';

    private const WEB_SNIPPET = 'docs/merge-snippets/web.php';

    private const INCLUDE_PAGES_LINE = "require __DIR__.'/owl-admin-pages.php';";
    private const INCLUDE_AUTH_LINE = "require __DIR__.'/owl-admin-auth.php';";

    public function analyze(string $basePath, string $preset = 'core'): WebRoutesAnalysis
    {
        $needsAuthRoutes = $preset === 'admin';
        $hasInertiaDependency = $this->hasInertiaDependency($basePath);
        $inertiaHint = 'composer require inertiajs/inertia-laravel';
        $pagesFileExists = File::exists($basePath.'/'.self::PAGES_ROUTES);
        $pagesFileStatus = $pagesFileExists ? WebRoutesAnalysis::STATUS_EXISTS : WebRoutesAnalysis::STATUS_MISSING;
        $authFileExists = File::exists($basePath.'/'.self::AUTH_ROUTES);
        $authFileStatus = $authFileExists ? WebRoutesAnalysis::STATUS_EXISTS : WebRoutesAnalysis::STATUS_MISSING;

        $webPath = $basePath.'/'.self::WEB_ROUTES;

        if (! File::exists($webPath)) {
            return new WebRoutesAnalysis(
                pagesFileStatus: $pagesFileStatus,
                authFileStatus: $authFileStatus,
                webRoutesStatus: WebRoutesAnalysis::STATUS_MISSING,
                hasPagesInclude: false,
                hasAuthInclude: false,
                hasInertiaDependency: $hasInertiaDependency,
                action: WebRoutesAnalysis::ACTION_BLOCKED,
                reason: 'routes/web.php is missing.',
                shouldCreatePagesFile: ! $pagesFileExists && $hasInertiaDependency,
                shouldCreateAuthFile: $needsAuthRoutes && ! $authFileExists && $hasInertiaDependency,
                inertiaInstallHint: $hasInertiaDependency ? null : $inertiaHint,
            );
        }

        $webContents = (string) file_get_contents($webPath);
        $hasPagesInclude = $this->hasInclude($webContents, self::PAGES_ROUTES);
        $hasAuthInclude = $this->hasInclude($webContents, self::AUTH_ROUTES);
        $isStandardWebRoutes = $this->isStandardWebRoutes($webContents);
        $webRoutesStatus = $isStandardWebRoutes
            ? WebRoutesAnalysis::STATUS_EXISTS
            : WebRoutesAnalysis::STATUS_NON_STANDARD;

        if ($hasPagesInclude && $pagesFileExists && (! $needsAuthRoutes || ($hasAuthInclude && $authFileExists))) {
            return new WebRoutesAnalysis(
                pagesFileStatus: $pagesFileStatus,
                authFileStatus: $authFileStatus,
                webRoutesStatus: $webRoutesStatus,
                hasPagesInclude: true,
                hasAuthInclude: $hasAuthInclude,
                hasInertiaDependency: $hasInertiaDependency,
                action: WebRoutesAnalysis::ACTION_OK,
                reason: $needsAuthRoutes
                    ? 'owl-admin-pages.php and owl-admin-auth.php are included from routes/web.php.'
                    : 'owl-admin-pages.php is included from routes/web.php.',
            );
        }

        if (! $hasInertiaDependency) {
            return new WebRoutesAnalysis(
                pagesFileStatus: $pagesFileStatus,
                authFileStatus: $authFileStatus,
                webRoutesStatus: $webRoutesStatus,
                hasPagesInclude: $hasPagesInclude,
                hasAuthInclude: $hasAuthInclude,
                hasInertiaDependency: false,
                action: WebRoutesAnalysis::ACTION_BLOCKED,
                reason: 'inertiajs/inertia-laravel is required for core admin page routes.',
                shouldCreatePagesFile: false,
                shouldCreateAuthFile: false,
                shouldMergeWebInclude: false,
                inertiaInstallHint: $inertiaHint,
            );
        }

        if (! $isStandardWebRoutes) {
            return new WebRoutesAnalysis(
                pagesFileStatus: $pagesFileStatus,
                authFileStatus: $authFileStatus,
                webRoutesStatus: $webRoutesStatus,
                hasPagesInclude: $hasPagesInclude,
                hasAuthInclude: $hasAuthInclude,
                hasInertiaDependency: true,
                action: WebRoutesAnalysis::ACTION_MANUAL,
                reason: 'Non-standard routes/web.php. Add the include manually.',
                shouldCreatePagesFile: ! $pagesFileExists,
                shouldCreateAuthFile: $needsAuthRoutes && ! $authFileExists,
                manualSnippetPath: $this->manualSnippetPath(),
            );
        }

        $shouldCreatePages = ! $pagesFileExists;
        $shouldCreateAuth = $needsAuthRoutes && ! $authFileExists;
        $shouldMergePages = ! $hasPagesInclude;
        $shouldMergeAuth = $needsAuthRoutes && ! $hasAuthInclude;
        $shouldMergeInclude = $shouldMergePages || $shouldMergeAuth;

        if (! $shouldCreatePages && ! $shouldCreateAuth && ! $shouldMergeInclude) {
            return new WebRoutesAnalysis(
                pagesFileStatus: $pagesFileStatus,
                authFileStatus: $authFileStatus,
                webRoutesStatus: $webRoutesStatus,
                hasPagesInclude: true,
                hasAuthInclude: $hasAuthInclude,
                hasInertiaDependency: true,
                action: WebRoutesAnalysis::ACTION_OK,
                reason: 'Core admin routes file and web.php include are configured.',
            );
        }

        return new WebRoutesAnalysis(
            pagesFileStatus: $pagesFileStatus,
            authFileStatus: $authFileStatus,
            webRoutesStatus: $webRoutesStatus,
            hasPagesInclude: $hasPagesInclude,
            hasAuthInclude: $hasAuthInclude,
            hasInertiaDependency: true,
            action: $shouldMergeInclude
                ? WebRoutesAnalysis::ACTION_AUTO_MERGE
                : WebRoutesAnalysis::ACTION_CREATE,
            reason: $this->buildReason($shouldCreatePages, $shouldCreateAuth, $shouldMergePages, $shouldMergeAuth),
            shouldCreatePagesFile: $shouldCreatePages,
            shouldCreateAuthFile: $shouldCreateAuth,
            shouldMergeWebInclude: $shouldMergeInclude,
        );
    }

    public function hasInclude(string $basePathOrContents, string $routeFile = self::PAGES_ROUTES): bool
    {
        $contents = $this->resolveWebContents($basePathOrContents);
        $needle = basename($routeFile);

        return str_contains($contents, $needle);
    }

    public function canAutoCreatePages(string $basePath, string $preset = 'core'): bool
    {
        return $this->analyze($basePath, $preset)->canAutoCreatePages();
    }

    public function canAutoMergeInclude(string $basePath, string $preset = 'core'): bool
    {
        return $this->analyze($basePath, $preset)->canAutoMergeInclude();
    }

    public function dryRun(string $basePath, string $preset = 'core'): WebRoutesAnalysis
    {
        return $this->analyze($basePath, $preset);
    }

    public function apply(
        string $basePath,
        string $preset = 'core',
        ?WebRoutesAnalysis $analysis = null,
        bool $dryRun = false
    ): bool
    {
        $analysis ??= $this->analyze($basePath, $preset);

        if (! $analysis->hasChanges()) {
            return true;
        }

        if ($dryRun) {
            return true;
        }

        if ($analysis->shouldCreatePagesFile) {
            $stub = $this->stubContents($preset === 'admin' ? self::ADMIN_PAGES_STUB : self::PAGES_STUB);

            if ($stub === null) {
                return false;
            }

            File::ensureDirectoryExists($basePath.'/routes');
            File::put($basePath.'/'.self::PAGES_ROUTES, $stub);
        }

        if ($analysis->shouldCreateAuthFile) {
            $stub = $this->stubContents(self::AUTH_STUB);

            if ($stub === null) {
                return false;
            }

            File::ensureDirectoryExists($basePath.'/routes');
            File::put($basePath.'/'.self::AUTH_ROUTES, $stub);
        }

        if ($analysis->shouldMergeWebInclude) {
            $path = $basePath.'/'.self::WEB_ROUTES;
            $contents = (string) file_get_contents($path);
            $includeLines = [self::INCLUDE_PAGES_LINE];

            if ($preset === 'admin') {
                $includeLines[] = self::INCLUDE_AUTH_LINE;
            }

            $updated = $this->injectIncludes($contents, $includeLines);

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
    public function plan(string $basePath, string $preset = 'core'): array
    {
        $analysis = $this->analyze($basePath, $preset);
        $steps = [];

        if ($analysis->shouldCreatePagesFile) {
            $steps[] = [
                'file' => self::PAGES_ROUTES,
                'action' => 'create',
                'detail' => 'Create core admin page routes from package stub.',
                'analysis' => $analysis,
            ];
        }

        if ($analysis->shouldCreateAuthFile) {
            $steps[] = [
                'file' => self::AUTH_ROUTES,
                'action' => 'create',
                'detail' => 'Create auth routes from package stub.',
                'analysis' => $analysis,
            ];
        }

        if ($analysis->shouldMergeWebInclude) {
            $steps[] = [
                'file' => self::WEB_ROUTES,
                'action' => 'merge',
                'detail' => $preset === 'admin'
                    ? "Add require __DIR__.'/owl-admin-pages.php'; and require __DIR__.'/owl-admin-auth.php'; to routes/web.php."
                    : "Add require __DIR__.'/owl-admin-pages.php'; to routes/web.php.",
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

    private function buildReason(
        bool $shouldCreatePages,
        bool $shouldCreateAuth,
        bool $shouldMergePages,
        bool $shouldMergeAuth
    ): string
    {
        $parts = [];

        if ($shouldCreatePages) {
            $parts[] = 'create routes/owl-admin-pages.php';
        }

        if ($shouldCreateAuth) {
            $parts[] = 'create routes/owl-admin-auth.php';
        }

        if ($shouldMergePages) {
            $parts[] = 'include owl-admin-pages.php in routes/web.php';
        }

        if ($shouldMergeAuth) {
            $parts[] = 'include owl-admin-auth.php in routes/web.php';
        }

        return 'Core admin routes setup: '.implode('; ', $parts).'.';
    }

    /**
     * @param  list<string>  $lines
     */
    private function injectIncludes(string $contents, array $lines): ?string
    {
        $missingLines = array_values(array_filter(
            $lines,
            fn (string $line): bool => ! str_contains($contents, $line)
        ));

        if ($missingLines === []) {
            return $contents;
        }

        $includeBlock = "\n// Owl Admin routes\n".implode("\n", $missingLines)."\n";

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

    private function stubContents(string $relativePath): ?string
    {
        $path = dirname(__DIR__, 2).'/'.$relativePath;

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
