<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Support\Facades\File;

class InertiaAppMerger
{
    private const APP_JSX = 'resources/js/app.jsx';

    private const APP_JS = 'resources/js/app.js';

    private const SNIPPET = 'docs/merge-snippets/app.jsx';

    public function analyze(string $basePath): InertiaAppAnalysis
    {
        $hasAppJsx = File::exists($basePath.'/'.self::APP_JSX);
        $hasAppJs = File::exists($basePath.'/'.self::APP_JS);

        if ($hasAppJsx) {
            return $this->analyzeExistingAppJsx($basePath);
        }

        if ($hasAppJs) {
            return new InertiaAppAnalysis(
                status: InertiaAppAnalysis::STATUS_APP_JS_ONLY,
                action: InertiaAppAnalysis::ACTION_CREATE,
                reason: 'resources/js/app.js exists; create resources/js/app.jsx and ensure vite.config.js uses app.jsx as the Inertia entry.',
                manualSnippetPath: $this->manualSnippetPath(),
            );
        }

        return new InertiaAppAnalysis(
            status: InertiaAppAnalysis::STATUS_MISSING,
            action: InertiaAppAnalysis::ACTION_CREATE,
            reason: 'resources/js/app.jsx is missing; create the standard Inertia React entrypoint from the package snippet.',
            manualSnippetPath: $this->manualSnippetPath(),
        );
    }

    public function canAutoCreate(string $basePath): bool
    {
        return $this->analyze($basePath)->canAutoCreate();
    }

    public function canAutoMerge(string $basePath): bool
    {
        return $this->analyze($basePath)->canAutoMerge();
    }

    public function dryRun(string $basePath): InertiaAppAnalysis
    {
        return $this->analyze($basePath);
    }

    public function apply(string $basePath, ?InertiaAppAnalysis $analysis = null, bool $dryRun = false): bool
    {
        $analysis ??= $this->analyze($basePath);

        if (! $analysis->canAutoCreate()) {
            return false;
        }

        if ($dryRun) {
            return true;
        }

        if (File::exists($basePath.'/'.self::APP_JSX)) {
            return false;
        }

        $snippet = $this->buildSnippet($basePath);
        File::ensureDirectoryExists($basePath.'/resources/js');
        File::put($basePath.'/'.self::APP_JSX, $snippet);

        return true;
    }

    /**
     * @return list<array{file: string, action: string, detail: string, analysis?: InertiaAppAnalysis}>
     */
    public function plan(string $basePath): array
    {
        $analysis = $this->analyze($basePath);

        $stepAction = match ($analysis->action) {
            InertiaAppAnalysis::ACTION_CREATE => 'create',
            InertiaAppAnalysis::ACTION_MANUAL => 'blocked',
            default => 'skip',
        };

        return [[
            'file' => self::APP_JSX,
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

    private function analyzeExistingAppJsx(string $basePath): InertiaAppAnalysis
    {
        $contents = (string) file_get_contents($basePath.'/'.self::APP_JSX);

        $hasCreateInertiaApp = str_contains($contents, 'createInertiaApp');
        $hasResolvePageComponent = str_contains($contents, 'resolvePageComponent');
        $hasPagesResolvePattern = $this->hasPagesResolvePattern($contents);
        $hasCssOrBootstrapImport = $this->hasCssOrBootstrapImport($contents);

        $isStandard = $hasCreateInertiaApp
            && $hasResolvePageComponent
            && $hasPagesResolvePattern
            && $hasCssOrBootstrapImport;

        if ($isStandard) {
            return new InertiaAppAnalysis(
                status: InertiaAppAnalysis::STATUS_EXISTS,
                action: InertiaAppAnalysis::ACTION_OK,
                reason: 'Standard Inertia React app.jsx entrypoint detected.',
                hasCreateInertiaApp: true,
                hasResolvePageComponent: true,
                hasPagesResolvePattern: true,
                hasCssOrBootstrapImport: true,
            );
        }

        return new InertiaAppAnalysis(
            status: InertiaAppAnalysis::STATUS_EXISTS,
            action: InertiaAppAnalysis::ACTION_MANUAL,
            reason: $this->manualReason(
                $hasCreateInertiaApp,
                $hasResolvePageComponent,
                $hasPagesResolvePattern,
                $hasCssOrBootstrapImport,
            ),
            hasCreateInertiaApp: $hasCreateInertiaApp,
            hasResolvePageComponent: $hasResolvePageComponent,
            hasPagesResolvePattern: $hasPagesResolvePattern,
            hasCssOrBootstrapImport: $hasCssOrBootstrapImport,
            manualSnippetPath: $this->manualSnippetPath(),
        );
    }

    private function manualReason(
        bool $hasCreateInertiaApp,
        bool $hasResolvePageComponent,
        bool $hasPagesResolvePattern,
        bool $hasCssOrBootstrapImport,
    ): string {
        $issues = [];

        if (! $hasCreateInertiaApp) {
            $issues[] = 'createInertiaApp missing';
        }

        if (! $hasResolvePageComponent) {
            $issues[] = 'resolvePageComponent missing';
        }

        if (! $hasPagesResolvePattern) {
            $issues[] = 'Pages resolve pattern missing';
        }

        if (! $hasCssOrBootstrapImport) {
            $issues[] = "import '../css/app.css' or './bootstrap' missing";
        }

        return 'Non-standard app.jsx ('.implode('; ', $issues).'). Manual merge required.';
    }

    private function hasPagesResolvePattern(string $contents): bool
    {
        return str_contains($contents, './Pages/')
            || str_contains($contents, "import.meta.glob('./Pages/");
    }

    private function hasCssOrBootstrapImport(string $contents): bool
    {
        return (bool) preg_match("/import\s+['\"]\.\.\/css\/app\.css['\"]/", $contents)
            || (bool) preg_match("/import\s+['\"]\.\/bootstrap(?:\.js)?['\"]/", $contents);
    }

    private function buildSnippet(string $basePath): string
    {
        $snippetPath = $this->manualSnippetPath();
        $snippet = is_file($snippetPath)
            ? (string) file_get_contents($snippetPath)
            : $this->defaultSnippet();

        if (! File::exists($basePath.'/resources/js/bootstrap.js')) {
            $snippet = preg_replace("/^import\s+['\"]\.\/bootstrap(?:\.js)?['\"];\R/m", '', $snippet) ?? $snippet;
        }

        if (! str_ends_with($snippet, "\n")) {
            $snippet .= "\n";
        }

        return $snippet;
    }

    private function defaultSnippet(): string
    {
        return <<<'JSX'
import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
JSX;
    }
}
