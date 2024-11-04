<?php

namespace Yabasi\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yabasi\View\Template;
use Yabasi\Cache\CacheManager;
use Yabasi\Config\Config;

class ViewCacheCommand extends Command
{
    protected static $defaultName = 'view:cache';
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
        $this->setDescription('Compile all Twig templates and cache them');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Caching views...");

        try {
            $this->template->cacheAllTemplates();

            if ($this->config->get('cache.driver') === 'redis') {
                $this->cache->tag('views')->flush();
                $output->writeln("Redis view cache cleared and regenerated.");
            }

            $output->writeln("Views cached successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("<error>Error caching views: " . $e->getMessage() . "</error>");
            return Command::FAILURE;
        }
    }
}