<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Support\Facades\File;

class FrontendSetupStateRecorder
{
    /** @var list<string> */
    private array $errors = [];

    /** @var list<string> */
    private array $warnings = [];

    private bool $completed = false;

    private ?string $completedAt = null;

    private bool $packageJsonUpdated = false;

    private bool $viteConfigUpdated = false;

    private bool $appCssUpdated = false;

    private bool $appJsxCreated = false;

    private bool $inertiaMiddlewareUpdated = false;

    private bool $routesFileCreated = false;

    private bool $webRoutesIncluded = false;

    private bool $npmInstallRan = false;

    private bool $buildRan = false;

    public function __construct(
        private readonly string $preset,
    ) {}

    public static function start(string $preset): self
    {
        return new self($preset);
    }

    /**
     * @param  list<string>  $warnings
     */
    public function addWarnings(array $warnings): void
    {
        foreach ($warnings as $warning) {
            $this->addWarning($warning);
        }
    }

    public function addWarning(string $warning): void
    {
        $this->warnings[] = $warning;
    }

    public function addError(string $error): void
    {
        $this->errors[] = $error;
    }

    public function markPackageJsonUpdated(): void
    {
        $this->packageJsonUpdated = true;
    }

    public function markViteConfigUpdated(): void
    {
        $this->viteConfigUpdated = true;
    }

    public function markAppCssUpdated(): void
    {
        $this->appCssUpdated = true;
    }

    public function markAppJsxCreated(): void
    {
        $this->appJsxCreated = true;
    }

    public function markInertiaMiddlewareUpdated(): void
    {
        $this->inertiaMiddlewareUpdated = true;
    }

    public function markRoutesFileCreated(): void
    {
        $this->routesFileCreated = true;
    }

    public function markWebRoutesIncluded(): void
    {
        $this->webRoutesIncluded = true;
    }

    public function markNpmInstallRan(): void
    {
        $this->npmInstallRan = true;
    }

    public function markBuildRan(): void
    {
        $this->buildRan = true;
    }

    public function complete(string $basePath): void
    {
        $this->completed = true;
        $this->completedAt = now()->toIso8601String();
        $this->refreshOutcomeFlags($basePath);
    }

    public function persist(string $basePath): void
    {
        if ($this->completed) {
            $this->refreshOutcomeFlags($basePath);
        }

        (new FrontendSetupState(FrontendSetupState::pathFor($basePath)))->write($this->toArray());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'completed' => $this->completed,
            'completed_at' => $this->completedAt,
            'package_version' => PackageVersion::current(),
            'preset' => $this->preset,
            'package_json_updated' => $this->packageJsonUpdated,
            'vite_config_updated' => $this->viteConfigUpdated,
            'app_css_updated' => $this->appCssUpdated,
            'app_jsx_created' => $this->appJsxCreated,
            'inertia_middleware_updated' => $this->inertiaMiddlewareUpdated,
            'routes_file_created' => $this->routesFileCreated,
            'web_routes_included' => $this->webRoutesIncluded,
            'npm_install_ran' => $this->npmInstallRan,
            'build_ran' => $this->buildRan,
        ];

        if ($this->errors !== []) {
            $data['errors'] = $this->errors;
        }

        if ($this->warnings !== []) {
            $data['warnings'] = $this->warnings;
        }

        return $data;
    }

    private function refreshOutcomeFlags(string $basePath): void
    {
        $pagesRoutes = $basePath.'/routes/owl-admin-pages.php';
        $webRoutes = $basePath.'/routes/web.php';

        if (File::exists($pagesRoutes)) {
            $this->routesFileCreated = true;
        }

        if (File::exists($webRoutes)) {
            $contents = (string) File::get($webRoutes);
            $this->webRoutesIncluded = str_contains($contents, 'owl-admin-pages.php');
        }
    }
}
