<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\Config\Config;
use Yabasi\Filesystem\Filesystem;

class KeyGenerateCommand extends Command
{
    /**
     * Command configuration
     */
    protected static $defaultName = 'key:generate';

    protected Config $config;
    protected Filesystem $filesystem;

    public function __construct(Config $config, Filesystem $filesystem)
    {
        parent::__construct();
        $this->config = $config;
        $this->filesystem = $filesystem;
    }

    protected function configure()
    {
        $this->setDescription('Generate a new application encryption key')
            ->setHelp('This command generates a new random encryption key for your application');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // Generate a random 32 character string
            $key = $this->generateRandomKey();

            // Get config file path
            $configFile = BASE_PATH . '/config/config.php';

            if (!$this->filesystem->exists($configFile)) {
                $output->writeln('<error>Config file not found at: ' . $configFile . '</error>');
                return Command::FAILURE;
            }

            // Read current config
            $config = file_get_contents($configFile);

            // Add or update app.key in config
            if (strpos($config, "'key'") !== false) {
                $config = preg_replace(
                    "/'key'(\s+)=>(\s+)'[^']*'/",
                    "'key'$1=>$2'" . $key . "'",
                    $config
                );
            } else {
                // If key doesn't exist, add it to app array
                $config = str_replace(
                    "'app' => [",
                    "'app' => [\n        'key' => '" . $key . "',",
                    $config
                );
            }

            // Save updated config
            if ($this->filesystem->put($configFile, $config)) {
                $output->writeln('<info>Application key set successfully: ' . $key . '</info>');
                return Command::SUCCESS;
            }

            $output->writeln('<error>Could not write key to config file</error>');
            return Command::FAILURE;

        } catch (\Exception $e) {
            $output->writeln('<error>An error occurred: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }

    protected function generateRandomKey(): string
    {
        return bin2hex(random_bytes(32)); // 64 character hex string
    }
}