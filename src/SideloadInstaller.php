<?php

declare(strict_types=1);

namespace Composer\Plugin\SeparateFile;

use Composer\Composer;
use Composer\Installer;
use Composer\IO\IOInterface;
use Composer\Json\JsonManipulator;
use Composer\Package\Link;
use Composer\Semver\VersionParser;
use Composer\DependencyResolver\Request;

final class SideloadInstaller
{
    private const PLUGINS_FILENAME = 'composer-plugins.json';

    private Composer $composer;
    private IOInterface $io;
    private VersionParser $versionParser;

    public function __construct(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->versionParser = new VersionParser();
    }

    public function getPluginsFilePath(): string
    {
        /** @var string $vendorDir */
        $vendorDir = $this->composer->getConfig()->get('vendor-dir');
        $projectDir = dirname($vendorDir);

        return $projectDir . '/' . self::PLUGINS_FILENAME;
    }

    /**
     * @return array<string, string> package => constraint
     */
    public function readPluginsFile(): array
    {
        $path = $this->getPluginsFilePath();

        if (!file_exists($path)) {
            return [];
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new \RuntimeException('Failed to read ' . $path);
        }

        /** @var array{require?: array<string, string>} $data */
        $data = json_decode($contents, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Invalid JSON in ' . $path);
        }

        return $data['require'] ?? [];
    }

    public function addToPluginsFile(string $package, string $constraint): void
    {
        $path = $this->getPluginsFilePath();

        if (file_exists($path)) {
            $contents = file_get_contents($path);
            if ($contents === false) {
                throw new \RuntimeException('Failed to read ' . $path);
            }
        } else {
            $contents = "{\n\n}\n";
        }

        $manipulator = new JsonManipulator($contents);
        $manipulator->addLink('require', $package, $constraint, true);

        if (file_put_contents($path, $manipulator->getContents()) === false) {
            throw new \RuntimeException('Failed to write ' . $path);
        }
    }

    public function removeFromPluginsFile(string $package): void
    {
        $path = $this->getPluginsFilePath();

        if (!file_exists($path)) {
            $this->io->writeError('<warning>No ' . self::PLUGINS_FILENAME . ' found</warning>');
            return;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new \RuntimeException('Failed to read ' . $path);
        }

        $manipulator = new JsonManipulator($contents);
        $manipulator->removeSubNode('require', $package);

        if (file_put_contents($path, $manipulator->getContents()) === false) {
            throw new \RuntimeException('Failed to write ' . $path);
        }
    }

    /**
     * @param list<string> $extraAllowList
     */
    public function installPluginPackages(array $extraAllowList = []): int
    {
        $pluginRequires = $this->readPluginsFile();

        if ($pluginRequires === [] && $extraAllowList === []) {
            $this->io->writeError('<info>No sideloaded packages to install.</info>');
            return 0;
        }

        $rootPackage = $this->composer->getPackage();
        $originalRequires = $rootPackage->getRequires();

        try {
            // Build Link objects for plugin requirements
            $links = [];
            foreach ($pluginRequires as $package => $constraint) {
                $parsedConstraint = $this->versionParser->parseConstraints($constraint);
                $links[$package] = new Link(
                    $rootPackage->getName(),
                    $package,
                    $parsedConstraint,
                    Link::TYPE_REQUIRE,
                    $constraint,
                );
            }

            // Merge plugin requires into root package
            $rootPackage->setRequires(array_merge($originalRequires, $links));

            $allowList = array_merge(array_keys($pluginRequires), $extraAllowList);

            $installer = Installer::create($this->io, $this->composer);
            $installer->setUpdate(true);
            $installer->setWriteLock(false);
            $installer->setUpdateAllowList($allowList);
            $installer->setUpdateAllowTransitiveDependencies(Request::UPDATE_LISTED_WITH_TRANSITIVE_DEPS);
            $installer->setDumpAutoloader(true);
            $installer->setRunScripts(false);

            return $installer->run();
        } finally {
            $rootPackage->setRequires($originalRequires);
        }
    }
}
