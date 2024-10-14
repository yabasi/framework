<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\CLI\Generators\ModelGenerator;

class MakeModelCommand extends Command
{
    protected static $defaultName = 'make:model';
    private ModelGenerator $modelGenerator;

    public function __construct(ModelGenerator $modelGenerator)
    {
        parent::__construct();
        $this->modelGenerator = $modelGenerator;
    }

    protected function configure()
    {
        $this
            ->setDescription('Create a new model class')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the model')
            ->addOption('table', 't', InputOption::VALUE_OPTIONAL, 'The name of the database table');
    }

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