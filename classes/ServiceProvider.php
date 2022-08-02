<?php namespace Winter\Debugbar\Classes;

use Barryvdh\Debugbar\ServiceProvider as BaseServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Winter\Debugbar\Middleware\InjectDebugbar;

/**
 * ServiceProvider
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register the Debugbar Middleware
     *
     * @param  string $middleware
     */
    protected function registerMiddleware($middleware)
    {
        $kernel = $this->app[Kernel::class];
        $kernel->pushMiddleware(InjectDebugbar::class);
    }
}
