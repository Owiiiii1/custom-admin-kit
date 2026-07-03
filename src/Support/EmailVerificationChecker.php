<?php

namespace OwlSolutions\CustomAdminKit\Support;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class EmailVerificationChecker
{
    /**
     * @return list<CheckResult>
     */
    public function check(): array
    {
        if (! config('owl-admin-kit.email_verification.enabled')) {
            return [];
        }

        if (! class_exists(User::class)) {
            return [
                CheckResult::fail(
                    'email-verification',
                    'Email verification is enabled but App\\Models\\User was not found.',
                    'Publish or create the host User model before enabling OWL_ADMIN_EMAIL_VERIFICATION.',
                ),
            ];
        }

        $implements = in_array(
            MustVerifyEmail::class,
            class_implements(User::class) ?: [],
            true,
        );

        if ($implements) {
            return [
                CheckResult::pass(
                    'email-verification',
                    'User model implements MustVerifyEmail (required when verification is enabled).',
                ),
            ];
        }

        return [
            CheckResult::fail(
                'email-verification',
                'OWL_ADMIN_EMAIL_VERIFICATION is enabled but App\\Models\\User does not implement MustVerifyEmail.',
                'Add MustVerifyEmail to User, or set OWL_ADMIN_EMAIL_VERIFICATION=false in .env.',
            ),
        ];
    }
}
