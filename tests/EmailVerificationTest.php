<?php

namespace OwlSolutions\CustomAdminKit\Tests;

use OwlSolutions\CustomAdminKit\Support\AdminRouteMiddleware;
use OwlSolutions\CustomAdminKit\Support\EmailVerificationChecker;

class EmailVerificationTest extends PackageTestCase
{
    public function test_middleware_stack_excludes_verified_by_default(): void
    {
        config(['owl-admin-kit.email_verification.enabled' => false]);

        $this->assertSame(['web', 'auth'], AdminRouteMiddleware::stack());
    }

    public function test_middleware_stack_includes_verified_when_enabled(): void
    {
        config(['owl-admin-kit.email_verification.enabled' => true]);

        $this->assertSame(['web', 'auth', 'verified'], AdminRouteMiddleware::stack());
    }

    public function test_checker_returns_empty_when_disabled(): void
    {
        config(['owl-admin-kit.email_verification.enabled' => false]);

        $checker = new EmailVerificationChecker;

        $this->assertSame([], $checker->check());
    }

    public function test_checker_fails_when_enabled_without_must_verify_email(): void
    {
        config(['owl-admin-kit.email_verification.enabled' => true]);

        if (! class_exists(\App\Models\User::class)) {
            $this->markTestSkipped('Workbench User model not available.');
        }

        $checker = new EmailVerificationChecker;
        $results = $checker->check();

        $this->assertCount(1, $results);

        if (in_array(
            \Illuminate\Contracts\Auth\MustVerifyEmail::class,
            class_implements(\App\Models\User::class) ?: [],
            true,
        )) {
            $this->assertTrue($results[0]->passed);
        } else {
            $this->assertFalse($results[0]->passed);
            $this->assertStringContainsString('MustVerifyEmail', $results[0]->message);
        }
    }
}
