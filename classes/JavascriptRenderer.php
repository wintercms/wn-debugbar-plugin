<?php namespace Winter\Debugbar\Classes;

use Barryvdh\Debugbar\JavascriptRenderer as BaseJavascriptRenderer;
use DebugBar\DebugBar;
use Winter\Storm\Support\Str;

/**
 * Overrides the Laravel Debugbar JavascriptRenderer to replace the styling
 */
class JavascriptRenderer extends BaseJavascriptRenderer
{
    public function __construct(DebugBar $debugBar, $baseUrl = null, $basePath = null)
    {
        parent::__construct($debugBar, $baseUrl, $basePath);

        // Add Winter's styling
        $this->cssFiles['winter'] = __DIR__ . '/../assets/css/debugbar.css';
    }
}
