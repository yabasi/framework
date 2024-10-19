<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\CLI\Generators\ControllerGenerator;

/**
 * MakeControllerCommand class for generating new controller classes.
 *
 * This command allows users to generate a new controller class
 * through the command line interface. It uses the ControllerGenerator
 * to perform the actual file generation.
 */
class MakeControllerCommand extends Command
{
    /**
     * @var string The name of the console command
     */
    protected static $defaultName = 'make:controller';

    /**
     * @var ControllerGenerator The controller generator instance
     */
    private ControllerGenerator $controllerGenerator;

    /**
     * MakeControllerCommand constructor.
     *
     * @param ControllerGenerator $controllerGenerator The controller generator instance
     */
    public function __construct(ControllerGenerator $controllerGenerator)
    {
        parent::__construct();
        $this->controllerGenerator = $controllerGenerator;
    }

    /**
     * Configures the command.
     *
     * This method sets up the command's name, description, arguments, and options.
     */
    protected function configure()
    {
        $this
            ->setDescription('Create a new controller class')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the controller')
            ->addOption('resource', 'r', InputOption::VALUE_NONE, 'Create a resource controller');
    }

    /**
     * Executes the command to create a new controller class.
     *
     * This method is called when the command is run from the CLI.
     * It creates a new controller class using the ControllerGenerator
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
        $resourceful = $input->getOption('resource');

        try {
            $filePath = $this->controllerGenerator->generate($name, $resourceful);
            $output->writeln("<info>Controller created successfully:</info> {$filePath}");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}