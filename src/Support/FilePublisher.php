<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Support\Facades\File;

class FilePublisher
{
    /**
     * @param  array<string, string>  $map  stub relative path => destination relative path
     * @return list<string> Published destination paths (relative)
     */
    public function publish(
        string $stubsPath,
        string $basePath,
        array $map,
        bool $force = false,
        bool $dryRun = false,
        bool $backup = false,
    ): array {
        $published = [];

        foreach ($map as $stubRelative => $destinationRelative) {
            $source = rtrim($stubsPath, '/').'/'.ltrim($stubRelative, '/');
            $destination = $basePath.'/'.ltrim($destinationRelative, '/');

            if (! File::exists($source)) {
                continue;
            }

            if (File::exists($destination) && ! $force) {
                continue;
            }

            if ($dryRun) {
                $published[] = $destinationRelative;

                continue;
            }

            if ($backup && File::exists($destination)) {
                $backupPath = $destination.'.owl-admin-backup-'.date('YmdHis');
                File::copy($destination, $backupPath);
            }

            File::ensureDirectoryExists(dirname($destination));
            File::copy($source, $destination);
            $published[] = $destinationRelative;
        }

        return $published;
    }
}
