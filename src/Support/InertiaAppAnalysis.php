<?php

namespace OwlSolutions\CustomAdminKit\Support;

class InertiaAppAnalysis
{
    public const STATUS_EXISTS = 'exists';

    public const STATUS_MISSING = 'missing';

    public const STATUS_APP_JS_ONLY = 'app.js only';

    public const ACTION_OK = 'ok';

    public const ACTION_CREATE = 'create';

    public const ACTION_MANUAL = 'manual';

    public function __construct(
        public readonly string $status,
        public readonly string $action,
        public readonly string $reason,
        public readonly bool $hasCreateInertiaApp = false,
        public readonly bool $hasResolvePageComponent = false,
        public readonly bool $hasPagesResolvePattern = false,
        public readonly bool $hasCssOrBootstrapImport = false,
        public readonly ?string $manualSnippetPath = null,
        public readonly ?string $targetFile = 'resources/js/app.jsx',
    ) {}

    public function canAutoCreate(): bool
    {
        return $this->action === self::ACTION_CREATE;
    }

    public function canAutoMerge(): bool
    {
        return false;
    }

    public function requiresManualMerge(): bool
    {
        return $this->action === self::ACTION_MANUAL;
    }

    public function hasChanges(): bool
    {
        return $this->canAutoCreate();
    }

    public function isStandard(): bool
    {
        return $this->status === self::STATUS_EXISTS
            && $this->hasCreateInertiaApp
            && $this->hasResolvePageComponent
            && $this->hasPagesResolvePattern
            && $this->hasCssOrBootstrapImport;
    }
}
