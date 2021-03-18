<?php

use Winter\Storm\Support\ClassLoader;

/**
 * To allow compatibility with plugins that extend the original RainLab.Debugbar plugin, this will alias those classes to
 * use the new Winter.Debugbar classes.
 */
$aliases = [
    Winter\Debugbar\Plugin::class                    => RainLab\Debugbar\Plugin::class,
    Winter\Debugbar\Classes\ServiceProvider::class   => RainLab\Debugbar\Classes\ServiceProvider::class,
    Winter\Debugbar\Middleware\InjectDebugbar::class => RainLab\Debugbar\Middleware\InjectDebugbar::class,
];

app(ClassLoader::class)->addAliases($aliases);
