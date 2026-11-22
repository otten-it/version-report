<?php

namespace OttenIt\VersionReport;

/**
 * The distribution from os-release, which PHP's php_uname() lacks (kernel only):
 *
 *   ID=ubuntu + VERSION_ID="26.04"  →  ['id' => 'ubuntu', 'version' => '26.04']
 */
class OperatingSystem
{
    /** @return array{id: string, version: string}|null Null when unreadable or without a version (rolling release). */
    public static function read(string $path = '/etc/os-release'): ?array
    {
        // @: a missing file or open_basedir must not break the report.
        $contents = (string) @file_get_contents($path);

        $id = self::value($contents, 'ID');
        $version = self::value($contents, 'VERSION_ID');

        return $id === null || $version === null ? null : ['id' => $id, 'version' => $version];
    }

    private static function value(string $contents, string $key): ?string
    {
        return preg_match('/^'.$key.'=(["\']?)([a-z0-9._-]{1,30})\1$/m', $contents, $match) === 1 ? $match[2] : null;
    }
}
