<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\Database\DatabaseManager;

class DatabaseRestoreCommand extends Command
{
    protected static $defaultName = 'db:restore';
    private DatabaseManager $databaseManager;

    public function __construct(DatabaseManager $databaseManager)
    {
        parent::__construct();
        $this->databaseManager = $databaseManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Restore database from a dump file')
            ->addArgument('file', InputArgument::REQUIRED, 'The dump file to restore from');
    }

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