<?php namespace Winter\Debugbar\Classes;

use Fruitcake\LaravelDebugbar\LaravelDebugbar;
use Fruitcake\LaravelDebugbar\ServiceProvider as BaseServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Log\Logger;
use Winter\Debugbar\Middleware\InjectDebugbar;

/**
 * ServiceProvider
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * Laravel Debugbar 4 no longer registers an injection middleware; instead the base
     * service provider injects the Debugbar via a RequestHandled event listener. We still
     * need Winter's InjectDebugbar middleware so that injection and request storage can be
     * gated by the winter.debugbar permissions, so we push it onto the kernel here. The
     * middleware short-circuits the base listener (by disabling the Debugbar) whenever the
     * current user is not permitted to view it.
     */
    public function boot(Dispatcher $events): void
    {
        /*
         * Registered before the base listener so it runs first: a response that never passed through
         * InjectDebugbar (for example a maintenance-mode response returned by earlier middleware) has
         * not been permission checked, so disable the Debugbar for it.
         */
        $events->listen(RequestHandled::class, function (RequestHandled $event): void {
            if (
                $this->app->resolved(LaravelDebugbar::class)
                && !$event->request->attributes->get(InjectDebugbar::HANDLED)
            ) {
                $this->app->make(LaravelDebugbar::class)->disable();
            }
        });

        parent::boot($events);

        $this->registerMiddleware(InjectDebugbar::class);
    }

    /**
     * Register the service provider.
     *
     * Debugbar's log collectors type-hint Illuminate\Log\Logger. Storm aliases that class to the
     * "log" service, which is a LogManager, so bind it to the default channel's Logger instead.
     */
    public function register(): void
    {
        parent::register();

        $this->app->bind(Logger::class, fn ($app) => $app->make('log')->driver());
    }

    /**
     * Register the Debugbar Middleware
     *
     * @param  string $middleware
     */
    protected function registerMiddleware(string $middleware): void
    {
        $kernel = $this->app[Kernel::class];
        $kernel->pushMiddleware($middleware);
    }
}
