<?php

namespace Yabasi\Asset;

use Yabasi\Config\Config;

class AssetManager
{
    protected array $css = [];
    protected array $js = [];
    protected string $publicPath;
    protected string $cachePath;
    protected bool $debug;
    protected bool $minify;

    public function __construct(Config $config)
    {
        $this->publicPath = $config->get('paths.public', BASE_PATH . '/public');
        $this->cachePath = $config->get('paths.cache', BASE_PATH . '/storage/cache');
        $this->debug = $config->get('app.debug', false);
        $this->minify = $config->get('asset.minify', false);
    }

    public function addCss(string $path): void
    {
        $this->css[] = $path;
    }

    public function addJs(string $path): void
    {
        $this->js[] = $path;
    }

    public function getCss(): string
    {
        return $this->getAssetHtml($this->css, 'css');
    }

    public function getJs(): string
    {
        return $this->getAssetHtml($this->js, 'js');
    }

    protected function getAssetHtml(array $files, string $type): string
    {
        $html = '';
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($this->minify) {
                $content = $type === 'css' ? $this->minifyCss($content) : $this->minifyJs($content);
            }
            if ($type === 'css') {
                $html .= '<style>' . $content . '</style>' . PHP_EOL;
            } else {
                $html .= '<script>' . $content . '</script>' . PHP_EOL;
            }
        }
        return $html;
    }

    protected function getDebugHtml(array $files, string $type): string
    {
        $html = '';
        foreach ($files as $file) {
            if ($type === 'css') {
                $html .= '<link rel="stylesheet" href="' . $file . '">' . PHP_EOL;
            } else {
                $html .= '<script src="' . $file . '"></script>' . PHP_EOL;
            }
        }
        return $html;
    }

    protected function getProductionHtml(string $file, string $type): string
    {
        if ($type === 'css') {
            return '<link rel="stylesheet" href="' . $file . '">' . PHP_EOL;
        }
        return '<script src="' . $file . '"></script>' . PHP_EOL;
    }

    protected function getCachedFile(array $files, string $type): string
    {
        $content = '';
        foreach ($files as $file) {
            $content .= file_get_contents($this->publicPath . '/' . $file);
        }

        $hash = md5($content);
        $cachedFileName = $type . '_' . $hash . '.' . $type;
        $cachedFilePath = $this->cachePath . '/' . $cachedFileName;

        if (!file_exists($cachedFilePath)) {
            if ($type === 'css') {
                $content = $this->minifyCss($content);
            } else {
                $content = $this->minifyJs($content);
            }
            file_put_contents($cachedFilePath, $content);
        }

        return '/cache/' . $cachedFileName;
    }

    protected function minifyCss(string $css): string
    {
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
        return $css;
    }

    protected function minifyJs(string $js): string
    {
        $js = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\")\/\/.*))/', '', $js);
        $js = preg_replace('/\s+/', ' ', $js);
        return $js;
    }
}