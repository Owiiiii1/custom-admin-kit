<?php

namespace OwlSolutions\CustomAdminKit\Support;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class FrontendDependencyChecker
{
    /**
     * npm import specifiers in core stubs mapped to installable package names.
     *
     * @var array<string, list<string>>
     */
    private const IMPORT_TO_PACKAGES = [
        'react' => ['react', 'react-dom'],
        '@inertiajs/react' => ['@inertiajs/react'],
        'lucide-react' => ['lucide-react'],
        'class-variance-authority' => ['class-variance-authority'],
        'clsx' => ['clsx'],
        'tailwind-merge' => ['tailwind-merge'],
        'radix-ui' => ['radix-ui'],
    ];

    /**
     * Build toolchain required to compile core stubs (audit: PostCSS + tailwind.config, not @tailwindcss/vite).
     *
     * @var list<string>
     */
    private const BUILD_TOOLCHAIN_PACKAGES = [
        'vite',
        '@vitejs/plugin-react',
        'laravel-vite-plugin',
        'tailwindcss',
        'postcss',
        'autoprefixer',
        'tailwindcss-animate',
    ];

    public function __construct(
        private readonly PublishMapResolver $publishMap,
    ) {}

    public function requiresFrontend(string $preset): bool
    {
        foreach ($this->publishMap->copyEntriesForPreset($preset) as $entry) {
            $stub = (string) ($entry['stub'] ?? '');

            if (str_ends_with($stub, '.jsx')
                || str_ends_with($stub, '.js')
                || str_ends_with($stub, '.css')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function requiredPackages(string $preset): array
    {
        if (! $this->requiresFrontend($preset)) {
            return [];
        }

        $packages = array_merge(
            $this->packagesFromStubImports($this->stubsPath()),
            self::BUILD_TOOLCHAIN_PACKAGES,
        );

        sort($packages);

        return array_values(array_unique($packages));
    }

    /**
     * @return list<string>
     */
    public function missingPackages(string $basePath, string $preset): array
    {
        $installed = $this->readInstalledPackages($basePath);
        $missing = [];

        foreach ($this->requiredPackages($preset) as $package) {
            if (! isset($installed[$package])) {
                $missing[] = $package;
            }
        }

        sort($missing);

        return $missing;
    }

    /**
     * @param  list<string>  $packages
     */
    public function buildInstallCommand(array $packages): string
    {
        if ($packages === []) {
            return 'npm install';
        }

        return 'npm install '.implode(' ', $packages);
    }

    /**
     * @return list<CheckResult>
     */
    public function check(string $basePath, string $preset, bool $strict): array
    {
        if (! $this->requiresFrontend($preset)) {
            return [
                CheckResult::pass('frontend-deps', 'Preset does not require frontend npm packages.'),
            ];
        }

        $missing = $this->missingPackages($basePath, $preset);

        if ($missing === []) {
            return [
                CheckResult::pass(
                    'frontend-deps',
                    'All '.count($this->requiredPackages($preset)).' required npm package(s) present.',
                ),
            ];
        }

        $command = $this->buildInstallCommand($missing);
        $list = implode(', ', $missing);
        $message = 'Missing npm package(s): '.$list;

        if ($strict) {
            return [
                CheckResult::fail(
                    'frontend-deps',
                    $message,
                    $command.'  (or re-run with --install-frontend-deps)',
                ),
            ];
        }

        return [
            CheckResult::warn(
                'frontend-deps',
                $message,
                $command,
            ),
        ];
    }

    /**
     * @param  list<string>  $packages
     */
    public function installPackages(string $basePath, array $packages): InstallProcessResult
    {
        if ($packages === []) {
            return new InstallProcessResult(true, 0, 'No npm packages to install.');
        }

        if (! $this->commandExists('npm')) {
            return new InstallProcessResult(false, 1, 'npm is not available in PATH.');
        }

        $process = new Process(
            array_merge(['npm', 'install', ...$packages]),
            $basePath,
            null,
            null,
            600,
        );

        $process->run();

        $output = trim($process->getOutput()."\n".$process->getErrorOutput());

        return new InstallProcessResult(
            $process->isSuccessful(),
            $process->getExitCode() ?? 1,
            $output !== '' ? $output : ($process->isSuccessful() ? 'npm install completed.' : 'npm install failed.'),
        );
    }

    /**
     * @return array<string, true>
     */
    private function readInstalledPackages(string $basePath): array
    {
        $path = $basePath.'/package.json';

        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            return [];
        }

        $installed = [];

        foreach (['dependencies', 'devDependencies', 'peerDependencies', 'optionalDependencies'] as $section) {
            if (! isset($decoded[$section]) || ! is_array($decoded[$section])) {
                continue;
            }

            foreach (array_keys($decoded[$section]) as $name) {
                if (is_string($name) && $name !== '') {
                    $installed[$name] = true;
                }
            }
        }

        return $installed;
    }

    /**
     * @return list<string>
     */
    private function packagesFromStubImports(string $stubsPath): array
    {
        $imports = $this->collectImportSpecifiers($stubsPath);
        $packages = [];

        foreach ($imports as $specifier) {
            if (isset(self::IMPORT_TO_PACKAGES[$specifier])) {
                array_push($packages, ...self::IMPORT_TO_PACKAGES[$specifier]);
            }
        }

        if ($this->stubCssUsesTailwind($stubsPath)) {
            // Already covered by BUILD_TOOLCHAIN_PACKAGES; keep import scan focused on JS.
        }

        return array_values(array_unique($packages));
    }

    /**
     * @return list<string>
     */
    private function collectImportSpecifiers(string $stubsPath): array
    {
        $jsRoot = $stubsPath.'/resources/js';

        if (! is_dir($jsRoot)) {
            return [];
        }

        $specifiers = [];

        foreach (File::allFiles($jsRoot) as $file) {
            $extension = strtolower($file->getExtension());

            if (! in_array($extension, ['js', 'jsx'], true)) {
                continue;
            }

            $contents = (string) file_get_contents($file->getPathname());

            if (preg_match_all('/(?:import\s+(?:[\w*\s{},]+from\s+)?|export\s+(?:\*|{[^}]*})\s+from\s+)[\'"]([^\'"]+)[\'"]/', $contents, $matches)) {
                foreach ($matches[1] as $specifier) {
                    if (! str_starts_with($specifier, '@/') && ! str_starts_with($specifier, '.')) {
                        $specifiers[] = $specifier;
                    }
                }
            }
        }

        sort($specifiers);

        return array_values(array_unique($specifiers));
    }

    private function stubCssUsesTailwind(string $stubsPath): bool
    {
        $cssPath = $stubsPath.'/resources/css/owl-admin.css';

        if (! is_file($cssPath)) {
            return false;
        }

        return str_contains((string) file_get_contents($cssPath), '@tailwind');
    }

    private function stubsPath(): string
    {
        return dirname(__DIR__, 2).'/stubs';
    }

    private function commandExists(string $command): bool
    {
        $process = Process::fromShellCommandline(sprintf('command -v %s', escapeshellarg($command)));

        $process->run();

        return $process->isSuccessful();
    }
}
