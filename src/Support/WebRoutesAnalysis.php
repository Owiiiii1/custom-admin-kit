<?php

namespace OwlSolutions\CustomAdminKit\Support;

class WebRoutesAnalysis
{
    public const STATUS_EXISTS = 'exists';

    public const STATUS_MISSING = 'missing';

    public const STATUS_NON_STANDARD = 'non-standard';

    public const ACTION_OK = 'ok';

    public const ACTION_CREATE = 'create';

    public const ACTION_AUTO_MERGE = 'auto-merge';

    public const ACTION_MANUAL = 'manual';

    public const ACTION_BLOCKED = 'blocked';

    public function __construct(
        public readonly string $pagesFileStatus,
        public readonly string $authFileStatus,
        public readonly string $webRoutesStatus,
        public readonly bool $hasPagesInclude,
        public readonly bool $hasAuthInclude,
        public readonly bool $hasInertiaDependency,
        public readonly string $action,
        public readonly string $reason,
        public readonly bool $shouldCreatePagesFile = false,
        public readonly bool $shouldCreateAuthFile = false,
        public readonly bool $shouldMergeWebInclude = false,
        public readonly ?string $manualSnippetPath = null,
        public readonly ?string $inertiaInstallHint = null,
    ) {}

    public function canAutoCreatePages(): bool
    {
        return $this->shouldCreatePagesFile;
    }

    public function canAutoMergeInclude(): bool
    {
        return $this->shouldMergeWebInclude;
    }

    public function hasChanges(): bool
    {
        return $this->canAutoCreatePages() || $this->shouldCreateAuthFile || $this->canAutoMergeInclude();
    }

    public function requiresManualMerge(): bool
    {
        return $this->action === self::ACTION_MANUAL;
    }

    public function failsWithoutInertia(): bool
    {
        return ! $this->hasInertiaDependency && $this->hasChanges();
    }
}
