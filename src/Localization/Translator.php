<?php

namespace Yabasi\Localization;

use Yabasi\Config\Config;

class Translator
{
    protected array $translations = [];
    protected string $locale;
    protected string $fallbackLocale;
    protected Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->locale = $config->get('app.locale', 'en');
        $this->fallbackLocale = $config->get('app.fallback_locale', 'en');
        $this->loadTranslations($this->locale);
        if ($this->locale !== $this->fallbackLocale) {
            $this->loadTranslations($this->fallbackLocale);
        }
    }

    public function get(string $key, array $replace = [], string $locale = null): string
    {
        $locale = $locale ?: $this->locale;
        $line = $this->getLine($key, $locale);

        if ($line === null) {
            return $key;
        }

        return $this->makeReplacements($line, $replace);
    }


    protected function getLine(string $key, string $locale): ?string
    {
        $parts = explode('.', $key);
        $line = $this->translations[$locale] ?? [];

        foreach ($parts as $part) {
            if (!isset($line[$part])) {
                if ($locale === $this->fallbackLocale) {
                    return null;
                }
                return $this->getLine($key, $this->fallbackLocale);
            }
            $line = $line[$part];
        }

        return is_string($line) ? $line : null;
    }

    protected function makeReplacements(string $line, array $replace): string
    {
        foreach ($replace as $key => $value) {
            $line = str_replace(
                [':' . $key, ':' . strtoupper($key), ':' . ucfirst($key)],
                [$value, strtoupper($value), ucfirst($value)],
                $line
            );
        }

        return $line;
    }

    protected function loadTranslations(string $locale): void
    {
        $path = $this->config->get('paths.lang') . '/' . $locale . '.json';
        if (!file_exists($path)) {
            error_log("Translation file not found: $path");
            return;
        }

        $content = file_get_contents($path);
        $this->translations[$locale] = json_decode($content, true);
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
        if (!isset($this->translations[$locale])) {
            $this->loadTranslations($locale);
        }
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}