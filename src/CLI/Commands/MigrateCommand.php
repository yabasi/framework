<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\Container\Container;
use Yabasi\Database\Connection;
use Yabasi\Database\Migrations\Migrator;
use Yabasi\Filesystem\Filesystem;

class MigrateCommand extends Command
{
    protected static $defaultName = 'migrate';
    private Migrator $migrator;

    public function __construct(Container $container)
    {
        parent::__construct();
        $this->migrator = new Migrator(
            $container->get(Connection::class),
            $container->get(Filesystem::class)
        );
    }

    protected function configure()
    {
        $this->setDescription('Run the database migrations');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Running migrations...');

        $this->migrator->runPending();

        $output->writeln('Migrations completed successfully.');

        return Command::SUCCESS;
    }
}