<?php

namespace OwlSolutions\CustomAdminKit\Support;

class ViteConfigAnalysis
{
    public const STATUS_MISSING = 'missing';

    public const STATUS_STANDARD = 'standard';

    public const STATUS_NON_STANDARD = 'non-standard';

    public const ACTION_SKIP = 'skip';

    public const ACTION_AUTO_MERGE = 'auto-merge';

    public const ACTION_MANUAL = 'manual';

    public const ACTION_BLOCKED = 'blocked';

    /**
     * @param  list<string>  $currentInputs
     * @param  list<string>  $missingInputs
     */
    public function __construct(
        public readonly string $status,
        public readonly array $currentInputs = [],
        public readonly array $missingInputs = [],
        public readonly bool $hasLaravelPlugin = false,
        public readonly bool $hasAppEntry = false,
        public readonly string $action = self::ACTION_SKIP,
        public readonly ?string $manualSnippetPath = null,
        public readonly ?string $detail = null,
    ) {}

    public function canAutoMerge(): bool
    {
        return $this->status === self::STATUS_STANDARD
            && $this->missingInputs !== [];
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
        return ! $this->hasAppEntry && ! $this->canAutoMerge();
    }
}
