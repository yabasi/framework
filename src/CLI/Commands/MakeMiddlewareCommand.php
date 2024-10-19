<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\CLI\Generators\MiddlewareGenerator;

/**
 * MakeMiddlewareCommand class for generating new middleware classes.
 *
 * This command allows users to generate a new middleware class
 * through the command line interface. It uses the MiddlewareGenerator
 * to perform the actual file generation.
 */
class MakeMiddlewareCommand extends Command
{
    /**
     * @var string The name of the console command
     */
    protected static $defaultName = 'make:middleware';

    /**
     * @var MiddlewareGenerator The middleware generator instance
     */
    private MiddlewareGenerator $middlewareGenerator;

    /**
     * MakeMiddlewareCommand constructor.
     *
     * @param MiddlewareGenerator $middlewareGenerator The middleware generator instance
     */
    public function __construct(MiddlewareGenerator $middlewareGenerator)
    {
        parent::__construct();
        $this->middlewareGenerator = $middlewareGenerator;
    }

    /**
     * Configures the command.
     *
     * This method sets up the command's name, description, and arguments.
     */
    protected function configure()
    {
        $this
            ->setDescription('Create a new middleware class')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the middleware');
    }

    /**
     * Executes the command to create a new middleware class.
     *
     * This method is called when the command is run from the CLI.
     * It creates a new middleware class using the MiddlewareGenerator
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