<?php
/**
 * To allow compatibility with plugins that extend the original RainLab.Debugbar plugin, this will alias those classes to
 * use the new Winter.Debugbar classes.
 */
$aliases = [
    // Regular aliases
    Winter\Debugbar\Plugin::class                    => 'RainLab\Debugbar\Plugin',
    Winter\Debugbar\Classes\ServiceProvider::class   => 'RainLab\Debugbar\Classes\ServiceProvider',
    Winter\Debugbar\Middleware\InjectDebugbar::class => 'RainLab\Debugbar\Middleware\InjectDebugbar',
];

foreach ($aliases as $original => $alias) {
    if (!class_exists($alias)) {
        class_alias($original, $alias);
    }
}
