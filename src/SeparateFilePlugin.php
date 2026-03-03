<?php

declare(strict_types=1);

namespace Composer\Plugin\SeparateFile;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

class SeparateFilePlugin implements PluginInterface, Capable, EventSubscriberInterface
{
    private Composer $composer;
    private IOInterface $io;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    /**
     * @return array<class-string, class-string>
     */
    public function getCapabilities(): array
    {
        return [
            \Composer\Plugin\Capability\CommandProvider::class => SeparateFileCommandProvider::class,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'onPostInstallOrUpdate',
            ScriptEvents::POST_UPDATE_CMD => 'onPostInstallOrUpdate',
        ];
    }

    public function onPostInstallOrUpdate(Event $event): void
    {
        $installer = new SideloadInstaller($this->composer, $this->io);

        $pluginRequires = $installer->readPluginsFile();
        if ($pluginRequires === []) {
            return;
        }

        $this->io->writeError('<info>Re-installing sideloaded packages...</info>');
        $installer->installPluginPackages();
    }
}
