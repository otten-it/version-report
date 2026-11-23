<?php

namespace OttenIt\VersionReport;

/**
 * The npm packages this application installed, from its package-lock.json: name, version and
 * dev flag, one entry per name and version (npm can install one package in several versions).
 * Dev packages are included: they run during every build.
 *
 *   no package.json                     →  null                  (the project does not use npm)
 *   package.json, no readable lock      →  ['lockfile' => false] (installed versions unknown)
 *   package-lock.json                   →  ['lockfile' => true, 'packages' => [...]]
 *
 * Reads lockfileVersion 2 and 3 (flat "packages" map) and 1 (nested "dependencies" tree).
 * Aliases count under their real name; links, workspaces, git and file dependencies are skipped:
 * they are no registry release.
 */
class NpmPackages
{
    /** A version npm installed from a registry, not a link, git remote, tarball URL or file. */
    private const REGISTRY_VERSION = '/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.+-]*)?$/';

    /** @return array{lockfile: bool, packages?: list<array{name: string, version: string, dev: bool}>}|null */
    public static function read(string $directory): ?array
    {
        if (! is_file($directory.'/package.json')) {
            return null;
        }

        // @: unreadable (permissions, open_basedir) must not break the report.
        $lock = json_decode((string) @file_get_contents($directory.'/package-lock.json'), true);

        if (! is_array($lock) || ! is_int($lock['lockfileVersion'] ?? null)) {
            return ['lockfile' => false];
        }

        $packages = $lock['lockfileVersion'] >= 2 && is_array($lock['packages'] ?? null)
            ? self::flat($lock['packages'])
            : self::tree(is_array($lock['dependencies'] ?? null) ? $lock['dependencies'] : []);

        return ['lockfile' => true, 'packages' => self::unique($packages)];
    }

    /**
     *   "node_modules/vite"                 { version, dev }
     *   "node_modules/a/node_modules/b"     a second version of b, nested: counted too
     *   "node_modules/my-alias"             { name: "real-package", version }: the real name
     *   "", "packages/ui", { link: true }   the project, workspaces, links: skipped
     */
    private static function flat(array $packages): array
    {
        $out = [];

        foreach ($packages as $path => $package) {
            $at = strrpos((string) $path, 'node_modules/');
            $version = $package['version'] ?? null;

            if ($at === false || ! is_array($package) || ! empty($package['link']) || ! is_string($version) || ! preg_match(self::REGISTRY_VERSION, $version)) {
                continue;
            }

            if (is_string($package['resolved'] ?? null) && ! preg_match('/^https?:/', $package['resolved'])) {
                continue; // git+ssh:, file:: not from a registry
            }

            $out[] = [
                'name' => is_string($package['name'] ?? null) ? $package['name'] : substr($path, $at + strlen('node_modules/')),
                'version' => $version,
                'dev' => ($package['dev'] ?? false) === true || ($package['devOptional'] ?? false) === true,
            ];
        }

        return $out;
    }

    /** lockfileVersion 1: an alias is { version: "npm:real-package@1.2.3" }. */
    private static function tree(array $dependencies, array &$out = []): array
    {
        foreach ($dependencies as $key => $dependency) {
            if (! is_array($dependency) || ! is_string($dependency['version'] ?? null)) {
                continue;
            }

            [$name, $version] = preg_match('/^npm:((?:@[^\/@]+\/)?[^@]+)@(.+)$/', $dependency['version'], $alias)
                ? [$alias[1], $alias[2]]
                : [(string) $key, $dependency['version']];

            if (preg_match(self::REGISTRY_VERSION, $version)) {
                $out[] = ['name' => $name, 'version' => $version, 'dev' => ($dependency['dev'] ?? false) === true];
            }

            self::tree(is_array($dependency['dependencies'] ?? null) ? $dependency['dependencies'] : [], $out);
        }

        return $out;
    }

    /** One per name and version, sorted; dev only when every copy is a dev dependency. */
    private static function unique(array $packages): array
    {
        $unique = [];

        foreach ($packages as $package) {
            $key = "{$package['name']}@{$package['version']}";

            if (isset($unique[$key])) {
                $unique[$key]['dev'] = $unique[$key]['dev'] && $package['dev'];
            } else {
                $unique[$key] = $package;
            }
        }

        ksort($unique);

        return array_values($unique);
    }
}
