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

        // Remove Laravel Debugbar's default styling
        foreach ($this->cssFiles as $key => $file) {
            if (Str::startsWith($key, 'laravel')) {
                unset($this->cssFiles[$key]);
            }
        }

        // Add Winter's styling
        $this->cssFiles['winter'] = __DIR__ . '/../assets/css/debugbar.css';
    }
}
