<?php

namespace Yabasi\Providers;

use Twig\TwigFunction;
use Yabasi\Asset\AssetManager;
use Yabasi\Cache\CacheManager;
use Yabasi\Config\Config;
use Yabasi\Localization\Translator;
use Yabasi\Security\CsrfProtection;
use Yabasi\ServiceProvider\ServiceProvider;
use Yabasi\Session\SessionManager;
use Yabasi\View\Template;

class TemplateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(Template::class, function ($container) {
            $template = new Template(
                $container->get(Config::class),
                $container->get(CsrfProtection::class),
                $container->get(Translator::class),
                $container->get(CacheManager::class),
                $container->get(AssetManager::class),
                $container->get(SessionManager::class)
            );

            $this->ensureTranslationFunction($template, $container->get(Translator::class));

            return $template;
        });
    }

    protected function ensureTranslationFunction(Template $template, Translator $translator): void
    {
        $twig = $template->getTwig();
        if (!$twig->getFunction('__')) {
            $twig->addFunction(new TwigFunction('__', function (string $key, array $params = []) use ($translator) {
                return $translator->get($key, $params);
            }));
        }
    }
}