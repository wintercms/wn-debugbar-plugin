<?php namespace Winter\Debugbar;

use App;
use Event;
use Config;
use Backend\Models\UserRole;
use Backend\Classes\Controller as BackendController;
use Barryvdh\Debugbar\Facades\Debugbar;
use Cms\Classes\Controller as CmsController;
use Cms\Classes\Layout;
use Cms\Classes\Page;
use System\Classes\PluginBase;
use System\Classes\CombineAssets;
use Winter\Debugbar\Collectors\BackendCollector;
use Winter\Debugbar\Collectors\CmsCollector;
use Winter\Debugbar\Collectors\ComponentsCollector;
use Winter\Debugbar\Collectors\ModelsCollector;
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
            'name'        => 'winter.debugbar::lang.plugin.name',
            'description' => 'winter.debugbar::lang.plugin.description',
            'author'      => 'Winter CMS',
            'icon'        => 'icon-bug',
            'homepage'    => 'https://github.com/wintercms/wn-debugbar-plugin',
            'replaces'    => ['RainLab.Debugbar' => '<= 3.1.1'],
        ];
    }

    /**
     * Boots service provider, Twig extensions, and alias facade, and injects collectors and resources.
     */
    public function boot()
    {
        // Configure the debugbar
        Config::set('debugbar', Config::get('winter.debugbar::config'));

        // Service provider
        App::register(\Winter\Debugbar\Classes\ServiceProvider::class);

        // Register alias
        $alias = AliasLoader::getInstance();
        $alias->alias('Debugbar', \Barryvdh\Debugbar\Facades\Debugbar::class);

        // Register middleware
        if (Config::get('app.debugAjax', false)) {
            $this->app[HttpKernelContract::class]->pushMiddleware(\Winter\Debugbar\Middleware\InterpretsAjaxExceptions::class);
        }

        $this->registerResourceInjection();

        if (App::runningInBackend()) {
            $this->addBackendCollectors();
        } else {
            $this->registerCmsTwigExtensions();
            $this->addFrontendCollectors();
        }

        $this->addGlobalCollectors();
    }

    /**
     * Registers assets bundles
     */
    public function register()
    {
        CombineAssets::registerCallback(function ($combiner) {
            $combiner->registerBundle('$/winter/debugbar/assets/less/debugbar.less');
        });
    }

    /**
     * Add globally available collectors
     */
    public function addGlobalCollectors()
    {
        /** @var \Barryvdh\Debugbar\LaravelDebugbar $debugBar */
        $debugBar = $this->app->make(\Barryvdh\Debugbar\LaravelDebugbar::class);
        $modelsCollector = $this->app->make(ModelsCollector::class);
        $debugBar->addCollector($modelsCollector);
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
            if (!$twig->hasExtension(\Barryvdh\Debugbar\Twig\Extension\Debug::class)) {
                $twig->addExtension(new \Barryvdh\Debugbar\Twig\Extension\Debug($this->app));
                $twig->addExtension(new \Barryvdh\Debugbar\Twig\Extension\Stopwatch($this->app));
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
     * Adds styling to the page
     */
    protected function registerResourceInjection()
    {
        // Add styling
        $addResources = function ($controller) {
            $renderer = Debugbar::getJavascriptRenderer();
            [$css, $js] = $renderer->getAssets();

            // Switch base Debugbar CSS out with our own
            foreach ($css as $key => $file) {
                if (str_ends_with($file, 'vendor/barryvdh/laravel-debugbar/src/Resources/laravel-debugbar.css')) {
                    $css[$key] = __DIR__ . '/assets/css/debugbar.css';
                    continue;
                }
                if (str_contains($file, 'vendor/barryvdh/laravel-debugbar/src/Resources/laravel-debugbar-dark-mode')) {
                    unset($css[$key]);
                    continue;
                }
            }

            $debugBar = $this->app->make(\Barryvdh\Debugbar\LaravelDebugbar::class);
            if ($debugBar->isEnabled()) {
                $controller->addCss($css);
                $controller->addJs($js);
            }
        };

        Event::listen('backend.page.beforeDisplay', $addResources, PHP_INT_MAX);
        Event::listen('cms.page.beforeDisplay', $addResources, PHP_INT_MAX);

        Event::listen('cms.page.postprocess', function ($controller, $url, $page, $dataHolder) {
            $renderer = Debugbar::getJavascriptRenderer();
            $widget = $renderer->render();
            $pos = strripos($dataHolder->content, '</body>');

            if ($pos !== false) {
                $dataHolder->content = substr($dataHolder->content, 0, $pos) . $widget . substr($dataHolder->content, $pos);
            } else {
                $dataHolder->content .= $widget;
            }
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
