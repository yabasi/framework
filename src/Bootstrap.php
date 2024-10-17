<?php

namespace Yabasi;

use Exception;
use Yabasi\Container\Container;
use Yabasi\Database\Connection;
use Yabasi\Database\Model;
use Yabasi\Logging\Logger;
use Yabasi\Providers\ConfigServiceProvider;
use Yabasi\Session\SecurityHandler;
use Yabasi\Session\SessionManager;

class Bootstrap
{
    private Container $container;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->container = new Container();
        $this->registerBaseServices();
        $this->registerServiceProviders();
        $this->startSession();
        $this->setupCacheDirectories();
        $this->setupErrorHandling();
        $this->setupModels();
    }

    /**
     * @throws Exception
     */
    public function run(): void
    {
        try {
            $app = new App($this->container);
            $app->run();
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    private function startSession(): void
    {
        $config = $this->container->get('config');
        $securityHandler = new SecurityHandler();
        $sessionManager = new SessionManager($config, $securityHandler);
        $this->container->singleton(SessionManager::class, $sessionManager);
        $sessionManager->start();
    }

    private function registerBaseServices(): void
    {
        $configProvider = new ConfigServiceProvider($this->container);
        $configProvider->register();
    }

    private function registerServiceProviders(): void
    {
        $providers = require BASE_PATH . '/config/providers.php';
        foreach ($providers as $provider) {
            if ($provider !== ConfigServiceProvider::class) {
                $providerInstance = new $provider($this->container);
                $providerInstance->register();
            }
        }
    }

    private function setupCacheDirectories(): void
    {
        $cachePath = $this->container->get('config')->get('cache.path', BASE_PATH . '/storage/cache');
        $directories = ['', '/twig', '/data', '/views'];

        foreach ($directories as $dir) {
            if (!is_dir($cachePath . $dir)) {
                mkdir($cachePath . $dir, 0755, true);
            }
        }
    }

    /**
     * @throws Exception
     */
    private function setupErrorHandling(): void
    {
        $config     = $this->container->get('config');
        $debugMode  = $config->get('app.debug', false);

        ini_set('display_errors', $debugMode ? '1' : '0');
        ini_set('display_startup_errors', $debugMode ? '1' : '0');
        error_reporting($debugMode ? E_ALL : 0);
    }

    /**
     * @throws Exception
     */
    protected function setupModels(): void
    {
        $connection = $this->container->get(Connection::class);
        $logger = $this->container->get(Logger::class);
        Model::setContainer($this->container);
        Model::setConnection($connection);
        Model::setLogger($logger);
    }

    /**
     * @throws Exception
     */
    private function handleException(Exception $e): void
    {
        echo "An error occurred: " . $e->getMessage();
        echo "<br>File: " . $e->getFile() . ", Line: " . $e->getLine();

        if ($this->container->get('config')->get('app.debug', false)) {
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
    }
}