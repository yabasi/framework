<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\Database\Migrations\Migrator;

/**
 * MigrateRollbackCommand class for rolling back the last database migration batch.
 *
 * This command allows users to roll back the most recent migration batch
 * through the command line interface. It uses the Migrator
 * to perform the actual rollback process.
 */
class MigrateRollbackCommand extends Command
{
    /**
     * @var string The name of the console command
     */
    protected static $defaultName = 'migrate:rollback';

    /**
     * @var Migrator The migrator instance
     */
    private Migrator $migrator;

    /**
     * MigrateRollbackCommand constructor.
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
        $this->setDescription('Rollback the last database migration');
    }

    /**
     * Executes the command to roll back the last migration batch.
     *
     * This method is called when the command is run from the CLI.
     * It rolls back the most recent migration batch, reverting
     * the database schema to its previous state.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return int 0 if everything went fine, or an exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Rolling back the last migration batch...');

        $this->migrator->rollback();

        $output->writeln('Rollback completed successfully.');

        return Command::SUCCESS;
    }
}