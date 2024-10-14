<?php

namespace Yabasi\Providers;

use Twig\TwigFunction;
use Yabasi\Localization\Translator;
use Yabasi\ServiceProvider\ServiceProvider;
use Yabasi\View\Template;

class TemplateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(Template::class, function ($container) {
            $template = new Template($container->get('config'), $container->get('csrfProtection'), $container->get(Translator::class));
            $this->addTranslationFunction($template, $container->get(Translator::class));
            return $template;
        });
    }

    protected function addTranslationFunction(Template $template, Translator $translator): void
    {
        $template->getTwig()->addFunction(new TwigFunction('__', function (string $key, array $params = []) use ($translator) {
            return $translator->get($key, $params);
        }));
    }
}