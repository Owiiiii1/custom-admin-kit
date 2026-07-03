<?php

namespace OwlSolutions\CustomAdminKit\Tests;

use OwlSolutions\CustomAdminKit\Support\PackageVersion;

class PackageVersionTest extends PackageTestCase
{
    public function test_resolve_falls_back_to_config_when_installed_versions_unavailable(): void
    {
        $this->assertSame('0.2.0', PackageVersion::resolve(null, null, '0.2.0'));
        $this->assertSame('0.2.0', PackageVersion::resolve(null, null, 'v0.2.0'));
    }

    public function test_resolve_falls_back_to_dev_when_no_sources_available(): void
    {
        $this->assertSame('dev', PackageVersion::resolve(null, null, null));
        $this->assertSame('dev', PackageVersion::resolve(null, null, ''));
    }

    public function test_resolve_prefers_pretty_version_over_config(): void
    {
        $this->assertSame('0.2.0', PackageVersion::resolve('v0.2.0', '0.2.0.0', '9.9.9'));
    }

    public function test_resolve_uses_raw_version_when_pretty_missing(): void
    {
        $this->assertSame('0.2.0.0', PackageVersion::resolve(null, '0.2.0.0', '9.9.9'));
    }

    public function test_current_returns_config_fallback_when_composer_version_unavailable(): void
    {
        config(['owl-admin-kit.version' => '0.2.0-fallback']);

        if (class_exists(\Composer\InstalledVersions::class)
            && \Composer\InstalledVersions::isInstalled('owlsolutions/custom-admin-kit')) {
            $this->markTestSkipped('Composer InstalledVersions is available in this environment.');
        }

        $this->assertSame('0.2.0-fallback', PackageVersion::current());
    }

    public function test_display_prefixes_v_when_missing(): void
    {
        $this->assertSame('v0.2.0', PackageVersion::display('0.2.0'));
        $this->assertSame('v0.2.0', PackageVersion::display('v0.2.0'));
    }

    public function test_equals_ignores_v_prefix(): void
    {
        $this->assertTrue(PackageVersion::equals('0.2.0', 'v0.2.0'));
        $this->assertFalse(PackageVersion::equals('0.1.1', '0.2.0'));
    }
}
