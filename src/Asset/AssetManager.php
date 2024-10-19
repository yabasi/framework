<?php

namespace Yabasi\Asset;

use Yabasi\Config\Config;

/**
 * AssetManager class for managing CSS and JavaScript assets.
 *
 * This class provides functionality to add, process, and retrieve CSS and JavaScript assets,
 * including options for minification and caching.
 */
class AssetManager
{
    /** @var array */
    protected array $css = [];

    /** @var array */
    protected array $js = [];

    /** @var string */
    protected string $publicPath;

    /** @var string */
    protected string $cachePath;

    /** @var bool */
    protected bool $debug;

    /** @var bool */
    protected bool $minify;

    /**
     * AssetManager constructor.
     *
     * @param Config $config Configuration object
     */
    public function __construct(Config $config)
    {
        $this->publicPath = $config->get('paths.public', BASE_PATH . '/public');
        $this->cachePath = $config->get('paths.cache', BASE_PATH . '/storage/cache');
        $this->debug = $config->get('app.debug', false);
        $this->minify = $config->get('asset.minify', false);
    }

    /**
     * Add a CSS file to the asset list.
     *
     * @param string $path Path to the CSS file
     */
    public function addCss(string $path): void
    {
        $this->css[] = $path;
    }

    /**
     * Add a JavaScript file to the asset list.
     *
     * @param string $path Path to the JavaScript file
     */
    public function addJs(string $path): void
    {
        $this->js[] = $path;
    }

    /**
     * Get the HTML for all CSS assets.
     *
     * @return string HTML string for CSS assets
     */
    public function getCss(): string
    {
        return $this->getAssetHtml($this->css, 'css');
    }

    /**
     * Get the HTML for all JavaScript assets.
     *
     * @return string HTML string for JavaScript assets
     */
    public function getJs(): string
    {
        return $this->getAssetHtml($this->js, 'js');
    }

    /**
     * Generate HTML for assets based on type.
     *
     * @param array  $files Array of file paths
     * @param string $type  Asset type ('css' or 'js')
     *
     * @return string Generated HTML
     */
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

    /**
     * Generate HTML for assets in debug mode.
     *
     * @param array  $files Array of file paths
     * @param string $type  Asset type ('css' or 'js')
     *
     * @return string Generated HTML
     */
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

    /**
     * Generate HTML for assets in production mode.
     *
     * @param string $file Path to the asset file
     * @param string $type Asset type ('css' or 'js')
     *
     * @return string Generated HTML
     */
    protected function getProductionHtml(string $file, string $type): string
    {
        if ($type === 'css') {
            return '<link rel="stylesheet" href="' . $file . '">' . PHP_EOL;
        }
        return '<script src="' . $file . '"></script>' . PHP_EOL;
    }

    /**
     * Get or create a cached version of the asset files.
     *
     * @param array  $files Array of file paths
     * @param string $type  Asset type ('css' or 'js')
     *
     * @return string Path to the cached file
     */
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

    /**
     * Minify CSS content.
     *
     * @param string $css CSS content to minify
     *
     * @return string Minified CSS content
     */
    protected function minifyCss(string $css): string
    {
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
        return $css;
    }

    /**
     * Minify JavaScript content.
     *
     * @param string $js JavaScript content to minify
     *
     * @return string Minified JavaScript content
     */
    protected function minifyJs(string $js): string
    {
        $js = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\")\/\/.*))/', '', $js);
        $js = preg_replace('/\s+/', ' ', $js);
        return $js;
    }
}