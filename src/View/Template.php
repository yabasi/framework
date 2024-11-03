<?php

namespace Yabasi\View;

use Twig\Cache\CacheInterface;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;
use Yabasi\Asset\AssetManager;
use Yabasi\Cache\CacheManager;
use Yabasi\Config\Config;
use Yabasi\Localization\Translator;
use Yabasi\Security\CsrfProtection;
use Yabasi\Session\SessionManager;

class Template
{
    protected Environment $twig;
    protected Config $config;
    protected Translator $translator;
    protected CsrfProtection $csrfProtection;
    protected CacheManager $cacheManager;
    protected AssetManager $assetManager;
    protected $sessionManager;

    public function __construct(
        Config $config,
        CsrfProtection $csrfProtection,
        Translator $translator,
        CacheManager $cacheManager,
        AssetManager $assetManager,
        SessionManager $sessionManager
    )
    {
        $this->config = $config;
        $this->csrfProtection = $csrfProtection;
        $this->translator = $translator;
        $this->cacheManager = $cacheManager;
        $this->assetManager = $assetManager;
        $this->sessionManager = $sessionManager;

        $loader = new FilesystemLoader($config->get('paths.views'));

        $options = [
            'cache' => $this->getCacheDriver(),
            'auto_reload' => $config->get('app.debug', false),
            'debug' => $config->get('app.debug', false),
        ];

        $this->twig = new Environment($loader, $options);
        $this->addCustomFunctions();
    }

    protected function addCustomFunctions(): void
    {
        $this->twig->addExtension(new DebugExtension());
        $this->twig->addFunction(new TwigFunction('__', [$this, 'translate']));
        $this->twig->addFunction(new TwigFunction('url', [$this, 'url']));
        $this->twig->addFunction(new TwigFunction('csrf_field', [$this, 'csrfField']));
        $this->twig->addFunction(new TwigFunction('csrf_token', [$this, 'getCsrfToken']));
        $this->twig->addFunction(new TwigFunction('old', [$this, 'getOldInput']));

        $this->twig->addExtension(new AssetTwigExtension($this->assetManager));
    }

    public function getOldInput($key, $default = null)
    {
        return $this->sessionManager->getFlash('_old_input')[$key] ?? $default;
    }

    protected function getCacheDriver(): CacheInterface|string
    {
        $cacheDriver = $this->config->get('cache.driver', 'file');

        return match ($cacheDriver) {
            'redis' => $this->getRedisCache(),
            default => $this->config->get('cache.twig', BASE_PATH . '/storage/cache/twig'),
        };
    }

    protected function getRedisCache(): CacheInterface
    {
        return new class($this->cacheManager) implements CacheInterface {
            private $cacheManager;

            public function __construct($cacheManager) {
                $this->cacheManager = $cacheManager;
            }

            public function generateKey(string $name, string $className = ''): string
            {
                return 'twig_' . hash('sha256', $name . $className);
            }

            public function write(string $key, string $content): void
            {
                $this->cacheManager->set($this->generateKey($key), $content);
            }

            public function load(string $key): void
            {
                $content = $this->cacheManager->get($this->generateKey($key));
                if ($content !== null) {
                    eval('?>'.$content);
                }
            }

            public function getTimestamp(string $key): int
            {
                return $this->cacheManager->has($this->generateKey($key)) ? time() : 0;
            }
        };
    }

    public function translate(string $key, array $params = []): string
    {
        return $this->translator->get($key, $params);
    }

    public function render($template, $variables = []): string
    {
        return $this->twig->render($template . '.twig', $variables);
    }

    public function url($path, $fullUrl = true)
    {
        if ($fullUrl) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $domainName = $_SERVER['HTTP_HOST'];
            $baseUrl = $protocol . $domainName;

            return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
        } else {
            return $path;
        }
    }

    public function csrfField(): string
    {
        $token = $this->csrfProtection->generateToken();
        return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
    }

    public function getCsrfToken(): string
    {
        return $this->csrfProtection->generateToken();
    }

    public function getTwig(): Environment
    {
        return $this->twig;
    }

    public function clearAllCache(): void
    {
        if ($this->config->get('cache.driver') === 'redis') {
            $this->cacheManager->clear();
        } else {
            $cacheDir = $this->config->get('cache.twig', BASE_PATH . '/storage/cache/twig');
            $this->clearDirectory($cacheDir);
        }
    }

    private function clearDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
    }
}