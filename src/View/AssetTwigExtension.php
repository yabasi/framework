<?php

namespace Yabasi\View;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Yabasi\Asset\AssetManager;

class AssetTwigExtension extends AbstractExtension
{
    protected AssetManager $assetManager;

    public function __construct(AssetManager $assetManager)
    {
        $this->assetManager = $assetManager;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset_css', [$this->assetManager, 'getCss'], ['is_safe' => ['html']]),
            new TwigFunction('asset_js', [$this->assetManager, 'getJs'], ['is_safe' => ['html']]),
        ];
    }

    public function getCss(): string
    {
        return $this->assetManager->getCss();
    }

    public function getJs(): string
    {
        return $this->assetManager->getJs();
    }
}