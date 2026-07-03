<?php

namespace OwlSolutions\CustomAdminKit\Support;

class InstallReport
{
    /** @var list<string> */
    private array $steps = [];

    /** @var list<string> */
    private array $warnings = [];

    /** @var list<string> */
    private array $errors = [];

    public function addStep(string $message): void
    {
        $this->steps[] = $message;
    }

    public function addWarning(string $message): void
    {
        $this->warnings[] = $message;
    }

    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /**
     * @return list<string>
     */
    public function steps(): array
    {
        return $this->steps;
    }

    /**
     * @return list<string>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /**
     * @return list<string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
