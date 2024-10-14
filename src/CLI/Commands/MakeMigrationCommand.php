<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\CLI\Generators\MigrationGenerator;

class MakeMigrationCommand extends Command
{
    protected static $defaultName = 'make:migration';
    private MigrationGenerator $migrationGenerator;

    public function __construct(MigrationGenerator $migrationGenerator)
    {
        parent::__construct();
        $this->migrationGenerator = $migrationGenerator;
    }

    protected function configure()
    {
        $this
            ->setDescription('Create a new migration file')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the migration')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'The type of migration (default, create, update, delete)', 'default');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $type = $input->getOption('type');

        try {
            $filePath = $this->migrationGenerator->generate($name, $type);
            $output->writeln("<info>Migration created successfully:</info> {$filePath}");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}