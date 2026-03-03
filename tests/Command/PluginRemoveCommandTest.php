<?php

declare(strict_types=1);

namespace Composer\Plugin\SeparateFile\Tests\Command;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\SeparateFile\Command\PluginRemoveCommand;
use PHPUnit\Framework\TestCase;

class PluginRemoveCommandTest extends TestCase
{
    private PluginRemoveCommand $command;

    protected function setUp(): void
    {
        $composer = $this->createMock(Composer::class);
        $io = $this->createMock(IOInterface::class);

        $this->command = new PluginRemoveCommand($composer, $io);
    }

    public function testCommandName(): void
    {
        $this->assertSame('plugin:remove', $this->command->getName());
    }

    public function testCommandDescription(): void
    {
        $this->assertSame(
            'Remove packages from composer-plugins.json and uninstall them',
            $this->command->getDescription(),
        );
    }

    public function testCommandHasRequiredArrayPackagesArgument(): void
    {
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasArgument('packages'));

        $argument = $definition->getArgument('packages');
        $this->assertTrue($argument->isRequired());
        $this->assertTrue($argument->isArray());
    }
}
