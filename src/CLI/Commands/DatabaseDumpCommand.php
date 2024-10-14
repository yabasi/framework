<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\Database\DatabaseManager;

class DatabaseDumpCommand extends Command
{
    protected static $defaultName = 'db:dump';
    private DatabaseManager $databaseManager;

    public function __construct(DatabaseManager $databaseManager)
    {
        parent::__construct();
        $this->databaseManager = $databaseManager;
    }

    protected function configure()
    {
        $this->setDescription('Create a database dump');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Creating database dump...');

        $dumpFile = $this->databaseManager->dump();

        $output->writeln("Database dump created successfully: $dumpFile");

        return Command::SUCCESS;
    }
}