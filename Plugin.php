<?php namespace Winter\Debugbar;

use App;
use Event;
use Config;
use BackendAuth;
use Backend\Models\UserRole;
use System\Classes\PluginBase;
use System\Classes\CombineAssets;
use Illuminate\Foundation\AliasLoader;

/**
 * Debugbar Plugin Information File
 *
 * TODO:
 * - Fix styling by scoping a html reset to phpdebugbar-openhandler and phpdebugbar
 */
class Plugin extends PluginBase
{
    /**
     * @var boolean Determine if this plugin should have elevated privileges.
     */
    public $elevated = true;

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'winter.debugbar::lang.plugin.name',
            'description' => 'winter.debugbar::lang.plugin.description',
            'author'      => 'Winter CMS',
            'icon'        => 'icon-bug',
            'homepage'    => 'https://github.com/wintercms/wn-debugbar-plugin',
            'replaces'    => ['RainLab.Debugbar' => '<= 3.1.1'],
        ];
    }

    /**
     * Register service provider, Twig extensions, and alias facade.
     */
    public function boot()
    {
        // Configure the debugbar
        Config::set('debugbar', Config::get('winter.debugbar::config'));

        // Service provider
        App::register(\Winter\Debugbar\Classes\ServiceProvider::class);

        // Register alias
        $alias = AliasLoader::getInstance();
        $alias->alias('Debugbar', '\Barryvdh\Debugbar\Facade');

        // Register middleware
        if (Config::get('app.debugAjax', false)) {
            $this->app['Illuminate\Contracts\Http\Kernel']->pushMiddleware('\Winter\Debugbar\Middleware\InterpretsAjaxExceptions');
        }

        // Add styling
        $addResources = function ($controller) {
            $debugBar = $this->app->make('Barryvdh\Debugbar\LaravelDebugbar');
            if ($debugBar->isEnabled()) {
                $controller->addCss(url(Config::get('cms.pluginsPath', '/plugins') . '/winter/debugbar/assets/css/debugbar.css'));
            }
        };
        Event::listen('backend.page.beforeDisplay', $addResources, PHP_INT_MAX);
        Event::listen('cms.page.beforeDisplay', $addResources, PHP_INT_MAX);

        Event::listen('cms.page.beforeDisplay', function ($controller, $url, $page) {
            // Twig extensions
            $twig = $controller->getTwig();
            if (!$twig->hasExtension(\Barryvdh\Debugbar\Twig\Extension\Debug::class)) {
                $twig->addExtension(new \Barryvdh\Debugbar\Twig\Extension\Debug($this->app));
                $twig->addExtension(new \Barryvdh\Debugbar\Twig\Extension\Stopwatch($this->app));
            }
        });
    }

    /**
     * Register the
     */
    public function register()
    {
        /*
         * Register asset bundles
         */
        CombineAssets::registerCallback(function ($combiner) {
            $combiner->registerBundle('$/winter/debugbar/assets/css/debugbar.less');
        });
    }

    /**
     * Register the permissions used by the plugin
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'winter.debugbar.access_debugbar' => [
                'tab' => 'winter.debugbar::lang.plugin.name',
                'label' => 'winter.debugbar::lang.plugin.access_debugbar',
                'roles' => UserRole::CODE_DEVELOPER,
            ],
            'winter.debugbar.access_stored_requests' => [
                'tab' => 'winter.debugbar::lang.plugin.name',
                'label' => 'winter.debugbar::lang.plugin.access_stored_requests',
                'roles' => UserRole::CODE_DEVELOPER,
            ],
        ];
    }
}
