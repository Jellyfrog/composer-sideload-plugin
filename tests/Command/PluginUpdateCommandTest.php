<?php

declare(strict_types=1);

namespace Composer\Plugin\SeparateFile\Tests\Command;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\SeparateFile\Command\PluginUpdateCommand;
use PHPUnit\Framework\TestCase;

class PluginUpdateCommandTest extends TestCase
{
    private PluginUpdateCommand $command;

    protected function setUp(): void
    {
        $composer = $this->createMock(Composer::class);
        $io = $this->createMock(IOInterface::class);

        $this->command = new PluginUpdateCommand($composer, $io);
    }

    public function testCommandName(): void
    {
        $this->assertSame('plugin:update', $this->command->getName());
    }

    public function testCommandDescription(): void
    {
        $this->assertSame(
            'Update sideloaded packages from composer-plugins.json',
            $this->command->getDescription(),
        );
    }

    public function testCommandHasOptionalArrayPackagesArgument(): void
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasArgument('packages'));

        $argument = $definition->getArgument('packages');
        $this->assertFalse($argument->isRequired());
        $this->assertTrue($argument->isArray());
    }
}
