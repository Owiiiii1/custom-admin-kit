<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Support\Facades\File;

class EnvironmentChecker
{
    /**
     * @return list<CheckResult>
     */
    public function check(string $basePath): array
    {
        $results = [];

        $writablePaths = [
            'storage' => $basePath.'/storage',
            'bootstrap/cache' => $basePath.'/bootstrap/cache',
        ];

        foreach ($writablePaths as $name => $path) {
            if (! File::isDirectory($path)) {
                $results[] = CheckResult::fail($name, "Directory missing: {$path}");
                continue;
            }

            $results[] = File::isWritable($path)
                ? CheckResult::pass($name, "{$name} is writable.")
                : CheckResult::fail($name, "{$name} is not writable.", "chmod -R ug+rwx {$path}");
        }

        $envPath = $basePath.'/.env';
        $results[] = File::exists($envPath)
            ? CheckResult::pass('env', '.env file exists.')
            : CheckResult::fail('env', '.env file not found.', 'Copy .env.example to .env and configure your app.');

        return $results;
    }
}
