<?php

namespace OwlSolutions\CustomAdminKit\Support;

class InertiaMiddlewareAnalysis
{
    public const STATUS_MISSING = 'missing';

    public const STATUS_EXISTS = 'exists';

    public const ACTION_OK = 'ok';

    public const ACTION_AUTO_MERGE = 'auto-merge';

    public const ACTION_MANUAL = 'manual';

    public const ACTION_BLOCKED = 'blocked';

    public function __construct(
        public readonly string $status,
        public readonly bool $hasOwlAdminShare,
        public readonly string $action,
        public readonly string $reason,
        public readonly bool $hasStandardShareMethod = false,
        public readonly ?string $manualSnippetPath = null,
        public readonly ?string $installHint = null,
        public readonly string $relativePath = 'app/Http/Middleware/HandleInertiaRequests.php',
    ) {}

    public function canAutoMerge(): bool
    {
        return $this->action === self::ACTION_AUTO_MERGE;
    }

    public function requiresManualMerge(): bool
    {
        return $this->action === self::ACTION_MANUAL;
    }

    public function hasChanges(): bool
    {
        return $this->canAutoMerge();
    }

    public function failsStrictCheck(): bool
    {
        return $this->status === self::STATUS_MISSING;
    }
}
