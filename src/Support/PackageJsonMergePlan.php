<?php

namespace OwlSolutions\CustomAdminKit\Support;

class PackageJsonMergePlan
{
    /**
     * @param  array<string, string>  $missingDependencies
     * @param  array<string, string>  $missingDevDependencies
     */
    public function __construct(
        public readonly array $missingDependencies = [],
        public readonly array $missingDevDependencies = [],
    ) {}

    public function hasChanges(): bool
    {
        return $this->missingDependencies !== [] || $this->missingDevDependencies !== [];
    }

    /**
     * @return list<string>
     */
    public function allMissingPackageNames(): array
    {
        return array_values(array_unique([
            ...array_keys($this->missingDependencies),
            ...array_keys($this->missingDevDependencies),
        ]));
    }
}
