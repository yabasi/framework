<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\Database\Migrations\Migrator;

class MigrateResetCommand extends Command
{
    protected static $defaultName = 'migrate:reset';
    private Migrator $migrator;

    public function __construct(Migrator $migrator)
    {
        parent::__construct();
        $this->migrator = $migrator;
    }

    protected function configure()
    {
        $this->setDescription('Rollback all database migrations');
    }

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