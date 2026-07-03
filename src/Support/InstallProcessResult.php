<?php

namespace OwlSolutions\CustomAdminKit\Support;

class InstallProcessResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly int $exitCode,
        public readonly string $output,
    ) {}
}
