<?php

namespace Winter\DebugBar\Twig\Extension;

use Illuminate\Foundation\Application;
use Twig\Environment as TwigEnvironment;
use Twig\Extension\AbstractExtension as TwigExtension;
use Twig\TwigFunction as TwigSimpleFunction;

/**
 * Access Laravels auth class in your Twig templates.
 */
class Debug extends TwigExtension
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
        return 'Laravel_Debugbar_Debug';
    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return [
            new TwigSimpleFunction(
                'debug',
                [$this, 'debug'],
                ['needs_context' => true, 'needs_environment' => true]
            ),
        ];
    }

    /**
     * Based on Twig_Extension_Debug / twig_var_dump
     * (c) 2011 Fabien Potencier
     *
     * @param TwigEnvironment $env
     * @param $context
     */
    public function debug(TwigEnvironment $env, $context)
    {
        if (!$env->isDebug() || !$this->debugbar) {
            return;
        }

        $count = func_num_args();
        if (2 === $count) {
            $data = [];
            foreach ($context as $key => $value) {
                if (is_object($value)) {
                    if (method_exists($value, 'toArray')) {
                        $data[$key] = $value->toArray();
                    } else {
                        $data[$key] = "Object (" . get_class($value) . ")";
                    }
                } else {
                    $data[$key] = $value;
                }
            }
            $this->debugbar->addMessage($data);
        } else {
            for ($i = 2; $i < $count; $i++) {
                $this->debugbar->addMessage(func_get_arg($i));
            }
        }

        return;
    }
}
