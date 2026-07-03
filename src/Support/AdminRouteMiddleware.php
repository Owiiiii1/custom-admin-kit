<?php

namespace OwlSolutions\CustomAdminKit\Support;

class AdminRouteMiddleware
{
    /**
     * Admin route middleware stack for host routes (web + auth, optional verified).
     *
     * @return list<string>
     */
    public static function stack(): array
    {
        $middleware = ['web', 'auth'];

        if (config('owl-admin-kit.email_verification.enabled')) {
            $middleware[] = 'verified';
        }

        return $middleware;
    }
}
