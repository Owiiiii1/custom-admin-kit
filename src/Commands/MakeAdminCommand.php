<?php

namespace OwlSolutions\CustomAdminKit\Commands;

use OwlSolutions\CustomAdminKit\Support\AdminUserCreator;
use OwlSolutions\CustomAdminKit\Support\AdminUserCredentialResolver;

class MakeAdminCommand extends BaseKitCommand
{
    protected $signature = 'owl-admin:make-admin
                            {--email= : Admin email (defaults to OWL_ADMIN_EMAIL)}
                            {--password= : Admin password (defaults to OWL_ADMIN_PASSWORD env)}
                            {--name= : Display name (defaults to OWL_ADMIN_NAME / config)}';

    protected $description = 'Create or update an admin user from env or options (never hardcoded credentials)';

    public function handle(AdminUserCreator $creator): int
    {
        $this->printBanner('Make Admin');

        if (! config('owl-admin-kit.admin_user.enabled', true)) {
            $this->error('Admin user creation is disabled (admin_user.enabled = false).');

            return self::FAILURE;
        }

        if ($this->option('name')) {
            config(['owl-admin-kit.admin_user.name' => (string) $this->option('name')]);
        }

        $resolver = new AdminUserCredentialResolver($this);
        $interactive = ! $this->option('no-interaction');
        $emailOverride = $this->option('email') ? (string) $this->option('email') : null;
        $passwordOverride = $this->option('password') ? (string) $this->option('password') : null;

        $resolved = $resolver->resolve($interactive, $emailOverride, $passwordOverride);

        if (! $resolved->success || $resolved->credentials === null) {
            $this->error($resolved->error ?? 'Could not resolve admin credentials.');

            return self::FAILURE;
        }

        $credentials = $resolved->credentials;

        if (($emailOverride !== null && strtolower($emailOverride) === 'admin@admin.com')
            || ($passwordOverride !== null && $passwordOverride === 'admin')) {
            $this->warn('You passed weak credentials explicitly. Use strong credentials outside local/testing environments.');
        }

        try {
            $user = $creator->create($credentials);
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(
            ($user->wasRecentlyCreated ? 'Admin user created: ' : 'Admin user updated: ')
            .$user->email.' (id: '.$user->getKey().')'
        );

        if ($credentials->passwordWasGenerated) {
            $this->warn('A random password was generated (local dev). Store it securely:');
            $this->line('  '.$credentials->password);
        }

        if ($this->output->isVerbose()) {
            $this->line('Name: '.$credentials->name);
        }

        return self::SUCCESS;
    }
}
