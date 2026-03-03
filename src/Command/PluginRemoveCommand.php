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

final class PluginRemoveCommand extends BaseCommand
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
        $this->setName('plugin:remove')
            ->setDescription('Remove packages from composer-plugins.json and uninstall them')
            ->addArgument(
                'packages',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Packages to remove (e.g. vendor/package)',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var list<string> $packages */
        $packages = $input->getArgument('packages');

        $installer = new SideloadInstaller($this->composer, $this->io);

        foreach ($packages as $package) {
            $this->io->writeError(sprintf(
                '<info>Removing %s from composer-plugins.json</info>',
                $package,
            ));

            $installer->removeFromPluginsFile($package);
        }

        // Removed packages must be in the allow list so the resolver can uninstall them
        return $installer->installPluginPackages(extraAllowList: $packages);
    }
}
