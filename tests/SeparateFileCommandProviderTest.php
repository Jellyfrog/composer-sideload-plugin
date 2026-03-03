<?php

declare(strict_types=1);

namespace Composer\Plugin\SeparateFile\Tests;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\SeparateFile\Command\PluginInstallCommand;
use Composer\Plugin\SeparateFile\Command\PluginRemoveCommand;
use Composer\Plugin\SeparateFile\Command\PluginRequireCommand;
use Composer\Plugin\SeparateFile\Command\PluginUpdateCommand;
use Composer\Plugin\SeparateFile\SeparateFileCommandProvider;
use PHPUnit\Framework\TestCase;

class SeparateFileCommandProviderTest extends TestCase
{
    private SeparateFileCommandProvider $provider;

    protected function setUp(): void
    {
        $composer = $this->createMock(Composer::class);
        $io = $this->createMock(IOInterface::class);

        $this->provider = new SeparateFileCommandProvider([
            'composer' => $composer,
            'io' => $io,
        ]);
    }

    public function testGetCommandsReturnsFourCommands(): void
    {
        $commands = $this->provider->getCommands();

        $this->assertCount(4, $commands);
    }

    public function testGetCommandsReturnsCorrectTypes(): void
    {
        $commands = $this->provider->getCommands();

        $types = array_map(static fn ($cmd) => $cmd::class, $commands);

        $this->assertContains(PluginRequireCommand::class, $types);
        $this->assertContains(PluginRemoveCommand::class, $types);
        $this->assertContains(PluginUpdateCommand::class, $types);
        $this->assertContains(PluginInstallCommand::class, $types);
    }
}
