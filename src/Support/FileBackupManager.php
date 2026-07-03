<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Support\Facades\File;

class FileBackupManager
{
    public function backupDirectory(string $basePath): string
    {
        $relative = trim((string) config('owl-admin-kit.frontend_setup.backup_path', 'storage/app/owl-admin-kit/backups'), '/');
        $timestamp = now()->format('Y-m-d-H-i-s');
        $directory = $basePath.'/'.$relative.'/'.$timestamp;

        File::ensureDirectoryExists($directory);

        return $directory;
    }

    /**
     * @param  list<string>  $relativePaths
     * @return list<string> Backed up destination paths inside backup directory
     */
    public function backupFiles(string $basePath, string $backupDirectory, array $relativePaths): array
    {
        $backedUp = [];

        foreach ($relativePaths as $relativePath) {
            $source = $basePath.'/'.ltrim($relativePath, '/');

            if (! File::exists($source)) {
                continue;
            }

            $destination = $backupDirectory.'/'.str_replace('/', '__', ltrim($relativePath, '/'));
            File::copy($source, $destination);
            $backedUp[] = $destination;
        }

        return $backedUp;
    }
}
