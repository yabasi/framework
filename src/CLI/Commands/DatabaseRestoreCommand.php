<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\Database\DatabaseManager;

/**
 * DatabaseRestoreCommand class for restoring database dumps.
 *
 * This command allows users to restore a previously created database backup (dump)
 * through the command line interface. It uses the DatabaseManager
 * to perform the actual restore operation.
 */
class DatabaseRestoreCommand extends Command
{
    /**
     * @var string The name of the console command
     */
    protected static $defaultName = 'db:restore';

    /**
     * @var DatabaseManager The database manager instance
     */
    private DatabaseManager $databaseManager;

    /**
     * DatabaseRestoreCommand constructor.
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
     *
     * This method sets up the command's name, description, and arguments.
     */
    protected function configure()
    {
        $this
            ->setDescription('Restore database from a dump file')
            ->addArgument('file', InputArgument::REQUIRED, 'The dump file to restore from');
    }

    /**
     * Executes the command to restore a database from a dump file.
     *
     * This method is called when the command is run from the CLI.
     * It restores the database using the provided dump file via the DatabaseManager
     * and outputs the result to the console.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return int 0 if everything went fine, or an exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $input->getArgument('file');

        $output->writeln("Restoring database from dump file: $file");

        try {
            $this->databaseManager->restore($file);
            $output->writeln('Database restored successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}