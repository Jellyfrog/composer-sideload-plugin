<?php

declare(strict_types=1);

namespace Composer\Plugin\SeparateFile\Command;

use Composer\Command\BaseCommand;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\SeparateFile\SideloadInstaller;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class PluginUpdateCommand extends BaseCommand
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
        $this->setName('plugin:update')
            ->setDescription('Update sideloaded packages from composer-plugins.json')
            ->addArgument(
                'packages',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Specific packages to update (updates all if omitted)',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var list<string> $packages */
        $packages = $input->getArgument('packages');

        $installer = new SideloadInstaller($this->composer, $this->io);
        $pluginRequires = $installer->readPluginsFile();

        if ($pluginRequires === []) {
            $this->io->writeError('<info>No sideloaded packages to update.</info>');
            return 0;
        }

        // Validate that requested packages are in the plugins file
        foreach ($packages as $package) {
            if (!isset($pluginRequires[$package])) {
                $this->io->writeError(sprintf(
                    '<error>Package %s is not in composer-plugins.json</error>',
                    $package,
                ));
                return 1;
            }
        }

        return $installer->installPluginPackages();
    }
}
