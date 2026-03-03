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

final class PluginRequireCommand extends BaseCommand
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
        $this->setName('plugin:require')
            ->setDescription('Add and install packages via composer-plugins.json')
            ->addArgument(
                'packages',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Packages to require (e.g. vendor/package:^1.0)',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var list<string> $packages */
        $packages = $input->getArgument('packages');

        $installer = new SideloadInstaller($this->composer, $this->io);

        foreach ($packages as $packageArg) {
            $parts = explode(':', $packageArg, 2);
            $package = $parts[0];
            $constraint = $parts[1] ?? '*';

            $this->io->writeError(sprintf(
                '<info>Adding %s (%s) to composer-plugins.json</info>',
                $package,
                $constraint,
            ));

            $installer->addToPluginsFile($package, $constraint);
        }

        return $installer->installPluginPackages();
    }
}
