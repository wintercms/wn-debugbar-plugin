<?php

namespace Winter\Debugbar\Middleware;

use Backend\Facades\BackendAuth;
use Closure;
use Fruitcake\LaravelDebugbar\LaravelDebugbar;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Application;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use Winter\Storm\Support\Facades\Config;

/**
 * Injects the Debugbar into the response, gated by the winter.debugbar permissions.
 *
 * Laravel Debugbar 4 removed its own InjectDebugbar middleware in favour of a
 * RequestHandled event listener (registered by the base ServiceProvider) that injects
 * the Debugbar unconditionally. This middleware replicates the legacy behaviour: it boots
 * the Debugbar, restricts request storage and injection to authorised users, and performs
 * the response handling itself via LaravelDebugbar::handleResponse(). Because handleResponse()
 * flags the response as modified, the base event listener becomes a no-op for this request.
 */
class InjectDebugbar
{
    /**
     * Request attribute set once this middleware has applied the permission checks.
     */
    public const HANDLED = 'winter.debugbar.handled';

    /**
     * The Laravel Application
     *
     * @var Application
     */
    protected $app;

    /**
     * The Debugbar instance
     *
     * @var LaravelDebugbar
     */
    protected $debugbar;

    /**
     * Create a new middleware instance.
     *
     * @param  Application     $app
     * @param  LaravelDebugbar $debugbar
     */
    public function __construct(Application $app, LaravelDebugbar $debugbar)
    {
        $this->app = $app;
        $this->debugbar = $debugbar;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$this->debugbar->isEnabled() || $this->debugbar->requestIsExcluded($request)) {
            return $next($request);
        }

        $this->debugbar->boot();

        // Render any downstream exception into a response so the Debugbar is still injected on
        // error pages. Laravel Debugbar 4 dropped its own InjectDebugbar middleware (which wrapped
        // the request in this try/catch via handleException()), so replicate it here rather than
        // letting the exception bypass handleResponse() below.
        try {
            /** @var \Illuminate\Http\Response $response */
            $response = $next($request);
        } catch (Throwable $e) {
            $handler = $this->app->make(ExceptionHandler::class);
            $handler->report($e);
            $response = $handler->render($request, $e);
        }

        // Database table might not exist yet
        try {
            $user = BackendAuth::getUser();
        } catch (Throwable $e) {
            $user = null;
        }

        $request->attributes->set(static::HANDLED, true);

        $canStore = ($user && $user->hasAccess('winter.debugbar.access_stored_requests'))
            || Config::get('winter.debugbar::store_all_requests', false);
        $canView = ($user && $user->hasAccess('winter.debugbar.access_debugbar'))
            || Config::get('winter.debugbar::allow_public_access', false);

        if (!$canStore) {
            // Disable stored requests
            // Note: this will completely disable storing requests from any users
            // without the required permission. If that functionality is desired again
            // in the future then we can look at overriding the OpenHandler controller
            $this->debugbar->setStorage(null);
        }

        if ($canView) {
            // Add the Debugbar to the response. This flags the response as modified, so the base
            // ServiceProvider's RequestHandled listener skips it.
            $this->debugbar->handleResponse($request, $response);

            return $response;
        }

        // The user may not see the Debugbar: store the request if permitted, but never send its
        // data in the response headers, and disable the Debugbar so the base RequestHandled and
        // Terminating listeners leave this request alone.
        try {
            if ($canStore) {
                $this->debugbar->collect();
            }
        } catch (Throwable $e) {
            $this->app['log']->error('Debugbar exception: ' . $e->getMessage(), ['exception' => $e]);
        } finally {
            $this->debugbar->disable();
        }

        return $response;
    }
}
