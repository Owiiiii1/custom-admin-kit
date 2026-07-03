<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Composer\InstalledVersions;
use Throwable;

class PackageVersion
{
    private const PACKAGE_NAME = 'owlsolutions/custom-admin-kit';

    public static function current(): string
    {
        try {
            if (class_exists(InstalledVersions::class) && InstalledVersions::isInstalled(self::PACKAGE_NAME)) {
                $pretty = InstalledVersions::getPrettyVersion(self::PACKAGE_NAME);

                if (is_string($pretty) && $pretty !== '') {
                    return self::normalize($pretty);
                }

                $version = InstalledVersions::getVersion(self::PACKAGE_NAME);

                if (is_string($version) && $version !== '') {
                    return self::normalize($version);
                }
            }
        } catch (Throwable) {
            // Fall through to config fallback.
        }

        return self::resolveFallback(config('owl-admin-kit.version'));
    }

    public static function display(string $version): string
    {
        $normalized = self::normalize($version);

        if ($normalized === '') {
            return 'vdev';
        }

        return 'v'.$normalized;
    }

    public static function equals(string $left, string $right): bool
    {
        return self::normalize($left) === self::normalize($right);
    }

    /**
     * @internal
     */
    public static function resolve(?string $prettyVersion, ?string $rawVersion, mixed $configVersion): string
    {
        if (is_string($prettyVersion) && $prettyVersion !== '') {
            return self::normalize($prettyVersion);
        }

        if (is_string($rawVersion) && $rawVersion !== '') {
            return self::normalize($rawVersion);
        }

        return self::resolveFallback($configVersion);
    }

    private static function resolveFallback(mixed $configVersion): string
    {
        if (is_string($configVersion) && $configVersion !== '') {
            return self::normalize($configVersion);
        }

        return 'dev';
    }

    private static function normalize(string $version): string
    {
        return ltrim($version, 'vV');
    }
}
