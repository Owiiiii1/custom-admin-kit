<?php

namespace OwlSolutions\CustomAdminKit\Support;

class AdminUserCredentials
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly string $name,
        public readonly bool $passwordWasGenerated = false,
    ) {}
}
