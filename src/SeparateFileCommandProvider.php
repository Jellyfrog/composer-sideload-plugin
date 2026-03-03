<?php

declare(strict_types=1);

namespace Composer\Plugin\SeparateFile;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\SeparateFile\Command\PluginInstallCommand;
use Composer\Plugin\SeparateFile\Command\PluginRemoveCommand;
use Composer\Plugin\SeparateFile\Command\PluginRequireCommand;
use Composer\Plugin\SeparateFile\Command\PluginUpdateCommand;

final class SeparateFileCommandProvider implements CommandProvider
{
    private Composer $composer;
    private IOInterface $io;

    /**
     * @param array{composer: Composer, io: IOInterface} $args
     */
    public function __construct(array $args)
    {
        $this->composer = $args['composer'];
        $this->io = $args['io'];
    }

    /**
     * @return list<\Composer\Command\BaseCommand>
     */
    public function getCommands(): array
    {
        return [
            new PluginRequireCommand($this->composer, $this->io),
            new PluginRemoveCommand($this->composer, $this->io),
            new PluginUpdateCommand($this->composer, $this->io),
            new PluginInstallCommand($this->composer, $this->io),
        ];
    }
}
