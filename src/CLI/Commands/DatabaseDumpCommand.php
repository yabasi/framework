<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\Database\DatabaseManager;

/**
 * DatabaseDumpCommand class for creating database dumps.
 *
 * This command allows users to create a backup (dump) of the database
 * through the command line interface. It uses the DatabaseManager
 * to perform the actual dump operation.
 */
class DatabaseDumpCommand extends Command
{
    /**
     * @var string The name of the console command
     */
    protected static $defaultName = 'db:dump';

    /**
     * @var DatabaseManager The database manager instance
     */
    private DatabaseManager $databaseManager;

    /**
     * DatabaseDumpCommand constructor.
     *
     * @param DatabaseManager $databaseManager The database manager instance
     */
    public function __construct(DatabaseManager $databaseManager)
    {
        parent::__construct();
        $this->databaseManager = $databaseManager;
    }

    /**
     * Configures the command.
     */
    protected function configure()
    {
        $this->setDescription('Create a database dump');
    }

    /**
     * Executes the command to create a database dump.
     *
     * This method is called when the command is run from the CLI.
     * It creates a database dump using the DatabaseManager and
     * outputs the result to the console.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return int 0 if everything went fine, or an exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Creating database dump...');

        $dumpFile = $this->databaseManager->dump();

        $output->writeln("Database dump created successfully: $dumpFile");

        return Command::SUCCESS;
    }
}