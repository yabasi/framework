<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\View\Template;
use Yabasi\Cache\CacheManager;
use Yabasi\Config\Config;

class ViewClearCommand extends Command
{
    protected static $defaultName = 'view:clear';
    protected Template $template;
    protected CacheManager $cache;
    protected Config $config;

    public function __construct(Template $template, CacheManager $cache, Config $config)
    {
        parent::__construct();
        $this->template = $template;
        $this->cache = $cache;
        $this->config = $config;
    }

    protected function configure()
    {
        $this->setDescription('Clear all cached view files');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Clearing view cache...");

        try {

            $this->template->clearCache();

            if ($this->config->get('cache.driver') === 'redis') {
                $this->cache->tag('views')->flush();
                $output->writeln("Redis view cache cleared.");
            }

            $output->writeln("View cache cleared successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("<error>Error clearing view cache: " . $e->getMessage() . "</error>");
            return Command::FAILURE;
        }
    }
}