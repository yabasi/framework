<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\CLI\Generators\MigrationGenerator;

/**
 * MakeMigrationCommand class for generating new database migration files.
 *
 * This command allows users to generate a new database migration file
 * through the command line interface. It uses the MigrationGenerator
 * to perform the actual file generation.
 */
class MakeMigrationCommand extends Command
{
    /**
     * @var string The name of the console command
     */
    protected static $defaultName = 'make:migration';

    /**
     * @var MigrationGenerator The migration generator instance
     */
    private MigrationGenerator $migrationGenerator;

    /**
     * MakeMigrationCommand constructor.
     *
     * @param MigrationGenerator $migrationGenerator The migration generator instance
     */
    public function __construct(MigrationGenerator $migrationGenerator)
    {
        parent::__construct();
        $this->migrationGenerator = $migrationGenerator;
    }

    /**
     * Configures the command.
     *
     * This method sets up the command's name, description, arguments, and options.
     */
    protected function configure()
    {
        $this
            ->setDescription('Create a new migration file')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the migration')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'The type of migration (default, create, update, delete)', 'default');
    }

    /**
     * Executes the command to create a new migration file.
     *
     * This method is called when the command is run from the CLI.
     * It creates a new migration file using the MigrationGenerator
     * and outputs the result to the console.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return int 0 if everything went fine, or an exit code
     */
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