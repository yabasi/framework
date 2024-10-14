<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\Database\Migrations\Migrator;

class MigrateRefreshCommand extends Command
{
    protected static $defaultName = 'migrate:refresh';
    private Migrator $migrator;

    public function __construct(Migrator $migrator)
    {
        parent::__construct();
        $this->migrator = $migrator;
    }

    protected function configure()
    {
        $this->setDescription('Refresh the database by rolling back all migrations and then migrating');
    }

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