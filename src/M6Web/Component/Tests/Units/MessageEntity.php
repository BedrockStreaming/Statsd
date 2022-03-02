<?php

namespace M6Web\Component\Statsd\Tests\Units;

use M6Web\Component\Statsd;
use mageekguy\atoum
;

/**
 * test class for Statsd client
 */
class MessageEntity extends atoum\test
{
    /**
     * getter test
     */
    public function testGet()
    {
        $this->if($messageEntity = new Statsd\MessageEntity(
            'raoul.node', 1, 'c', 0.2))
            ->then()
                ->string($messageEntity->getNode())
                ->isEqualTo('raoul.node')
            ->and()
                ->integer($messageEntity->getValue())
                ->isEqualto(1)
            ->and()
                ->float($messageEntity->getSampleRate())
                ->isIdenticalTo(0.2)
            ->and()
                ->string($messageEntity->getUnit())
                ->isIdenticalTo('c');
    }

    /**
     * test message formating
     */
    public function testgetStatsdMessage()
    {
        $expectedDeprecation =
            'M6Web\Component\Statsd\MessageEntity::getStatsdMessage is deprecated and will be '.
            'removed in the next major version. Update your code to use '.
            'M6Web\Component\Statsd\MessageFormatter\MessageFormatterInterface::format.';

        // not sampled message
        $this
            ->when(function () {
                $this->if($messageEntity = new Statsd\MessageEntity(
                    'raoul.node', 1, 'c'))
                    ->then()
                    ->string($messageEntity->getStatsdMessage())
                    ->isEqualTo('raoul.node:1|c')
                ;
            })
            ->error()
            ->withMessage($expectedDeprecation)
            ->exists();

        // sampled message
        $this->function->mt_rand = function () { return 1; };
        $this->function->mt_getrandmax = function () { return 10; };

        $this
            ->when(function () {
                $this->if($messageEntity = new Statsd\MessageEntity(
                    'raoul.node', 1, 'c', 0.2))
                    ->then()
                        ->string($messageEntity->getStatsdMessage())
                            ->isEqualTo('raoul.node:1|c|@0.2')
                ;
            })
            ->error()
            ->withMessage($expectedDeprecation)
            ->exists();

        // with tags
        $this
            ->when(function () {
                $this->if($messageEntity = new Statsd\MessageEntity(
                    'raoul.node', 1, 'c', 0.2, ['foo' => 'bar']))
                    ->then()
                    ->string($messageEntity->getStatsdMessage())
                    ->isEqualTo('raoul.node,foo=bar:1|c|@0.2')
                ;
            })
            ->error()
            ->withMessage($expectedDeprecation)
            ->exists();
    }

    public function testErrorConstructorStatsdMessage()
    {
        $this->exception(
            function () {
                new Statsd\MessageEntity('raoul.node', [1], 0.2, 'c');
            })
            ->isInstanceOf('\M6Web\Component\Statsd\Exception')
            ->exception(
                function () {
                    new Statsd\MessageEntity('raoul.node', 1, 1, 'c');
                })
            ->isInstanceOf('\M6Web\Component\Statsd\Exception')
            ->exception(
                function () {
                    new Statsd\MessageEntity('raoul.node', 1, 0, 'c');
                })
            ->isInstanceOf('\M6Web\Component\Statsd\Exception')
            ->exception(
                function () {
                    new Statsd\MessageEntity('raoul.node', 1, -1, 'c');
                })
            ->isInstanceOf('\M6Web\Component\Statsd\Exception');

        $this->exception(
            function () {
                new Statsd\MessageEntity('raoul.node', [1]);
            })
            ->isInstanceOf('\M6Web\Component\Statsd\Exception');

        $this->exception(
            function () {
                new Statsd\MessageEntity('raoul.node', 1, 'c', 1, 'stringTag');
            }
        )->isInstanceOf('\M6Web\Component\Statsd\Exception');
    }
}
