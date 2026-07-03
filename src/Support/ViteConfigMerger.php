<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Support\Facades\File;

class ViteConfigMerger
{
    private const CONFIG_FILE = 'vite.config.js';

    private const MANUAL_SNIPPET = 'docs/merge-snippets/vite.config.js';

    /**
     * @var list<string>
     */
    private const APP_ENTRIES = [
        'resources/js/app.jsx',
        'resources/js/app.js',
    ];

    public function analyze(string $basePath): ViteConfigAnalysis
    {
        $path = $basePath.'/'.self::CONFIG_FILE;

        if (! File::exists($path)) {
            return new ViteConfigAnalysis(
                status: ViteConfigAnalysis::STATUS_MISSING,
                action: ViteConfigAnalysis::ACTION_BLOCKED,
                detail: 'vite.config.js not found.',
            );
        }

        $contents = (string) file_get_contents($path);
        $hasLaravelPlugin = str_contains($contents, 'laravel-vite-plugin');
        $currentInputs = $this->parseLaravelInputs($contents);
        $hasAppEntry = $this->inputsContainAppEntry($currentInputs);
        $targetInputs = $this->targetInputs($basePath);
        $missingInputs = $this->diffMissingInputs($currentInputs, $targetInputs);

        if (! $hasLaravelPlugin || ! $this->isStandardStructure($contents)) {
            return new ViteConfigAnalysis(
                status: ViteConfigAnalysis::STATUS_NON_STANDARD,
                currentInputs: $currentInputs,
                missingInputs: $missingInputs,
                hasLaravelPlugin: $hasLaravelPlugin,
                hasAppEntry: $hasAppEntry,
                action: $this->resolveNonStandardAction($missingInputs, $hasAppEntry),
                manualSnippetPath: $this->manualSnippetPath(),
                detail: $this->nonStandardDetail($hasLaravelPlugin, $currentInputs),
            );
        }

        if ($missingInputs === []) {
            return new ViteConfigAnalysis(
                status: ViteConfigAnalysis::STATUS_STANDARD,
                currentInputs: $currentInputs,
                missingInputs: [],
                hasLaravelPlugin: true,
                hasAppEntry: $hasAppEntry,
                action: ViteConfigAnalysis::ACTION_SKIP,
                detail: 'Laravel Vite plugin includes required Inertia entry inputs.',
            );
        }

        return new ViteConfigAnalysis(
            status: ViteConfigAnalysis::STATUS_STANDARD,
            currentInputs: $currentInputs,
            missingInputs: $missingInputs,
            hasLaravelPlugin: true,
            hasAppEntry: $hasAppEntry,
            action: ViteConfigAnalysis::ACTION_AUTO_MERGE,
            detail: 'Add missing laravel plugin input entries.',
        );
    }

    public function canAutoMerge(string $basePath): bool
    {
        return $this->analyze($basePath)->canAutoMerge();
    }

    /**
     * @return list<string>
     */
    public function getMissingInputs(string $basePath): array
    {
        return $this->analyze($basePath)->missingInputs;
    }

    public function dryRun(string $basePath): ViteConfigAnalysis
    {
        return $this->analyze($basePath);
    }

    public function apply(string $basePath, ?ViteConfigAnalysis $analysis = null, bool $dryRun = false): bool
    {
        $analysis ??= $this->analyze($basePath);

        if (! $analysis->canAutoMerge()) {
            return false;
        }

        if ($dryRun) {
            return true;
        }

        $path = $basePath.'/'.self::CONFIG_FILE;
        $contents = (string) file_get_contents($path);
        $mergedInputs = array_values(array_unique([
            ...$analysis->currentInputs,
            ...$analysis->missingInputs,
        ]));
        $updated = $this->replaceInputDeclaration($contents, $mergedInputs);

        if ($updated === null || $updated === $contents) {
            return false;
        }

        File::put($path, $updated);

        return true;
    }

    /**
     * @return list<array{file: string, action: string, detail: string, analysis?: ViteConfigAnalysis}>
     */
    public function plan(string $basePath): array
    {
        $analysis = $this->analyze($basePath);

        if ($analysis->status === ViteConfigAnalysis::STATUS_MISSING) {
            return [[
                'file' => self::CONFIG_FILE,
                'action' => 'blocked',
                'detail' => $analysis->detail ?? 'vite.config.js not found.',
                'analysis' => $analysis,
            ]];
        }

        $action = match ($analysis->action) {
            ViteConfigAnalysis::ACTION_AUTO_MERGE => 'merge',
            ViteConfigAnalysis::ACTION_MANUAL => 'blocked',
            ViteConfigAnalysis::ACTION_BLOCKED => 'blocked',
            default => 'skip',
        };

        return [[
            'file' => self::CONFIG_FILE,
            'action' => $action,
            'detail' => $analysis->detail ?? $this->summarizeAnalysis($analysis),
            'analysis' => $analysis,
        ]];
    }

    public function manualSnippetPath(): string
    {
        return dirname(__DIR__, 2).'/'.self::MANUAL_SNIPPET;
    }

    public function manualSnippetRelativePath(): string
    {
        return self::MANUAL_SNIPPET;
    }

    /**
     * @return list<string>
     */
    private function targetInputs(string $basePath): array
    {
        $inputs = ['resources/css/app.css'];

        if (File::exists($basePath.'/resources/js/app.jsx')) {
            $inputs[] = 'resources/js/app.jsx';
        } elseif (File::exists($basePath.'/resources/js/app.js')) {
            $inputs[] = 'resources/js/app.js';
        } else {
            $inputs[] = 'resources/js/app.jsx';
        }

        return $inputs;
    }

    /**
     * @param  list<string>  $currentInputs
     * @param  list<string>  $targetInputs
     * @return list<string>
     */
    private function diffMissingInputs(array $currentInputs, array $targetInputs): array
    {
        $current = array_fill_keys($currentInputs, true);
        $missing = [];

        foreach ($targetInputs as $input) {
            if (! isset($current[$input])) {
                $missing[] = $input;
            }
        }

        return $missing;
    }

    /**
     * @param  list<string>  $inputs
     */
    private function inputsContainAppEntry(array $inputs): bool
    {
        foreach ($inputs as $input) {
            if (in_array($input, self::APP_ENTRIES, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function parseLaravelInputs(string $contents): array
    {
        if (! preg_match('/laravel\s*\(\s*\{([^}]*)\}/s', $contents, $matches)) {
            return [];
        }

        $block = $matches[1];

        if (preg_match('/input\s*:\s*\[(.*?)\]/s', $block, $arrayMatch)) {
            preg_match_all("/['\"]([^'\"]+)['\"]/", $arrayMatch[1], $items);

            return array_values(array_filter($items[1] ?? [], fn (string $item) => $item !== ''));
        }

        if (preg_match("/input\s*:\s*['\"]([^'\"]+)['\"]/", $block, $stringMatch)) {
            return [$stringMatch[1]];
        }

        return [];
    }

    private function isStandardStructure(string $contents): bool
    {
        if (! str_contains($contents, 'defineConfig')) {
            return false;
        }

        if (! str_contains($contents, 'laravel-vite-plugin')) {
            return false;
        }

        if (preg_match_all('/laravel\s*\(/', $contents) !== 1) {
            return false;
        }

        if (! preg_match('/laravel\s*\(\s*\{([^}]*)\}/s', $contents, $matches)) {
            return false;
        }

        $block = $matches[1];

        if (! str_contains($block, 'input')) {
            return false;
        }

        if (preg_match('/input\s*:\s*\[(.*?)\]/s', $block, $arrayMatch)) {
            $arrayBody = $arrayMatch[1];

            if (str_contains($arrayBody, '...') || str_contains($arrayBody, '`')) {
                return false;
            }

            if (preg_match('/[\w$]+\(/', $arrayBody)) {
                return false;
            }

            return (bool) preg_match_all("/['\"]([^'\"]+)['\"]/", $arrayBody);
        }

        return (bool) preg_match("/input\s*:\s*['\"]([^'\"]+)['\"]/", $block);
    }

    /**
     * @param  list<string>  $missingInputs
     */
    private function resolveNonStandardAction(array $missingInputs, bool $hasAppEntry): string
    {
        if ($missingInputs === [] && $hasAppEntry) {
            return ViteConfigAnalysis::ACTION_SKIP;
        }

        return ViteConfigAnalysis::ACTION_MANUAL;
    }

    /**
     * @param  list<string>  $currentInputs
     */
    private function nonStandardDetail(bool $hasLaravelPlugin, array $currentInputs): string
    {
        if (! $hasLaravelPlugin) {
            return 'Non-standard vite.config.js (laravel-vite-plugin missing). Manual merge required.';
        }

        if ($currentInputs === []) {
            return 'Non-standard vite.config.js (laravel input could not be parsed). Manual merge required.';
        }

        return 'Non-standard vite.config.js structure. Manual merge required.';
    }

    private function summarizeAnalysis(ViteConfigAnalysis $analysis): string
    {
        if ($analysis->action === ViteConfigAnalysis::ACTION_MANUAL) {
            return 'Manual merge required. See '.self::MANUAL_SNIPPET;
        }

        if ($analysis->missingInputs !== []) {
            return 'Add missing inputs: '.implode(', ', $analysis->missingInputs);
        }

        return 'No vite.config.js changes required.';
    }

    /**
     * @param  list<string>  $mergedInputs
     */
    private function replaceInputDeclaration(string $contents, array $mergedInputs): ?string
    {
        if ($mergedInputs === []) {
            return null;
        }

        if (count($mergedInputs) === 1) {
            $replacement = "input: '{$mergedInputs[0]}'";
        } else {
            $items = implode(', ', array_map(fn (string $input) => "'{$input}'", $mergedInputs));
            $replacement = "input: [{$items}]";
        }

        $updated = preg_replace(
            '/input\s*:\s*(?:\[[^\]]*\]|\'[^\']*\'|"[^"]*")/',
            $replacement,
            $contents,
            1,
        );

        return is_string($updated) ? $updated : null;
    }
}
