<?php

namespace Winter\DebugBar\Twig\TokenParser;

use Winter\DebugBar\Twig\Node\StopwatchNode;
use Twig\Token as TwigToken;
use Twig\TokenParser\AbstractTokenParser as TwigTokenParser;

/**
 * Token Parser for the stopwatch tag. Based on Symfony\Bridge\Twig\TokenParser\StopwatchTokenParser;
 *
 * @author Wouter J <wouter@wouterj.nl>
 */
class StopwatchTokenParser extends TwigTokenParser
{
    protected $debugbarAvailable;

    public function __construct($debugbarAvailable)
    {
        $this->debugbarAvailable = $debugbarAvailable;
    }

    public function parse(TwigToken $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        // {% stopwatch 'bar' %}
        $name = $this->parser->getExpressionParser()->parseExpression();

        $stream->expect(TwigToken::BLOCK_END_TYPE);

        // {% endstopwatch %}
        $body = $this->parser->subparse([$this, 'decideStopwatchEnd'], true);
        $stream->expect(TwigToken::BLOCK_END_TYPE);

        if ($this->debugbarAvailable) {
            return new StopwatchNode(
                $name,
                $body,
                new \Twig\Node\Expression\AssignName($this->parser->getVarName(), $token->getLine()),
                $lineno,
                $this->getTag()
            );
        }

        return $body;
    }

    public function getTag()
    {
        return 'stopwatch';
    }

    public function decideStopwatchEnd(TwigToken $token)
    {
        return $token->test('endstopwatch');
    }
}
