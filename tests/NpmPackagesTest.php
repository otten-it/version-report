<?php

namespace OttenIt\VersionReport\Tests;

use OttenIt\VersionReport\NpmPackages;
use PHPUnit\Framework\TestCase;

class NpmPackagesTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/version-report-npm-'.getmypid();
        @mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir.'/*'));
        rmdir($this->dir);
    }

    private function project(?array $lock, bool $packageJson = true): ?array
    {
        if ($packageJson) {
            file_put_contents($this->dir.'/package.json', '{"private": true}');
        }
        if ($lock !== null) {
            file_put_contents($this->dir.'/package-lock.json', json_encode($lock));
        }

        return NpmPackages::read($this->dir);
    }

    private static function byKey(array $read): array
    {
        return array_column(array_map(fn ($p) => ["{$p['name']}@{$p['version']}", $p['dev']], $read['packages']), 1, 0);
    }

    public function test_a_project_without_package_json_does_not_use_npm(): void
    {
        $this->assertNull($this->project(null, packageJson: false));
    }

    public function test_package_json_without_a_readable_lock_file_is_said(): void
    {
        $this->assertSame(['lockfile' => false], $this->project(null));

        file_put_contents($this->dir.'/package-lock.json', 'not json');
        $this->assertSame(['lockfile' => false], NpmPackages::read($this->dir));
    }

    public function test_lockfile_version_3_nested_versions_aliases_dev_and_what_is_skipped(): void
    {
        $read = $this->project(['lockfileVersion' => 3, 'packages' => [
            '' => ['name' => 'my-app', 'version' => '1.0.0'],
            'node_modules/vite' => ['version' => '5.0.0', 'dev' => true, 'resolved' => 'https://registry.npmjs.org/vite/-/vite-5.0.0.tgz'],
            'node_modules/debug' => ['version' => '4.3.4'],
            'node_modules/send/node_modules/debug' => ['version' => '2.6.9'],
            'node_modules/my-lodash' => ['name' => 'lodash', 'version' => '4.17.20'],
            'node_modules/@scope/pkg' => ['version' => '1.0.0', 'devOptional' => true],
            'node_modules/ui' => ['resolved' => 'packages/ui', 'link' => true],
            'packages/ui' => ['name' => 'ui', 'version' => '0.0.1'],
            'node_modules/from-git' => ['version' => '1.0.0', 'resolved' => 'git+ssh://git@github.com/x/y.git#abc'],
            'node_modules/from-file' => ['version' => 'file:../lib'],
            'node_modules/a/node_modules/ms' => ['version' => '2.1.3', 'dev' => true],
            'node_modules/b/node_modules/ms' => ['version' => '2.1.3'],
        ]]);

        $this->assertTrue($read['lockfile']);
        $this->assertSame([
            '@scope/pkg@1.0.0' => true, 'debug@2.6.9' => false, 'debug@4.3.4' => false, 'lodash@4.17.20' => false,
            'ms@2.1.3' => false, 'vite@5.0.0' => true,
        ], self::byKey($read));
        $this->assertSame(['name', 'version', 'dev'], array_keys($read['packages'][0]));
    }

    public function test_lockfile_version_1_tree_and_aliases(): void
    {
        $read = $this->project(['lockfileVersion' => 1, 'dependencies' => [
            'express' => ['version' => '4.17.1', 'dependencies' => ['debug' => ['version' => '2.6.9']]],
            'debug' => ['version' => '4.3.4', 'dev' => true],
            'my-lodash' => ['version' => 'npm:lodash@4.17.20'],
            'scoped-alias' => ['version' => 'npm:@scope/real@2.0.0'],
            'from-git' => ['version' => 'github:x/y#abc'],
        ]]);

        $this->assertSame([
            '@scope/real@2.0.0' => false, 'debug@2.6.9' => false, 'debug@4.3.4' => true, 'express@4.17.1' => false, 'lodash@4.17.20' => false,
        ], self::byKey($read));
    }
}
