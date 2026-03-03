<?php

declare(strict_types=1);

namespace Composer\Plugin\SeparateFile\Tests\Command;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\SeparateFile\Command\PluginInstallCommand;
use PHPUnit\Framework\TestCase;

class PluginInstallCommandTest extends TestCase
{
    private PluginInstallCommand $command;

    protected function setUp(): void
    {
        $composer = $this->createMock(Composer::class);
        $io = $this->createMock(IOInterface::class);

        $this->command = new PluginInstallCommand($composer, $io);
    }

    public function testCommandName(): void
    {
        $this->assertSame('plugin:install', $this->command->getName());
    }

    public function testCommandDescription(): void
    {
        $this->assertSame(
            'Install all packages from composer-plugins.json',
            $this->command->getDescription(),
        );
    }

    public function testCommandHasNoArguments(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertCount(0, $definition->getArguments());
    }
}
