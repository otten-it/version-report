<?php

namespace OttenIt\VersionReport\Tests;

use Orchestra\Testbench\TestCase;
use OttenIt\VersionReport\NpmPackages;
use OttenIt\VersionReport\OperatingSystem;
use OttenIt\VersionReport\VersionReportServiceProvider;

class VersionReportTest extends TestCase
{
    private const TOKEN = 'a-long-shared-secret-for-the-tests';

    protected function getPackageProviders($app): array
    {
        return [VersionReportServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('version-report.token', self::TOKEN);
    }

    public function test_the_right_token_gets_the_versions(): void
    {
        $response = $this->getJson('/version-report', ['Authorization' => 'Bearer '.self::TOKEN])
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('report_version', 1)
            ->assertJsonPath('laravel', app()->version())
            ->assertJsonPath('php', PHP_VERSION)
            ->assertJsonPath('os', OperatingSystem::read());

        $names = array_column($response->json('packages'), 'name');
        $this->assertContains('laravel/framework', $names);
        $this->assertNotContains('phpunit/phpunit', $names, 'dev packages are not reported');

        // Always present since 1.2: null, or what package-lock.json holds (NpmPackagesTest).
        $this->assertArrayHasKey('npm', $response->json());
        $this->assertSame(NpmPackages::read(base_path()), $response->json('npm'));
    }

    public function test_no_token_or_a_wrong_token_gets_a_404(): void
    {
        $this->getJson('/version-report')->assertNotFound();
        $this->getJson('/version-report', ['Authorization' => 'Bearer wrong'])->assertNotFound();
        $this->getJson('/version-report?token='.self::TOKEN)->assertNotFound();
    }

    public function test_without_a_configured_token_the_endpoint_does_not_exist(): void
    {
        config(['version-report.token' => null]);

        $this->getJson('/version-report', ['Authorization' => 'Bearer '])->assertNotFound();
        $this->getJson('/version-report', ['Authorization' => 'Bearer anything'])->assertNotFound();
    }

    public function test_it_sets_no_cookies(): void
    {
        $response = $this->getJson('/version-report', ['Authorization' => 'Bearer '.self::TOKEN]);

        $this->assertEmpty($response->headers->getCookies());
    }
}
