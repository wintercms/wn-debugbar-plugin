<?php

namespace Winter\DebugBar\Twig\Extension;

use Winter\DebugBar\Twig\TokenParser\StopwatchTokenParser;
use Illuminate\Foundation\Application;
use Twig\Extension\AbstractExtension as TwigExtension;

/**
 * Access Laravels auth class in your Twig templates.
 * Based on Symfony\Bridge\Twig\Extension\StopwatchExtension
 */
class Stopwatch extends TwigExtension
{
    /**
     * @var \Barryvdh\Debugbar\LaravelDebugbar
     */
    protected $debugbar;

    /**
     * Create a new auth extension.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    public function __construct(Application $app)
    {
        if ($app->bound('debugbar')) {
            $this->debugbar = $app['debugbar'];
        } else {
            $this->debugbar = null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'stopwatch';
    }

    public function getTokenParsers()
    {
        return [
            /*
             * {% stopwatch foo %}
             * Some stuff which will be recorded on the timeline
             * {% endstopwatch %}
             */
            new StopwatchTokenParser($this->debugbar !== null),
        ];
    }

    public function getDebugbar()
    {
        return $this->debugbar;
    }
}
