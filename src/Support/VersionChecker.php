<?php

namespace OwlSolutions\CustomAdminKit\Support;

class VersionChecker
{
    /**
     * @return list<CheckResult>
     */
    public function check(): array
    {
        $results = [];
        $matrix = config('owl-admin-kit.supported_matrix', []);

        $phpMin = str_replace('^', '', (string) ($matrix['php'] ?? '^8.3')).'.0';
        $phpOk = version_compare(PHP_VERSION, $phpMin, '>=');
        $results[] = $phpOk
            ? CheckResult::pass('php', 'PHP '.PHP_VERSION.' meets '.($matrix['php'] ?? '^8.3').' requirement.')
            : CheckResult::fail('php', 'PHP '.PHP_VERSION.' does not meet requirement.', 'Upgrade PHP to 8.3+.');

        if (defined('Illuminate\Foundation\Application::VERSION')) {
            $laravelVersion = \Illuminate\Foundation\Application::VERSION;
            $laravelOk = version_compare($laravelVersion, '13.0.0', '>=');
            $results[] = $laravelOk
                ? CheckResult::pass('laravel', "Laravel {$laravelVersion} meets ^13.0 requirement.")
                : CheckResult::fail('laravel', "Laravel {$laravelVersion} does not meet ^13.0 requirement.", 'Upgrade Laravel to 13.x.');
        } else {
            $results[] = CheckResult::fail(
                'laravel',
                'Laravel application context not detected.',
                'Run this command inside a Laravel project with the package installed.'
            );
        }

        $nodeVersion = trim((string) shell_exec('node -v 2>/dev/null'));
        if ($nodeVersion !== '') {
            $nodeOk = version_compare(ltrim($nodeVersion, 'v'), '20.19.0', '>=');
            $results[] = $nodeOk
                ? CheckResult::pass('node', "Node {$nodeVersion} detected.")
                : CheckResult::warn('node', "Node {$nodeVersion} may be below recommended 20.19+.", 'Upgrade Node for Vite 8 frontend builds.');
        } else {
            $results[] = CheckResult::warn('node', 'Node.js not found in PATH.', 'Install Node 20.19+ for frontend builds.');
        }

        return $results;
    }
}
