<?php

declare(strict_types=1);

namespace Composer\Plugin\SeparateFile\Command;

use Composer\Command\BaseCommand;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\SeparateFile\SideloadInstaller;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class PluginInstallCommand extends BaseCommand
{
    private Composer $composer;
    private IOInterface $io;

    public function __construct(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('plugin:install')
            ->setDescription('Install all packages from composer-plugins.json');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $installer = new SideloadInstaller($this->composer, $this->io);

        return $installer->installPluginPackages();
    }
}
