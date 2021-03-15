<?php

if (!class_exists(RainLab\Debugbar\Plugin::class)) {
    class_alias(Winter\Debugbar\Plugin::class, RainLab\Debugbar\Plugin::class);

    class_alias(Winter\Debugbar\Classes\ServiceProvider::class, RainLab\Debugbar\Classes\ServiceProvider::class);

    class_alias(Winter\Debugbar\Middleware\InjectDebugbar::class, RainLab\Debugbar\Middleware\InjectDebugbar::class);
}
