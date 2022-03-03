<?php

namespace Winter\DebugBar\Twig\Extension;

use DebugBar\DataFormatter\DataFormatterInterface;
use Twig\Environment as TwigEnvironment;
use Twig\Extension\AbstractExtension as TwigExtension;
use Twig\TwigFunction as TwigSimpleFunction;

/**
 * Dump variables using the DataFormatter
 */
class Dump extends TwigExtension
{
    /**
     * @var \DebugBar\DataFormatter\DataFormatter
     */
    protected $formatter;

    /**
     * Create a new auth extension.
     *
     * @param \DebugBar\DataFormatter\DataFormatterInterface $formatter
     */
    public function __construct(DataFormatterInterface $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'Laravel_Debugbar_Dump';
    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return [
            new TwigSimpleFunction(
                'dump',
                [$this, 'dump'],
                ['is_safe' => ['html'], 'needs_context' => true, 'needs_environment' => true]
            ),
        ];
    }

    /**
     * Based on Twig_Extension_Debug / twig_var_dump
     * (c) 2011 Fabien Potencier
     *
     * @param TwigEnvironment $env
     * @param $context
     *
     * @return string
     */
    public function dump(TwigEnvironment $env, $context)
    {
        $output = '';

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
            $output .= $this->formatter->formatVar($data);
        } else {
            for ($i = 2; $i < $count; $i++) {
                $output .= $this->formatter->formatVar(func_get_arg($i));
            }
        }

        return '<pre>' . $output . '</pre>';
    }
}
