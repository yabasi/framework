<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\CLI\Generators\MiddlewareGenerator;

class MakeMiddlewareCommand extends Command
{
    protected static $defaultName = 'make:middleware';
    private MiddlewareGenerator $middlewareGenerator;

    public function __construct(MiddlewareGenerator $middlewareGenerator)
    {
        parent::__construct();
        $this->middlewareGenerator = $middlewareGenerator;
    }

    protected function configure()
    {
        $this
            ->setDescription('Create a new middleware class')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the middleware');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        try {
            $filePath = $this->middlewareGenerator->generate($name);
            $output->writeln("<info>Middleware created successfully:</info> {$filePath}");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}