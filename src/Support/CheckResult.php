<?php

namespace OwlSolutions\CustomAdminKit\Support;

class CheckResult
{
    public const SEVERITY_PASS = 'pass';

    public const SEVERITY_WARN = 'warn';

    public const SEVERITY_FAIL = 'fail';

    public function __construct(
        public readonly string $name,
        public readonly bool $passed,
        public readonly string $message,
        public readonly ?string $hint = null,
        public readonly string $severity = self::SEVERITY_PASS,
    ) {}

    public static function pass(string $name, string $message): self
    {
        return new self($name, true, $message, null, self::SEVERITY_PASS);
    }

    public static function warn(string $name, string $message, ?string $hint = null): self
    {
        return new self($name, true, $message, $hint, self::SEVERITY_WARN);
    }

    public static function fail(string $name, string $message, ?string $hint = null): self
    {
        return new self($name, false, $message, $hint, self::SEVERITY_FAIL);
    }

    public function isHardFailure(): bool
    {
        return $this->severity === self::SEVERITY_FAIL && ! $this->passed;
    }

    public function isWarning(): bool
    {
        return $this->severity === self::SEVERITY_WARN;
    }
}
