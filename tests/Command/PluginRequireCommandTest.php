<?php

declare(strict_types=1);

namespace Composer\Plugin\SeparateFile\Tests\Command;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\SeparateFile\Command\PluginRequireCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PluginRequireCommand::class)]
class PluginRequireCommandTest extends TestCase
{
    private PluginRequireCommand $command;

    protected function setUp(): void
    {
        $composer = $this->createStub(Composer::class);
        $io = $this->createStub(IOInterface::class);

        $this->command = new PluginRequireCommand($composer, $io);
    }

    public function testCommandName(): void
    {
        $this->assertSame('plugin:require', $this->command->getName());
    }

    public function testCommandDescription(): void
    {
        $this->assertSame(
            'Add and install packages via composer-plugins.json',
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
