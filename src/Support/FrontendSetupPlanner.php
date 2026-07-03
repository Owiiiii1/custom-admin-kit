<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Support\Facades\File;

class FrontendSetupPlanner
{
    public function __construct(
        private readonly PublishMapResolver $publishMap,
        private readonly DependencyChecker $dependencies,
        private readonly FrontendDependencyChecker $frontendDependencies,
        private readonly PackageJsonMerger $packageJsonMerger,
        private readonly ViteConfigMerger $viteConfigMerger,
        private readonly InertiaAppMerger $inertiaAppMerger,
        private readonly InertiaMiddlewareMerger $inertiaMiddlewareMerger,
    ) {}

    public function plan(string $basePath, string $preset): FrontendSetupResult
    {
        $prerequisites = $this->checkPrerequisites($basePath, $preset);
        $blocking = array_filter($prerequisites, fn (CheckResult $result) => $result->isHardFailure());

        if ($blocking !== []) {
            return new FrontendSetupResult(
                ready: false,
                prerequisites: $prerequisites,
                planSteps: [],
            );
        }

        $packageJsonSteps = $this->packageJsonMerger->plan($basePath, $preset);
        $packageJsonMerge = $packageJsonSteps[0]['merge'] ?? null;

        $viteConfigSteps = $this->viteConfigMerger->plan($basePath);
        $viteConfigAnalysis = $viteConfigSteps[0]['analysis'] ?? null;

        $inertiaAppSteps = $this->inertiaAppMerger->plan($basePath);
        $inertiaAppAnalysis = $inertiaAppSteps[0]['analysis'] ?? null;

        $planSteps = array_merge(
            $packageJsonSteps,
            $viteConfigSteps,
            $inertiaAppSteps,
            $this->planAppCss($basePath),
            $this->inertiaMiddlewareMerger->plan($basePath),
        );

        $missingNpm = $packageJsonMerge instanceof PackageJsonMergePlan
            ? $packageJsonMerge->allMissingPackageNames()
            : $this->frontendDependencies->missingPackages($basePath, $preset);
        $npmCommand = $missingNpm !== []
            ? $this->frontendDependencies->buildInstallCommand($missingNpm)
            : null;

        $warnings = [];

        foreach ($planSteps as $step) {
            if ($step['action'] === 'blocked') {
                $warnings[] = $step['file'].': '.$step['detail'];
            }
        }

        return new FrontendSetupResult(
            ready: true,
            prerequisites: $prerequisites,
            planSteps: $planSteps,
            missingNpm: $missingNpm,
            warnings: $warnings,
            npmInstallCommand: $npmCommand,
            packageJsonMerge: $packageJsonMerge instanceof PackageJsonMergePlan ? $packageJsonMerge : null,
            viteConfigAnalysis: $viteConfigAnalysis instanceof ViteConfigAnalysis ? $viteConfigAnalysis : null,
            inertiaAppAnalysis: $inertiaAppAnalysis instanceof InertiaAppAnalysis ? $inertiaAppAnalysis : null,
        );
    }

    /**
     * @return list<CheckResult>
     */
    private function checkPrerequisites(string $basePath, string $preset): array
    {
        $results = [];

        if (! $this->publishMap->isPresetAvailable($preset)) {
            $results[] = CheckResult::fail(
                'preset',
                "Preset [{$preset}] is not available.",
                $this->publishMap->unavailablePresetMessage($preset),
            );

            return $results;
        }

        $results[] = CheckResult::pass('preset', "Preset [{$preset}] is supported for frontend setup (v0.2).");

        $requiredFiles = [
            'package.json' => 'Host package.json is required for npm merge.',
            'vite.config.js' => 'Host vite.config.js is required for Vite merge.',
            'resources/css/app.css' => 'Host resources/css/app.css is required for Tailwind/CSS merge.',
        ];

        foreach ($requiredFiles as $file => $detail) {
            $results[] = File::exists($basePath.'/'.$file)
                ? CheckResult::pass("file:{$file}", "{$file} exists.")
                : CheckResult::fail("file:{$file}", "Missing required file: {$file}", $detail);
        }

        $viteAnalysis = $this->viteConfigMerger->analyze($basePath);

        if ($viteAnalysis->status !== ViteConfigAnalysis::STATUS_MISSING && ! $viteAnalysis->hasAppEntry) {
            $hint = $viteAnalysis->canAutoMerge()
                ? 'Run owl-admin:frontend-setup with --backup to add the Inertia app entry.'
                : 'Merge manually using docs/merge-snippets/vite.config.js';

            $results[] = CheckResult::warn(
                'vite-app-entry',
                'vite.config.js laravel input is missing the Inertia app entry (resources/js/app.jsx or app.js).',
                $hint,
            );
        }

        $inertiaAppAnalysis = $this->inertiaAppMerger->analyze($basePath);

        if ($inertiaAppAnalysis->action === InertiaAppAnalysis::ACTION_OK) {
            $results[] = CheckResult::pass(
                'file:app-entry',
                'resources/js/app.jsx is a standard Inertia React entrypoint.',
            );
        } elseif ($inertiaAppAnalysis->canAutoCreate()) {
            $results[] = CheckResult::warn(
                'file:app-entry',
                'resources/js/app.jsx can be created from the package snippet.',
                'Run owl-admin:frontend-setup with --backup or --force.',
            );
        } elseif ($inertiaAppAnalysis->requiresManualMerge()) {
            $results[] = CheckResult::warn(
                'file:app-entry',
                'Non-standard resources/js/app.jsx detected.',
                'Merge manually using docs/merge-snippets/app.jsx',
            );
        }

        if ($inertiaAppAnalysis->status === InertiaAppAnalysis::STATUS_APP_JS_ONLY) {
            $results[] = CheckResult::warn(
                'file:app-js-only',
                'Host uses resources/js/app.js; create app.jsx and point vite.config.js to app.jsx.',
                'Run owl-admin:frontend-setup with --backup or --force after reviewing vite.config.js inputs.',
            );
        }

        $middleware = 'app/Http/Middleware/HandleInertiaRequests.php';
        $results[] = File::exists($basePath.'/'.$middleware)
            ? CheckResult::pass('file:handle-inertia', "{$middleware} exists.")
            : CheckResult::fail(
                'file:handle-inertia',
                'Missing HandleInertiaRequests middleware.',
                'composer require inertiajs/inertia-laravel and publish middleware.',
            );

        foreach ($this->dependencies->check($basePath, strict: false) as $dependencyResult) {
            $results[] = $dependencyResult;
        }

        if (! $this->frontendDependencies->requiresFrontend($preset)) {
            $results[] = CheckResult::warn('frontend-preset', 'Preset does not declare frontend stubs.');
        } else {
            $results[] = CheckResult::pass('frontend-preset', 'Core preset requires frontend npm packages.');
        }

        return $results;
    }

    /**
     * @return list<array{file: string, action: string, detail: string}>
     */
    private function planAppCss(string $basePath): array
    {
        $relativePath = 'resources/css/app.css';
        $absolutePath = $basePath.'/'.$relativePath;

        if (! File::exists($absolutePath)) {
            return [[
                'file' => $relativePath,
                'action' => 'blocked',
                'detail' => 'resources/css/app.css not found.',
            ]];
        }

        $contents = (string) file_get_contents($absolutePath);

        if (str_contains($contents, 'owl-admin.css')) {
            return [[
                'file' => $relativePath,
                'action' => 'skip',
                'detail' => 'owl-admin.css already referenced.',
            ]];
        }

        return [[
            'file' => $relativePath,
            'action' => 'merge',
            'detail' => 'Import ./owl-admin.css from published kit styles.',
        ]];
    }
}
