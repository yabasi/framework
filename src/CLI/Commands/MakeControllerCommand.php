<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\CLI\Generators\ControllerGenerator;

class MakeControllerCommand extends Command
{
    protected static $defaultName = 'make:controller';
    private ControllerGenerator $controllerGenerator;

    public function __construct(ControllerGenerator $controllerGenerator)
    {
        parent::__construct();
        $this->controllerGenerator = $controllerGenerator;
    }

    protected function configure()
    {
        $this
            ->setDescription('Create a new controller class')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the controller')
            ->addOption('resource', 'r', InputOption::VALUE_NONE, 'Create a resource controller');
    }

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