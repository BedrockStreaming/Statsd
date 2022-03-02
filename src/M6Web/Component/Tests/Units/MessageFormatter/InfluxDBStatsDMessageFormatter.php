<?php

namespace M6Web\Component\Statsd\Tests\Units\MessageFormatter;

use M6Web\Component\Statsd;
use mageekguy\atoum
    ;

class InfluxDBStatsDMessageFormatter extends atoum\test
{
    public function testFormat()
    {
        // not sampled message
        $message = new \mock\M6Web\Component\Statsd\MessageEntity('raoul.node', 1, 'c');
        $this
            ->if($formatter = new Statsd\MessageFormatter\InfluxDBStatsDMessageFormatter())
            ->then()
            ->string($formatter->format($message))
            ->isEqualTo('raoul.node:1|c');

        // sampled message
        $message = new \mock\M6Web\Component\Statsd\MessageEntity('raoul.node', 1, 'c', 0.2);
        $this->calling($message)->useSampleRate = true;
        $this
            ->given($formatter = new Statsd\MessageFormatter\InfluxDBStatsDMessageFormatter())
            ->then()
            ->string($formatter->format($message))
            ->isEqualTo('raoul.node:1|c|@0.2');

        // with tags
        $message = new \mock\M6Web\Component\Statsd\MessageEntity('raoul.node', 1, 'c', 0.2, ['foo' => 'bar']);
        $this->calling($message)->useSampleRate = true;
        $this
            ->if($formatter = new Statsd\MessageFormatter\InfluxDBStatsDMessageFormatter())
            ->then()
            ->string($formatter->format($message))
            ->isEqualTo('raoul.node,foo=bar:1|c|@0.2');
    }
}
