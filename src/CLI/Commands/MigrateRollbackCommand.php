<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\Database\Migrations\Migrator;

class MigrateRollbackCommand extends Command
{
    protected static $defaultName = 'migrate:rollback';
    private Migrator $migrator;

    public function __construct(Migrator $migrator)
    {
        parent::__construct();
        $this->migrator = $migrator;
    }

    protected function configure()
    {
        $this->setDescription('Rollback the last database migration');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Rolling back the last migration batch...');

        $this->migrator->rollback();

        $output->writeln('Rollback completed successfully.');

        return Command::SUCCESS;
    }
}