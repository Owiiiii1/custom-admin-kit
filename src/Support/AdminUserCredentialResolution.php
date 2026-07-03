<?php

namespace OwlSolutions\CustomAdminKit\Support;

class AdminUserCredentialResolution
{
    public function __construct(
        public readonly bool $success,
        public readonly ?AdminUserCredentials $credentials = null,
        public readonly ?string $error = null,
    ) {}

    public static function ok(AdminUserCredentials $credentials): self
    {
        return new self(true, $credentials);
    }

    public static function fail(string $error): self
    {
        return new self(false, null, $error);
    }
}
