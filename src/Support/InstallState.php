<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Support\Facades\File;

class InstallState
{
    public function __construct(
        private readonly string $path,
    ) {}

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

        $contents = File::get($this->path);
        $decoded = json_decode($contents, true);

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
