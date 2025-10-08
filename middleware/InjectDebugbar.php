<?php

namespace Winter\Debugbar\Middleware;

use Backend\Facades\BackendAuth;
use Barryvdh\Debugbar\Middleware\InjectDebugbar as BaseMiddleware;
use Closure;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use Winter\Storm\Support\Facades\Config;

class InjectDebugbar extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$this->debugbar->isEnabled() || $this->inExceptArray($request)) {
            return $next($request);
        }

        $this->debugbar->boot();

        try {
            /** @var \Illuminate\Http\Response $response */
            $response = $next($request);
        } catch (Throwable $e) {
            $response = $this->handleException($request, $e);
        }

        // Database table might not exist yet
        try {
            $user = BackendAuth::getUser();
        } catch (Throwable $e) {
            $user = null;
        }

        if ((!$user || !$user->hasAccess('winter.debugbar.access_stored_requests')) &&
            !Config::get('winter.debugbar::store_all_requests', false)) {
            // Disable stored requests
            // Note: this will completely disable storing requests from any users
            // without the required permission. If that functionality is desired again
            // in the future then we can look at overriding the OpenHandler controller
            $this->debugbar->setStorage(null);
        }

        // Modify the response to add the Debugbar if allowed
        if (
            ($user && $user->hasAccess('winter.debugbar.access_debugbar')) ||
            Config::get('winter.debugbar::allow_public_access', false)
        ) {
            $this->debugbar->modifyResponse($request, $response);
        }

        return $response;
    }
}
