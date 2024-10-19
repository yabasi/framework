<?php

namespace Yabasi\CLI;

use Exception;
use Symfony\Component\Console\Application as SymfonyConsole;
use Yabasi\CLI\Commands\DatabaseDumpCommand;
use Yabasi\CLI\Commands\DatabaseRestoreCommand;
use Yabasi\CLI\Commands\MakeControllerCommand;
use Yabasi\CLI\Commands\MakeMiddlewareCommand;
use Yabasi\CLI\Commands\MakeMigrationCommand;
use Yabasi\CLI\Commands\MakeModelCommand;
use Yabasi\CLI\Commands\MigrateCommand;
use Yabasi\CLI\Commands\MigrateRefreshCommand;
use Yabasi\CLI\Commands\MigrateResetCommand;
use Yabasi\CLI\Commands\MigrateRollbackCommand;
use Yabasi\CLI\Commands\MigrateStatusCommand;
use Yabasi\CLI\Generators\ControllerGenerator;
use Yabasi\CLI\Generators\MiddlewareGenerator;
use Yabasi\CLI\Generators\MigrationGenerator;
use Yabasi\CLI\Generators\ModelGenerator;
use Yabasi\Container\Container;
use Yabasi\Filesystem\Filesystem;

/**
 * Console class for managing CLI commands and interactions.
 *
 * This class sets up and manages the console application, including
 * registering available commands and handling command execution.
 */
class Console
{
    /**
     * @var Container The dependency injection container
     */
    protected Container $container;

    /**
     * @var SymfonyConsole The Symfony Console application instance
     */
    protected SymfonyConsole $console;

    /**
     * Console constructor.
     *
     * @param Container $container The dependency injection container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->console = new SymfonyConsole('Yabasi', '1.0.0');
        $this->registerCommands();
    }

    /**
     * Register all available console commands.
     *
     * @throws Exception If there's an error during command registration
     */
    protected function registerCommands(): void
    {
        $filesystem = $this->container->make(Filesystem::class);
        $vendorPath = $this->getStubPath();

        $this->console->add(new MakeModelCommand(new ModelGenerator($filesystem, $vendorPath)));
        $this->console->add(new MakeControllerCommand(new ControllerGenerator($filesystem, $vendorPath)));
        $this->console->add(new MakeMiddlewareCommand(new MiddlewareGenerator($filesystem, $vendorPath)));
        $this->console->add(new MakeMigrationCommand(new MigrationGenerator($filesystem, $vendorPath)));

        $this->console->add($this->container->make(MigrateCommand::class));
        $this->console->add($this->container->make(MigrateRollbackCommand::class));
        $this->console->add($this->container->make(MigrateRefreshCommand::class));
        $this->console->add($this->container->make(MigrateStatusCommand::class));
        $this->console->add($this->container->make(MigrateResetCommand::class));

        $this->console->add($this->container->make(DatabaseDumpCommand::class));
        $this->console->add($this->container->make(DatabaseRestoreCommand::class));
    }

    /**
     * Run the console application.
     *
     * @param array $argv The command line arguments
     * @return int The exit code of the console application
     */
    public function run(array $argv): int
    {
        return $this->console->run();
    }

    /**
     * Get the path to the stub files.
     *
     * @return string The full path to the stub files directory
     */
    protected function getStubPath(): string
    {
        return dirname(__DIR__, 4) . '/yabasi/framework/src/CLI/stubs/';
    }
}