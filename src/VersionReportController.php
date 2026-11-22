<?php

namespace OttenIt\VersionReport;

use Composer\InstalledVersions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reports the versions this application runs on: operating system, Laravel, PHP and every installed
 * production package, for the Otten IT hosting dashboard (supported versions, known vulnerabilities).
 *
 * Only with the right bearer token. Without one, with a wrong one, or while no token is
 * configured, it answers 404: to anyone else the endpoint does not exist. A version list tells
 * an attacker what to try, so it is never public.
 */
class VersionReportController
{
    public const REPORT_VERSION = 1;

    public function __invoke(Request $request): JsonResponse
    {
        $token = (string) config('version-report.token');
        $given = (string) $request->bearerToken();

        if ($token === '' || $given === '' || ! hash_equals($token, $given)) {
            abort(404);
        }

        return response()
            ->json([
                'report_version' => self::REPORT_VERSION,
                'laravel' => app()->version(),
                'php' => PHP_VERSION,
                'os' => OperatingSystem::read(),
                'packages' => $this->packages(),
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
