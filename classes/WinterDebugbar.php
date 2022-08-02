<?php namespace Winter\Debugbar\Classes;

use Barryvdh\Debugbar\LaravelDebugbar;

class WinterDebugbar extends LaravelDebugbar
{
    /**
     * Returns a winterized JavascriptRenderer for this instance
     *
     * @param string $baseUrl
     * @param string $basePathng
     * @return JavascriptRenderer
     */
    public function getJavascriptRenderer($baseUrl = null, $basePath = null)
    {
        if ($this->jsRenderer === null) {
            $this->jsRenderer = new JavascriptRenderer($this, $baseUrl, $basePath);
        }
        return $this->jsRenderer;
    }
}
