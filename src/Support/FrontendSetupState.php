<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Support\Facades\File;

class FrontendSetupState
{
    public function __construct(
        private readonly string $path,
    ) {}

    public static function relativePath(): string
    {
        return (string) config(
            'owl-admin-kit.frontend_setup.state_file',
            'storage/app/owl-admin-kit/frontend-setup-state.json',
        );
    }

    public static function pathFor(string $basePath): string
    {
        return rtrim($basePath, '/').'/'.ltrim(self::relativePath(), '/');
    }

    public function path(): string
    {
        return $this->path;
    }

    public function exists(): bool
    {
        return File::exists($this->path);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function read(): ?array
    {
        if (! $this->exists()) {
            return null;
        }

        $decoded = json_decode((string) File::get($this->path), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function write(array $data): void
    {
        File::ensureDirectoryExists(dirname($this->path));

        File::put(
            $this->path,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
        );
    }

    public function delete(): bool
    {
        if (! $this->exists()) {
            return false;
        }

        return File::delete($this->path);
    }
}
