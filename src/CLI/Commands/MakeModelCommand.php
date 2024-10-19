<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\CLI\Generators\ModelGenerator;

/**
 * MakeModelCommand class for generating new model classes.
 *
 * This command allows users to generate a new model class
 * through the command line interface. It uses the ModelGenerator
 * to perform the actual file generation.
 */
class MakeModelCommand extends Command
{
    /**
     * @var string The name of the console command
     */
    protected static $defaultName = 'make:model';

    /**
     * @var ModelGenerator The model generator instance
     */
    private ModelGenerator $modelGenerator;

    /**
     * MakeModelCommand constructor.
     *
     * @param ModelGenerator $modelGenerator The model generator instance
     */
    public function __construct(ModelGenerator $modelGenerator)
    {
        parent::__construct();
        $this->modelGenerator = $modelGenerator;
    }

    /**
     * Configures the command.
     *
     * This method sets up the command's name, description, arguments, and options.
     */
    protected function configure()
    {
        $this
            ->setDescription('Create a new model class')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the model')
            ->addOption('table', 't', InputOption::VALUE_OPTIONAL, 'The name of the database table');
    }

    /**
     * Executes the command to create a new model class.
     *
     * This method is called when the command is run from the CLI.
     * It creates a new model class using the ModelGenerator
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
        $table = $input->getOption('table');

        try {
            $filePath = $this->modelGenerator->generate($name, $table);
            $output->writeln("<info>Model created successfully:</info> {$filePath}");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}