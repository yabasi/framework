<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\Database\Migrations\Migrator;

/**
 * MigrateRefreshCommand class for refreshing the database migrations.
 *
 * This command allows users to refresh the database by rolling back all migrations
 * and then re-running them through the command line interface. It uses the Migrator
 * to perform the actual migration process.
 */
class MigrateRefreshCommand extends Command
{
    /**
     * @var string The name of the console command
     */
    protected static $defaultName = 'migrate:refresh';

    /**
     * @var Migrator The migrator instance
     */
    private Migrator $migrator;

    /**
     * MigrateRefreshCommand constructor.
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
        $this->setDescription('Refresh the database by rolling back all migrations and then migrating');
    }

    /**
     * Executes the command to refresh database migrations.
     *
     * This method is called when the command is run from the CLI.
     * It rolls back all existing migrations and then re-runs them,
     * effectively refreshing the database schema.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return int 0 if everything went fine, or an exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Refreshing the database...');

        // Rollback all migrations
        while (count($this->migrator->getRunMigrations()) > 0) {
            $this->migrator->rollback();
        }

        // Run all migrations
        $this->migrator->runPending();

        $output->writeln('Database refresh completed successfully.');

        return Command::SUCCESS;
    }
}