<?php namespace Winter\Debugbar;

use Backend\Classes\Controller as BackendController;
use Backend\Models\UserRole;
use Barryvdh\Debugbar\Facades\Debugbar;
use Barryvdh\Debugbar\LaravelDebugbar;
use Barryvdh\Debugbar\SymfonyHttpDriver;
use Cms\Classes\Controller as CmsController;
use Cms\Classes\Layout;
use Cms\Classes\Page;
use Config;
use Event;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Session\SessionManager;
use System\Classes\CombineAssets;
use System\Classes\PluginBase;
use Twig\Extension\ProfilerExtension;
use Twig\Profiler\Profile;
use Winter\Debugbar\Classes\WinterDebugbar;
use Winter\Debugbar\Collectors\BackendCollector;
use Winter\Debugbar\Collectors\CmsCollector;
use Winter\Debugbar\Collectors\ComponentsCollector;
use Winter\Debugbar\Collectors\ModelsCollector;

/**
 * Winter.Debugbar Plugin
 */
class Plugin extends PluginBase
{
    /**
     * @var boolean Determine if this plugin should have elevated privileges.
     */
    public $elevated = true;

    /**
     * Returns information about this plugin.
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
            'replaces'    => ['RainLab.Debugbar' => '<= 3.3.2'],
        ];
    }

    /**
     * Registers the plugin
     */
    public function register()
    {
        // Provide the winter.debugbar config under the debugbar namespace
        Config::registerNamespaceAlias('winter.debugbar', 'debugbar');

        // Register the service provider
        $this->app->register(\Winter\Debugbar\Classes\ServiceProvider::class);

        // Replace the LaravelDebugbar with the WinterDebugbar
        $this->app->singleton(LaravelDebugbar::class, function ($app) {
            $debugbar = new WinterDebugbar($app);

            if ($app->bound(SessionManager::class)) {
                $sessionManager = $app->make(SessionManager::class);
                $httpDriver = new SymfonyHttpDriver($sessionManager);
                $debugbar->setHttpDriver($httpDriver);
            }

            return $debugbar;
        });

        // Register alias
        $alias = AliasLoader::getInstance();
        $alias->alias('Debugbar', Debugbar::class);

        // Register the asset bundle
        CombineAssets::registerCallback(function ($combiner) {
            $combiner->registerBundle('$/winter/debugbar/assets/less/debugbar.less');
        });
    }

    /**
     * Boots service provider, Twig extensions, and alias facade, and injects collectors and resources.
     */
    public function boot()
    {
        // Disabled by config, halt
        if (Config::get('debugbar.enabled') === false) {
            return;
        }

        // Register middleware
        if (Config::get('app.debugAjax', false)) {
            $this->app[HttpKernelContract::class]->pushMiddleware(\Winter\Debugbar\Middleware\InterpretsAjaxExceptions::class);
        }

        // Register custom collectors
        if ($this->app->runningInBackend()) {
            $this->addBackendCollectors();
        } else {
            $this->registerCmsTwigExtensions();
            $this->addFrontendCollectors();
        }

        $this->addGlobalCollectors();
    }

    /**
     * Add globally available collectors
     */
    public function addGlobalCollectors()
    {
        if (Config::get('debugbar.collectors.models', true)) {
            // Disable original models collector because it will be replaced
            Config::set('debugbar.collectors.models', false);

            /** @var \Barryvdh\Debugbar\LaravelDebugbar $debugBar */
            $debugBar = $this->app->make(\Barryvdh\Debugbar\LaravelDebugbar::class);
            $modelsCollector = $this->app->make(ModelsCollector::class);
            $debugBar->addCollector($modelsCollector);
        }
    }

    /**
     * Add collectors used by the frontend only
     */
    public function addFrontendCollectors()
    {
        /** @var \Barryvdh\Debugbar\LaravelDebugbar $debugBar */
        $debugBar = $this->app->make(\Barryvdh\Debugbar\LaravelDebugbar::class);

        if (Config::get('debugbar.collectors.cms', true)) {
            // Disable route collector as the CMS collector presents this info instead
            Config::set('debugbar.collectors.route', false);

            Event::listen('cms.page.beforeDisplay', function (CmsController $controller, $url, ?Page $page) use ($debugBar) {
                if ($page) {
                    $collector = new CmsCollector($controller, $url, $page);
                    if (!$debugBar->hasCollector($collector->getName())) {
                        $debugBar->addCollector($collector);
                    }
                }
            });
        }

        if (Config::get('debugbar.collectors.components', true)) {
            Event::listen('cms.page.initComponents', function (CmsController $controller, ?Page $page, ?Layout $layout) use ($debugBar) {
                if ($page) {
                    $collector = new ComponentsCollector($controller, $page, $layout);
                    if (!$debugBar->hasCollector($collector->getName())) {
                        $debugBar->addCollector($collector);
                    }
                }
            });
        }
    }

    /**
     * Add collectors used by the Backend only
     */
    public function addBackendCollectors()
    {
        /** @var \Barryvdh\Debugbar\LaravelDebugbar $debugBar */
        $debugBar = $this->app->make(\Barryvdh\Debugbar\LaravelDebugbar::class);

        if (Config::get('debugbar.collectors.backend', true)) {
            Event::listen('backend.page.beforeDisplay', function (BackendController $controller, $action, array $params) use ($debugBar) {
                $collector = new BackendCollector($controller, $action, $params);
                if (!$debugBar->hasCollector($collector->getName())) {
                    $debugBar->addCollector($collector);
                }
            });
        }
    }

    /**
     * Registers extensions in the CMS Twig environment
     */
    protected function registerCmsTwigExtensions()
    {
        $profile = new Profile;
        $debugBar = $this->app->make(\Barryvdh\Debugbar\LaravelDebugbar::class);

        Event::listen('cms.page.beforeDisplay', function ($controller, $url, $page) use ($profile, $debugBar) {
            $twig = $controller->getTwig();
            if (!$twig->hasExtension(\Winter\DebugBar\Twig\Extension\Debug::class)) {
                $twig->addExtension(new \Winter\DebugBar\Twig\Extension\Debug($this->app));
                $twig->addExtension(new \Winter\DebugBar\Twig\Extension\Stopwatch($this->app));
            }

            if (!$twig->hasExtension(ProfilerExtension::class)) {
                $twig->addExtension(new ProfilerExtension($profile));
            }
        });

        if (class_exists(\DebugBar\Bridge\NamespacedTwigProfileCollector::class)) {
            $debugBar->addCollector(new \DebugBar\Bridge\NamespacedTwigProfileCollector($profile));
        } else {
            $debugBar->addCollector(new \DebugBar\Bridge\TwigProfileCollector($profile));
        }
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
                'roles' => [UserRole::CODE_DEVELOPER],
            ],
            'winter.debugbar.access_stored_requests' => [
                'tab' => 'winter.debugbar::lang.plugin.name',
                'label' => 'winter.debugbar::lang.plugin.access_stored_requests',
                'roles' => [UserRole::CODE_DEVELOPER],
            ],
        ];
    }
}
