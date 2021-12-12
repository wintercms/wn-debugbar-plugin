<?php namespace RainLab\Debugbar;

use App;
use Backend\Classes\Controller as BackendController;
use Cms\Classes\Controller as CmsController;
use Cms\Classes\Page;
use Event;
use Config;
use Backend\Models\UserRole;
use Illuminate\Routing\Events\RouteMatched;
use RainLab\Debugbar\DataCollectors\OctoberBackendCollector;
use RainLab\Debugbar\DataCollectors\OctoberCmsCollector;
use System\Classes\PluginBase;
use System\Classes\CombineAssets;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Twig\Extension\ProfilerExtension;
use Twig\Profiler\Profile;

/**
 * Plugin Information File
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
            'name'        => 'rainlab.debugbar::lang.plugin.name',
            'description' => 'rainlab.debugbar::lang.plugin.description',
            'author'      => 'RainLab',
            'icon'        => 'icon-bug',
            'homepage'    => 'https://github.com/rainlab/debugbar-plugin'
        ];
    }

    /**
     * boot service provider, Twig extensions, and alias facade.
     */
    public function boot()
    {
        // Configure the debugbar
        Config::set('debugbar', Config::get('rainlab.debugbar::config'));

        // Service provider
        App::register(\RainLab\Debugbar\Classes\ServiceProvider::class);

        // Register alias
        $alias = AliasLoader::getInstance();
        $alias->alias('Debugbar', \Barryvdh\Debugbar\Facade::class);


        // Register middleware
        if (Config::get('app.debug_ajax', Config::get('app.debugAjax', false))) {
            $this->app[HttpKernelContract::class]->pushMiddleware(\RainLab\Debugbar\Middleware\InterpretsAjaxExceptions::class);
        }

        $this->registerResourceInjection();

        $this->registerTwigExtensions();

        $this->addCollectors();
    }

    public function addCollectors()
    {
        /** @var \Barryvdh\Debugbar\LaravelDebugbar $debugBar */
        $debugBar = $this->app->make('Barryvdh\Debugbar\LaravelDebugbar');
        $modelsCollector = $this->app->make('RainLab\Debugbar\DataCollectors\OctoberModelsCollector');
        $debugBar->addCollector($modelsCollector);

        Event::listen('backend.page.beforeDisplay', function (BackendController $controller, $action, array $params) use ($debugBar) {
            $debugBar->addCollector(new OctoberBackendCollector($controller, $action, $params));
        });

        Event::listen('cms.page.beforeDisplay', function(CmsController $controller, $url, Page $page) use ($debugBar) {
            $debugBar->addCollector(new OctoberCmsCollector($controller, $url, $page));
        });
    }
    /**
     * register the service provider
     */
    public function register()
    {
        /*
         * Register asset bundles
         */
        CombineAssets::registerCallback(function ($combiner) {
            $combiner->registerBundle('$/rainlab/debugbar/assets/less/debugbar.less');
        });
    }

    /**
     * registerTwigExtensions
     */
    protected function registerTwigExtensions()
    {
        Event::listen('cms.page.beforeDisplay', function ($controller, $url, $page) {
            $twig = $controller->getTwig();
            if (!$twig->hasExtension(\Barryvdh\Debugbar\Twig\Extension\Debug::class)) {
                $twig->addExtension(new \Barryvdh\Debugbar\Twig\Extension\Debug($this->app));
                $twig->addExtension(new \Barryvdh\Debugbar\Twig\Extension\Stopwatch($this->app));
            }

            if (!$twig->hasExtension(ProfilerExtension::class)) {
                $debugBar = $this->app->make('Barryvdh\Debugbar\LaravelDebugbar');

                $profile = new Profile();
                $twig->addExtension(new ProfilerExtension($profile));

                if (class_exists(\DebugBar\Bridge\NamespacedTwigProfileCollector::class)) {
                    $debugBar->addCollector(new \DebugBar\Bridge\NamespacedTwigProfileCollector($profile));
                } else {
                    $debugBar->addCollector(new \DebugBar\Bridge\TwigProfileCollector($profile));
                }
            }
        });

    }

    /**
     * registerResourceInjection adds styling to the page
     */
    protected function registerResourceInjection()
    {
        // Add styling
        $addResources = function($controller) {
            $debugBar = $this->app->make(\Barryvdh\Debugbar\LaravelDebugbar::class);
            if ($debugBar->isEnabled()) {
                $controller->addCss('/plugins/rainlab/debugbar/assets/css/debugbar.css');
            }
        };

        Event::listen('backend.page.beforeDisplay', $addResources, PHP_INT_MAX);

        Event::listen('cms.page.beforeDisplay', $addResources, PHP_INT_MAX);
    }

    /**
     * Register the permissions used by the plugin
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'rainlab.debugbar.access_debugbar' => [
                'tab' => 'rainlab.debugbar::lang.plugin.name',
                'label' => 'rainlab.debugbar::lang.plugin.access_debugbar',
                'roles' => UserRole::CODE_DEVELOPER,
            ],
            'rainlab.debugbar.access_stored_requests' => [
                'tab' => 'rainlab.debugbar::lang.plugin.name',
                'label' => 'rainlab.debugbar::lang.plugin.access_stored_requests',
                'roles' => UserRole::CODE_DEVELOPER,
            ],
        ];
    }
}
