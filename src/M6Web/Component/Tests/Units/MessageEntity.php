<?php
namespace M6Web\Component\Statsd\tests\units;

use
    \M6Web\Component\Statsd,
    \mageekguy\atoum
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
            'raoul.node', 1, 0.2, 'c'))
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
        $this->if($messageEntity = new Statsd\MessageEntity(
            'raoul.node', 1, 0.2, 'c'))
            ->then()
                ->string($messageEntity->getStatsdMessage())
                ->isEqualTo('raoul.node:1|c')
            ->and()
                ->string($messageEntity->getStatsdMessage(true))
                ->isEqualTo('raoul.node:1|c|@0.2');

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

        ;

    }
}