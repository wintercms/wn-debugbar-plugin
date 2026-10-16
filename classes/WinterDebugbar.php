<?php namespace Winter\Debugbar\Classes;

use DebugBar\JavascriptRenderer;
use Fruitcake\LaravelDebugbar\LaravelDebugbar;

class WinterDebugbar extends LaravelDebugbar
{
    /**
     * Returns the JavascriptRenderer for this instance with Winter's styling applied.
     *
     * Laravel Debugbar 4 performs the renderer configuration inside getJavascriptRenderer(),
     * so we defer to the parent to build and cache the renderer and then register Winter's
     * stylesheet via the renderer's asset API (the renderer is cached, so the CSS is only
     * added the first time it is built).
     */
    public function getJavascriptRenderer(?string $baseUrl = null, ?string $basePath = null): JavascriptRenderer
    {
        $alreadyBuilt = $this->jsRenderer !== null;

        $renderer = parent::getJavascriptRenderer($baseUrl, $basePath);

        if (!$alreadyBuilt) {
            $renderer->addAssets(cssFiles: ['debugbar.css'], basePath: __DIR__ . '/../assets/css');
        }

        return $renderer;
    }
}
