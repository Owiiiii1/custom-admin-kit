<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Support\Facades\File;

class FileConflictChecker
{
    public function __construct(
        private readonly PublishMapResolver $publishMap,
    ) {}

    /**
     * @param  list<string>  $relativePaths  Paths relative to Laravel base path
     * @return list<CheckResult>
     */
    public function check(string $basePath, array $relativePaths): array
    {
        $results = [];

        foreach ($relativePaths as $relativePath) {
            $results[] = $this->checkPath($basePath, $relativePath, 'medium', false);
        }

        return $results;
    }

    /**
     * Build conflict results from publish-map copy entries for a preset.
     *
     * @return list<CheckResult>
     */
    public function checkPublishPlan(string $basePath, string $preset, bool $force): array
    {
        $results = [];

        foreach ($this->publishMap->copyEntriesForPreset($preset) as $entry) {
            $target = (string) $entry['target'];
            $conflict = (string) ($entry['conflict'] ?? 'low');
            $results[] = $this->checkPath($basePath, $target, $conflict, $force, $entry);
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>|null  $entry
     */
    private function checkPath(
        string $basePath,
        string $relativePath,
        string $conflictLevel,
        bool $force,
        ?array $entry = null,
    ): CheckResult {
        $fullPath = $basePath.'/'.ltrim($relativePath, '/');
        $label = $entry['source'] ?? $relativePath;

        if (! File::exists($fullPath)) {
            return CheckResult::pass(
                "conflict:{$relativePath}",
                "No conflict: {$label}"
            );
        }

        if ($force) {
            return CheckResult::warn(
                "conflict:{$relativePath}",
                "Will overwrite: {$label}",
                'Running with --force.'
            );
        }

        $hint = 'Use --force to overwrite, --backup to keep a copy, or resolve manually.';

        return match ($conflictLevel) {
            'critical', 'high' => CheckResult::fail(
                "conflict:{$relativePath}",
                "Blocked conflict ({$conflictLevel}): {$label} → {$relativePath}",
                $hint
            ),
            default => CheckResult::warn(
                "conflict:{$relativePath}",
                "Existing file will be skipped ({$conflictLevel}): {$label} → {$relativePath}",
                $hint
            ),
        };
    }
}
