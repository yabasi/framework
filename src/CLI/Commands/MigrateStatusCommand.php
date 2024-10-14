<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\Database\Migrations\Migrator;

class MigrateStatusCommand extends Command
{
    protected static $defaultName = 'migrate:status';
    private Migrator $migrator;

    public function __construct(Migrator $migrator)
    {
        parent::__construct();
        $this->migrator = $migrator;
    }

    protected function configure()
    {
        $this->setDescription('Show the status of each migration');
    }

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