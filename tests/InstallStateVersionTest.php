<?php

namespace OwlSolutions\CustomAdminKit\Tests;

use OwlSolutions\CustomAdminKit\Support\InstallState;
use OwlSolutions\CustomAdminKit\Support\PackageVersion;

class InstallStateVersionTest extends PackageTestCase
{
    public function test_install_state_uses_package_version(): void
    {
        $path = storage_path('framework/testing/owl-admin-kit-state-'.uniqid('', true).'.json');

        (new InstallState($path))->write([
            'version' => PackageVersion::current(),
            'installed_at' => now()->toIso8601String(),
            'package' => 'owlsolutions/custom-admin-kit',
            'preset' => 'core',
            'published_files' => [],
            'published_count' => 0,
            'admin_seeded' => false,
        ]);

        $state = (new InstallState($path))->read();

        $this->assertIsArray($state);
        $this->assertSame(PackageVersion::current(), $state['version']);

        @unlink($path);
    }
}
