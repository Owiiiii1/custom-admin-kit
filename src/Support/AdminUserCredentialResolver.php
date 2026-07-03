<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AdminUserCredentialResolver
{
    private const FORBIDDEN_EMAIL = 'admin@admin.com';

    private const FORBIDDEN_PASSWORD = 'admin';

    public function __construct(
        private readonly ?Command $command = null,
    ) {}

    public function resolve(bool $interactive, ?string $emailOverride = null, ?string $passwordOverride = null): AdminUserCredentialResolution
    {
        if (! config('owl-admin-kit.admin_user.enabled', true)) {
            return AdminUserCredentialResolution::fail('Admin user creation is disabled (admin_user.enabled = false).');
        }

        $name = (string) config('owl-admin-kit.admin_user.name', 'Admin');
        $email = $emailOverride ?? $this->resolveEmail($interactive, $emailOverride);
        $passwordResult = $this->resolvePassword($interactive, $passwordOverride);

        if ($email === null) {
            return AdminUserCredentialResolution::fail(
                'Admin email is required. Set OWL_ADMIN_EMAIL in .env or pass --email / answer the prompt.'
            );
        }

        if ($passwordResult === null) {
            return AdminUserCredentialResolution::fail(
                'Admin password is required. Set OWL_ADMIN_PASSWORD in .env, use interactive mode, or enable OWL_ADMIN_ALLOW_DEFAULT_PASSWORD=true with APP_ENV=local to generate one.'
            );
        }

        [$password, $generated] = $passwordResult;

        $validationError = $this->validateForbiddenCredentials($email, $password);

        if ($validationError !== null) {
            return AdminUserCredentialResolution::fail($validationError);
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return AdminUserCredentialResolution::fail('Invalid admin email address.');
        }

        if (strlen($password) < 6) {
            return AdminUserCredentialResolution::fail('Admin password must be at least 6 characters.');
        }

        return AdminUserCredentialResolution::ok(
            new AdminUserCredentials($email, $password, $name, $generated),
        );
    }

    public function canResolveWithoutInteraction(): bool
    {
        $email = $this->emailFromConfig();

        if ($email === null || $email === '') {
            return false;
        }

        $password = $this->passwordFromEnv();

        if ($password !== null && $password !== '') {
            return $this->validateForbiddenCredentials($email, $password) === null;
        }

        return $this->defaultPasswordGenerationAllowed();
    }

    private function resolveEmail(bool $interactive, ?string $emailOverride = null): ?string
    {
        if ($emailOverride !== null && $emailOverride !== '') {
            return $emailOverride;
        }

        $email = $this->emailFromConfig();

        if ($email !== null && $email !== '') {
            return $email;
        }

        if ($interactive && $this->command !== null) {
            $asked = (string) $this->command->ask('Admin email (OWL_ADMIN_EMAIL)');

            return $asked !== '' ? $asked : null;
        }

        return null;
    }

    /**
     * @return array{0: string, 1: bool}|null
     */
    private function resolvePassword(bool $interactive, ?string $passwordOverride = null): ?array
    {
        if ($passwordOverride !== null && $passwordOverride !== '') {
            return [$passwordOverride, false];
        }

        $password = $this->passwordFromEnv();

        if ($password !== null && $password !== '') {
            return [$password, false];
        }

        if ($this->defaultPasswordGenerationAllowed()) {
            return [Str::password(16), true];
        }

        if ($interactive && $this->command !== null) {
            $asked = (string) $this->command->secret('Admin password (min 6 chars, OWL_ADMIN_PASSWORD)');

            return $asked !== '' ? [$asked, false] : null;
        }

        return null;
    }

    private function emailFromConfig(): ?string
    {
        $email = config('owl-admin-kit.admin_user.email');

        if (is_string($email) && $email !== '') {
            return $email;
        }

        $fromEnv = env('OWL_ADMIN_EMAIL');

        return is_string($fromEnv) && $fromEnv !== '' ? $fromEnv : null;
    }

    private function passwordFromEnv(): ?string
    {
        $key = (string) config('owl-admin-kit.admin_user.password_env', 'OWL_ADMIN_PASSWORD');
        $password = env($key);

        return is_string($password) && $password !== '' ? $password : null;
    }

    private function defaultPasswordGenerationAllowed(): bool
    {
        if (! config('owl-admin-kit.admin_user.default_password_allowed', false)) {
            return false;
        }

        return app()->environment('local');
    }

    private function validateForbiddenCredentials(string $email, string $password): ?string
    {
        if (strtolower($email) === self::FORBIDDEN_EMAIL) {
            return 'Email admin@admin.com is not allowed. Set OWL_ADMIN_EMAIL to a unique address.';
        }

        if ($password === self::FORBIDDEN_PASSWORD) {
            return 'Password "admin" is not allowed. Set OWL_ADMIN_PASSWORD to a strong value.';
        }

        if (strtolower($email) === self::FORBIDDEN_EMAIL && $password === self::FORBIDDEN_PASSWORD) {
            return 'Hardcoded credentials admin@admin.com/admin are not allowed.';
        }

        return null;
    }
}
