<?php

namespace OttenIt\VersionReport;

use Composer\InstalledVersions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reports the versions this application runs on: operating system, Laravel, PHP, every installed
 * production Composer package and every npm package from package-lock.json, for the Otten IT
 * hosting dashboard (supported versions, known vulnerabilities, malware).
 *
 * Only with the right bearer token. Without one, with a wrong one, or while no token of at least
 * MIN_TOKEN_LENGTH characters is configured, it answers 404: to anyone else the endpoint does not
 * exist. A version list tells an attacker what to try, so it is never public.
 */
class VersionReportController
{
    public const REPORT_VERSION = 1;

    /** Shorter configured tokens disable the endpoint: one typed by hand is too easy to guess. */
    public const MIN_TOKEN_LENGTH = 32;

    public function __invoke(Request $request): JsonResponse
    {
        $token = (string) config('version-report.token');
        $given = (string) $request->bearerToken();

        if (strlen($token) < self::MIN_TOKEN_LENGTH || ! hash_equals($token, $given)) {
            abort(404);
        }

        return response()
            ->json([
                'report_version' => self::REPORT_VERSION,
                'laravel' => app()->version(),
                'php' => PHP_VERSION,
                'os' => OperatingSystem::read(),
                'packages' => $this->packages(),
                'npm' => NpmPackages::read(base_path()),
                'generated_at' => now()->toIso8601String(),
            ])
            ->header('Cache-Control', 'no-store')
            ->header('X-Robots-Tag', 'noindex');
    }

    /** @return list<array{name: string, version: string}> Production packages, from Composer's runtime data. */
    private function packages(): array
    {
        $packages = [];

        foreach (InstalledVersions::getAllRawData() as $installed) {
            foreach ($installed['versions'] ?? [] as $name => $package) {
                if (($package['dev_requirement'] ?? false) || ! isset($package['pretty_version']) || $name === ($installed['root']['name'] ?? null)) {
                    continue;
                }

                $packages[$name] = ['name' => $name, 'version' => $package['pretty_version']];
            }
        }

        ksort($packages);

        return array_values($packages);
    }
}
