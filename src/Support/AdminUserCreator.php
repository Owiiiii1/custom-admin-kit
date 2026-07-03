<?php

namespace OwlSolutions\CustomAdminKit\Support;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AdminUserCreator
{
    public function create(AdminUserCredentials $credentials): User
    {
        if (! class_exists(User::class)) {
            throw new \RuntimeException('App\\Models\\User not found.');
        }

        $attributes = [
            'name' => $credentials->name,
            'password' => Hash::make($credentials->password),
        ];

        if (Schema::hasColumn('users', 'locale')) {
            $attributes['locale'] = config('app.locale', 'en');
        }

        foreach (['access_full', 'access_billing', 'access_staff'] as $flag) {
            if (Schema::hasColumn('users', $flag)) {
                $attributes[$flag] = true;
            }
        }

        /** @var User $user */
        $user = User::query()->updateOrCreate(['email' => $credentials->email], $attributes);

        return $user;
    }
}
