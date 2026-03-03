<?php

declare(strict_types=1);

namespace Composer\Plugin\SeparateFile\Tests;

use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\SeparateFile\SeparateFileCommandProvider;
use Composer\Plugin\SeparateFile\SeparateFilePlugin;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SeparateFilePlugin::class)]
class SeparateFilePluginTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/composer-sideload-plugin-test-' . uniqid();
        mkdir($this->tempDir . '/vendor', 0777, true);
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

    private function createComposerStub(): Composer
    {
        $config = $this->createStub(Config::class);
        $config->method('get')->willReturn($this->tempDir . '/vendor');

        $composer = $this->createStub(Composer::class);
        $composer->method('getConfig')->willReturn($config);

        return $composer;
    }

    public function testGetCapabilities(): void
    {
        $plugin = new SeparateFilePlugin();

        $capabilities = $plugin->getCapabilities();

        $this->assertArrayHasKey(CommandProvider::class, $capabilities);
        $this->assertSame(SeparateFileCommandProvider::class, $capabilities[CommandProvider::class]);
    }

    public function testGetSubscribedEventsReturnsPostInstallAndUpdateHandlers(): void
    {
        $events = SeparateFilePlugin::getSubscribedEvents();

        $this->assertArrayHasKey(ScriptEvents::POST_INSTALL_CMD, $events);
        $this->assertArrayHasKey(ScriptEvents::POST_UPDATE_CMD, $events);
        $this->assertSame('onPostInstallOrUpdate', $events[ScriptEvents::POST_INSTALL_CMD]);
        $this->assertSame('onPostInstallOrUpdate', $events[ScriptEvents::POST_UPDATE_CMD]);
    }

    public function testActivateAndDeactivateDoNotThrow(): void
    {
        $composer = $this->createComposerStub();
        $io = $this->createStub(IOInterface::class);
        $plugin = new SeparateFilePlugin();

        $plugin->activate($composer, $io);
        $plugin->deactivate($composer, $io);

        // No exception thrown
        $this->assertTrue(true);
    }

    public function testOnPostInstallOrUpdateSkipsWhenNoPluginsFile(): void
    {
        $composer = $this->createComposerStub();
        $io = $this->createMock(IOInterface::class);

        $io->expects($this->never())->method('writeError');

        $plugin = new SeparateFilePlugin();
        $plugin->activate($composer, $io);

        $event = $this->createStub(Event::class);
        $plugin->onPostInstallOrUpdate($event);
    }
}
