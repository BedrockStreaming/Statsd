<?php
namespace M6Web\Component\Statsd\Tests\Units\MessageFormatter;

use
    \M6Web\Component\Statsd,
    \mageekguy\atoum
    ;

class DogStatsDMessageFormatter extends atoum\test
{
    public function testFormat()
    {
        // not sampled message
        $message = new \mock\M6Web\Component\Statsd\MessageEntity('raoul.node', 1, 'c');
        $this
            ->if($formatter = new Statsd\MessageFormatter\DogStatsDMessageFormatter())
            ->then()
            ->string($formatter->format($message))
            ->isEqualTo("raoul.node:1|c\n");

        // sampled message
        $this->function->mt_rand = 1;
        $this->function->mt_getrandmax = 10;

        $message = new \mock\M6Web\Component\Statsd\MessageEntity('raoul.node', 1, 'c', 0.2);
        $this->calling($message)->useSampleRate = true;
        $this
            ->given($formatter = new Statsd\MessageFormatter\DogStatsDMessageFormatter())
            ->then()
            ->string($formatter->format($message))
            ->isEqualTo("raoul.node:1|c|@0.2\n");

        // with tags
        $message = new \mock\M6Web\Component\Statsd\MessageEntity('raoul.node', 1, 'c', 0.2, ['foo' => 'bar']);
        $this->calling($message)->useSampleRate = true;
        $this
            ->if($formatter = new Statsd\MessageFormatter\DogStatsDMessageFormatter())
            ->then()
            ->string($formatter->format($message))
            ->isEqualTo("raoul.node:1|c|@0.2|#foo:bar\n");
    }
}
