<?php

namespace OttenIt\VersionReport\Tests;

use Orchestra\Testbench\TestCase; // not PHPUnit's: Laravel turns warnings into exceptions
use OttenIt\VersionReport\OperatingSystem;
use PHPUnit\Framework\Attributes\DataProvider;

class OperatingSystemTest extends TestCase
{
    public static function files(): array
    {
        return [
            'ubuntu' => ["PRETTY_NAME=\"Ubuntu 26.04 LTS\"\nVERSION_ID=\"26.04\"\nID=ubuntu\nID_LIKE=debian\n", ['id' => 'ubuntu', 'version' => '26.04']],
            'quoted id' => ["ID=\"amzn\"\nVERSION_ID=\"2023\"\n", ['id' => 'amzn', 'version' => '2023']],
            'debian' => ["# comment (trixie)\nVERSION_ID='13'\nID=debian", ['id' => 'debian', 'version' => '13']],
            'rolling, no version' => ["ID=arch\nBUILD_ID=rolling\n", null],
            'similar keys only' => ["ID_LIKE=debian\nUBUNTU_VERSION_ID=26.04\n", null],
            'unexpected characters' => ["ID=<script>\nVERSION_ID=1\n", null],
            'empty' => ['', null],
        ];
    }

    #[DataProvider('files')]
    public function test_it_reads_os_release(string $contents, ?array $expected): void
    {
        $path = tempnam(sys_get_temp_dir(), 'os-release');
        file_put_contents($path, $contents);

        try {
            $this->assertSame($expected, OperatingSystem::read($path));
        } finally {
            unlink($path);
        }
    }

    public function test_an_unreadable_file_gives_null(): void
    {
        $this->assertNull(OperatingSystem::read('/nonexistent/os-release'));
    }
}
