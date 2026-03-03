<?php

declare(strict_types=1);

namespace Composer\Plugin\SeparateFile\Tests;

use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Plugin\SeparateFile\SideloadInstaller;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SideloadInstallerTest extends TestCase
{
    private string $tempDir;
    private Composer&MockObject $composer;
    private IOInterface&MockObject $io;
    private SideloadInstaller $installer;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/composer-sideload-test-' . uniqid();
        mkdir($this->tempDir . '/vendor', 0777, true);

        $config = $this->createMock(Config::class);
        $config->method('get')
            ->with('vendor-dir')
            ->willReturn($this->tempDir . '/vendor');

        $this->composer = $this->createMock(Composer::class);
        $this->composer->method('getConfig')->willReturn($config);

        $this->io = $this->createMock(IOInterface::class);

        $this->installer = new SideloadInstaller($this->composer, $this->io);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    private function pluginsFilePath(): string
    {
        return $this->tempDir . '/composer-plugins.json';
    }

    public function testGetPluginsFilePath(): void
    {
        $this->assertSame(
            $this->tempDir . '/composer-plugins.json',
            $this->installer->getPluginsFilePath(),
        );
    }

    public function testReadPluginsFileReturnsEmptyArrayWhenFileDoesNotExist(): void
    {
        $this->assertSame([], $this->installer->readPluginsFile());
    }

    public function testReadPluginsFileReturnsRequireSection(): void
    {
        file_put_contents(
            $this->pluginsFilePath(),
            json_encode(['require' => ['psr/log' => '^3.0', 'monolog/monolog' => '^3.0']]),
        );

        $this->assertSame(
            ['psr/log' => '^3.0', 'monolog/monolog' => '^3.0'],
            $this->installer->readPluginsFile(),
        );
    }

    public function testReadPluginsFileReturnsEmptyArrayWhenNoRequireSection(): void
    {
        file_put_contents($this->pluginsFilePath(), '{}');

        $this->assertSame([], $this->installer->readPluginsFile());
    }

    public function testReadPluginsFileThrowsOnInvalidJson(): void
    {
        file_put_contents($this->pluginsFilePath(), 'not valid json{{{');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid JSON');
        $this->installer->readPluginsFile();
    }

    public function testAddToPluginsFileCreatesFileWhenNotExists(): void
    {
        $this->installer->addToPluginsFile('psr/log', '^3.0');

        $this->assertFileExists($this->pluginsFilePath());
        $data = json_decode((string) file_get_contents($this->pluginsFilePath()), true);
        $this->assertSame('^3.0', $data['require']['psr/log']);
    }

    public function testAddToPluginsFileAddsToExistingFile(): void
    {
        $this->installer->addToPluginsFile('psr/log', '^3.0');
        $this->installer->addToPluginsFile('monolog/monolog', '^3.0');

        $data = json_decode((string) file_get_contents($this->pluginsFilePath()), true);
        $this->assertSame('^3.0', $data['require']['psr/log']);
        $this->assertSame('^3.0', $data['require']['monolog/monolog']);
    }

    public function testAddToPluginsFileSortsPackages(): void
    {
        $this->installer->addToPluginsFile('monolog/monolog', '^3.0');
        $this->installer->addToPluginsFile('a/package', '^1.0');

        $data = json_decode((string) file_get_contents($this->pluginsFilePath()), true);
        $keys = array_keys($data['require']);
        $this->assertSame(['a/package', 'monolog/monolog'], $keys);
    }

    public function testAddToPluginsFileUpdatesExistingConstraint(): void
    {
        $this->installer->addToPluginsFile('psr/log', '^2.0');
        $this->installer->addToPluginsFile('psr/log', '^3.0');

        $data = json_decode((string) file_get_contents($this->pluginsFilePath()), true);
        $this->assertSame('^3.0', $data['require']['psr/log']);
    }

    public function testRemoveFromPluginsFileRemovesPackage(): void
    {
        $this->installer->addToPluginsFile('psr/log', '^3.0');
        $this->installer->addToPluginsFile('monolog/monolog', '^3.0');

        $this->installer->removeFromPluginsFile('psr/log');

        $data = json_decode((string) file_get_contents($this->pluginsFilePath()), true);
        $this->assertArrayHasKey('monolog/monolog', $data['require']);
        $this->assertArrayNotHasKey('psr/log', $data['require']);
    }

    public function testRemoveFromPluginsFileWarnsWhenNoFile(): void
    {
        $this->io->expects($this->once())
            ->method('writeError')
            ->with($this->stringContains('No composer-plugins.json found'));

        $this->installer->removeFromPluginsFile('psr/log');
    }

    public function testInstallPluginPackagesReturnsZeroWhenNoRequires(): void
    {
        $result = $this->installer->installPluginPackages();

        $this->assertSame(0, $result);
    }

    public function testInstallPluginPackagesOutputsMessageWhenNoRequires(): void
    {
        $this->io->expects($this->once())
            ->method('writeError')
            ->with($this->stringContains('No sideloaded packages to install'));

        $this->installer->installPluginPackages();
    }

    public function testInstallPluginPackagesReturnsZeroWhenEmptyRequiresAndEmptyExtraAllowList(): void
    {
        $result = $this->installer->installPluginPackages([]);

        $this->assertSame(0, $result);
    }
}
