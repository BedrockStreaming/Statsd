<?php

namespace M6Web\Component\Statsd\Tests\Units;

use M6Web\Component\Statsd;
use mageekguy\atoum;

/**
 * test class for Statsd client
 */
class Client extends atoum\test
{
    /**
     * constructor test
     */
    public function test__construct()
    {
        $this->assert
            ->exception(function () {
                new Statsd\Client([]);
            })
            ->isInstanceOf('\M6Web\Component\Statsd\Exception')
            ->exception(function () {
                new Statsd\Client(
                    [
                        'serv1' => ['port' => 8125],
                    ]
                );
            })
            ->isInstanceOf('\M6Web\Component\Statsd\Exception')
            ->exception(function () {
                new Statsd\Client(
                    [
                        'serv1' => ['address' => 'udp://200.22.143.12'],
                        'serv2' => ['port' => 8125, 'address' => 'udp://200.22.143.12'],
                    ]
                );
            })
            ->isInstanceOf('\M6Web\Component\Statsd\Exception')
            ->exception(function () {
                new Statsd\Client(
                    [
                        'serv1' => ['port' => 8125, 'address' => 'http://200.22.143.12'],
                    ]
                );
            })
            ->isInstanceOf('\M6Web\Component\Statsd\Exception');
    }

    /**
     * send back a server config
     *
     * @return array
     */
    protected function getConf()
    {
        return [
            'serv1' => ['address' => 'udp://200.22.143.xxx', 'port' => '8125'],
            'serv2' => ['address' => 'udp://200.22.143.xxx', 'port' => '8126'],
        ];
    }

    /**
     * test of getServers
     *
     * @return void
     */
    public function testGetServers()
    {
        $this->if($client = new Statsd\Client($this->getConf()))
                ->then()
                ->object($client)->isInstanceOf('\M6Web\Component\Statsd\Client')
                ->array($client->getServers())
                ->isIdenticalTo($this->getConf());
    }

    /**
     * Test get server key
     *
     * @return void
     */
    public function testGetServerKey()
    {
        $this->if($client = new Statsd\Client($this->getConf()))
                ->then()
                ->string($client->getServerKey('foo2'))
                ->isIdenticalTo('serv1')
                ->string($client->getServerKey('foo'))
                ->isIdenticalTo('serv2');
    }

    /**
     * Test clear
     *
     * @return void
     */
    public function testClear()
    {
        $this->if($client = new Statsd\Client($this->getConf()))
            ->then()
            ->object($client->clearToSend());
    }

    /**
     * testTiming
     *
     * @return void
     */
    public function testTiming()
    {
        $message = new Statsd\MessageEntity(
            'service.timer.raoul',
            100,
            'ms',
            1.0
        );

        $this->if($client = new Statsd\Client($this->getConf()))
            ->then()
            ->object($client->timing('service.timer.raoul', 100))
                ->isInstanceOf('\M6Web\Component\Statsd\Client')
            ->and
                ->object($queue = $client->getToSend())
                    ->isInstanceOf('\SplQueue')
                ->integer($queue->count())
                    ->isEqualTo(1)
                ->array($queue->dequeue())
                    ->isEqualTo([
                        'server' => 'serv1', 'message' => $message,
                    ])
        ;
    }

    /**
     * testIncrement
     *
     * @return void
     */
    public function testIncrement()
    {
        $message = new Statsd\MessageEntity(
            'service.raoul',
            1,
            'c',
            1.0
        );

        $this->if($client = new Statsd\Client($this->getConf()))
            ->then()
                ->object($client->increment('service.raoul'))
                    ->isInstanceOf('\M6Web\Component\Statsd\Client')
            ->and
                ->object($queue = $client->getToSend())
                    ->isInstanceOf('\SplQueue')
                ->integer($queue->count())
                    ->isEqualTo(1)
                ->array($queue->dequeue())
                    ->isEqualTo([
                        'server' => 'serv1', 'message' => $message,
                    ])
        ;
    }

    /**
     * testDecrement
     *
     * @return void
     */
    public function testDecrement()
    {
        $message = new Statsd\MessageEntity(
            'service.raoul',
            -1,
            'c',
            1.0
        );

        $this->if($client = new Statsd\Client($this->getConf()))
            ->then()
                ->object($client->decrement('service.raoul'))
                    ->isInstanceOf('\M6Web\Component\Statsd\Client')
            ->and
                ->object($queue = $client->getToSend())
                    ->isInstanceOf('\SplQueue')
                ->integer($queue->count())
                    ->isEqualTo(1)
                ->array($queue->dequeue())
                    ->isEqualTo([
                        'server' => 'serv1', 'message' => $message,
                    ])
        ;
    }

    /**
     * testCount
     *
     * @return void
     */
    public function testCount()
    {
        $message = new Statsd\MessageEntity(
            'service.raoul',
            5,
            'c',
            1.0
        );
        $this->if($client = new Statsd\Client($this->getConf()))
            ->then()
                ->object($client->count('service.raoul', 5))
                ->isInstanceOf('\M6Web\Component\Statsd\Client')
            ->and
                ->object($queue = $client->getToSend())
                    ->isInstanceOf('\SplQueue')
                ->integer($queue->count())
                    ->isEqualTo(1)
                ->array($queue->dequeue())
                    ->isEqualTo([
                        'server' => 'serv1', 'message' => $message,
                    ])
                ->integer($queue->count())
                    ->isEqualTo(0)
        ;
    }

    /**
     * testGauge
     *
     * @return void
     */
    public function testGauge()
    {
        $message = new Statsd\MessageEntity(
            'service.raoul',
            3,
            'g',
            1.0
        );
        $this->if($client = new Statsd\Client($this->getConf()))
            ->then()
            ->object($client->gauge('service.raoul', 3))
                ->isInstanceOf('\M6Web\Component\Statsd\Client')
            ->and
                ->object($queue = $client->getToSend())
                    ->isInstanceOf('\SplQueue')
                ->integer($queue->count())
                    ->isEqualTo(1)
                ->array($queue->dequeue())
                    ->isEqualTo([
                        'server' => 'serv1', 'message' => $message,
                    ])
                ->integer($queue->count())
                    ->isEqualTo(0)
        ;
    }

    /**
     * testSet
     *
     * @return void
     */
    public function testSet()
    {
        $message = new Statsd\MessageEntity(
            'service.raoul',
            9,
            's',
            1.0
        );
        $this->if($client = new Statsd\Client($this->getConf()))
            ->then()

                ->object($client->set('service.raoul', 9))
                    ->isInstanceOf('\M6Web\Component\Statsd\Client')
            ->and
                ->object($queue = $client->getToSend())
                    ->isInstanceOf('\SplQueue')
                ->integer($queue->count())
                    ->isEqualTo(1)
                ->array($queue->dequeue())
                    ->isEqualTo([
                        'server' => 'serv1', 'message' => $message,
                    ])
                ->integer($queue->count())
                    ->isEqualTo(0)
        ;
    }

    /**
     * Test send
     *
     * @return void
     */
    public function testSend()
    {
        $this->if($client = new Statsd\Client($this->getConf()))
            ->then()
            ->object($client->increment('service.raoul')->decrement('service.raoul2'));

        $this->mockClass("\M6Web\Component\Statsd\Client");
        $client = new \mock\M6Web\Component\Statsd\Client($this->getConf());
        $client->getMockController()->writeDatas = function ($server, $datas) {
            return true;
        };
        $this->if($client->increment('service.foo'))
            ->then()
            ->boolean($client->send())
            ->isEqualTo(true)
            ->mock($client)
                ->call('writeDatas')->exactly(1);
        $client = new \mock\M6Web\Component\Statsd\Client($this->getConf());
        $client->getMockController()->writeDatas = function ($server, $datas) {
            return true;
        };
        $this->if($client->increment('service.foo')->increment('service.foo')) // incr x2
            ->then()
            ->boolean($client->send())
            ->mock($client)
                ->call('writeDatas')->exactly(1); // but one call
        $client = new \mock\M6Web\Component\Statsd\Client($this->getConf());
        $client->getMockController()->writeDatas = function ($server, $datas) {
            return true;
        };
        $this->if($client->increment('foo2')->increment('foo')) // incr x2
            ->then()
            ->boolean($client->send())
            ->mock($client)
                ->call('writeDatas')->exactly(2);

        $this->if($client->count('foocount', 5))
            ->then()
            ->boolean($client->send())
            ->mock($client)
                ->call('writeDatas')->exactly(3);
    }
}
