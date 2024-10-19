<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\Database\Migrations\Migrator;

/**
 * MigrateResetCommand class for resetting all database migrations.
 *
 * This command allows users to roll back all database migrations
 * through the command line interface. It uses the Migrator
 * to perform the actual reset process.
 */
class MigrateResetCommand extends Command
{
    /**
     * @var string The name of the console command
     */
    protected static $defaultName = 'migrate:reset';

    /**
     * @var Migrator The migrator instance
     */
    private Migrator $migrator;

    /**
     * MigrateResetCommand constructor.
     *
     * @param Migrator $migrator The migrator instance
     */
    public function __construct(Migrator $migrator)
    {
        parent::__construct();
        $this->migrator = $migrator;
    }

    /**
     * Configures the command.
     *
     * This method sets up the command's name and description.
     */
    protected function configure()
    {
        $this->setDescription('Rollback all database migrations');
    }

    /**
     * Executes the command to reset all database migrations.
     *
     * This method is called when the command is run from the CLI.
     * It rolls back all existing migrations, effectively resetting
     * the database schema to its initial state.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return int 0 if everything went fine, or an exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Rolling back all migrations...');

        $batches = $this->migrator->getMigrationBatches();

        foreach ($batches as $batch => $migrations) {
            foreach ($migrations as $migration) {
                $this->migrator->rollback($migration);
                $output->writeln("Rolled back: " . $migration);
            }
        }

        $output->writeln('All migrations have been rolled back.');

        return Command::SUCCESS;
    }
}