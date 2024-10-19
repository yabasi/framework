<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\Database\Migrations\Migrator;

/**
 * MigrateStatusCommand class for displaying the status of database migrations.
 *
 * This command allows users to view the current status of all migrations
 * through the command line interface. It uses the Migrator
 * to retrieve migration information and displays it in a table format.
 */
class MigrateStatusCommand extends Command
{
    /**
     * @var string The name of the console command
     */
    protected static $defaultName = 'migrate:status';

    /**
     * @var Migrator The migrator instance
     */
    private Migrator $migrator;

    /**
     * MigrateStatusCommand constructor.
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
        $this->setDescription('Show the status of each migration');
    }

    /**
     * Executes the command to display the status of all migrations.
     *
     * This method is called when the command is run from the CLI.
     * It retrieves the status of all migrations and displays them
     * in a table format, showing which migrations have been run
     * and which are still pending.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return int 0 if everything went fine, or an exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $migrations = $this->migrator->getMigrations();
        $ranMigrations = $this->migrator->getRunMigrations();

        $table = new Table($output);
        $table->setHeaders(['Migration', 'Status']);

        foreach ($migrations as $migration) {
            $status = in_array($migration, $ranMigrations) ? 'Ran' : 'Pending';
            $table->addRow([basename($migration), $status]);
        }

        $table->render();

        return Command::SUCCESS;
    }
}