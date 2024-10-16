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

class Console
{
    protected Container $container;
    protected SymfonyConsole $console;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->console = new SymfonyConsole('Yabasi', '1.0.0');
        $this->registerCommands();
    }

    /**
     * @throws Exception
     */
    protected function registerCommands(): void
    {
        $filesystem = $this->container->make(Filesystem::class);
        $vendorPath = $this->getVendorPath();


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

    public function run(array $argv): int
    {
        return $this->console->run();
    }

    protected function getVendorPath(): string
    {
        return realpath(__DIR__ . '/../../../../vendor');
    }
}