<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\Container\Container;
use Yabasi\Database\Connection;
use Yabasi\Database\Migrations\Migrator;
use Yabasi\Filesystem\Filesystem;

/**
 * MigrateCommand class for running database migrations.
 *
 * This command allows users to run pending database migrations
 * through the command line interface. It uses the Migrator
 * to perform the actual migration process.
 */
class MigrateCommand extends Command
{
    /**
     * @var string The name of the console command
     */
    protected static $defaultName = 'migrate';

    /**
     * @var Migrator The migrator instance
     */
    private Migrator $migrator;

    /**
     * MigrateCommand constructor.
     *
     * @param Container $container The dependency injection container
     */
    public function __construct(Container $container)
    {
        parent::__construct();
        $this->migrator = new Migrator(
            $container->get(Connection::class),
            $container->get(Filesystem::class)
        );
    }

    /**
     * Configures the command.
     *
     * This method sets up the command's name and description.
     */
    protected function configure()
    {
        $this->setDescription('Run the database migrations');
    }

    /**
     * Executes the command to run database migrations.
     *
     * This method is called when the command is run from the CLI.
     * It runs pending migrations using the Migrator and outputs
     * the result to the console.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return int 0 if everything went fine, or an exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->migrator->getConnection()->getPdo() === null) {
            $output->writeln('<error>Database connection is not available. Skipping migrations.</error>');
            return Command::FAILURE;
        }

        $output->writeln('Running migrations...');

        $this->migrator->runPending();

        $output->writeln('Migrations completed successfully.');

        return Command::SUCCESS;
    }
}