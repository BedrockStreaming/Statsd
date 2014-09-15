<?php
namespace M6Web\Component\Statsd\tests\units;

use
    \M6Web\Component\Statsd,
    \mageekguy\atoum,
    mock\M6Web\Component as mock
;

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
                new Statsd\Client(array());
            })
            ->isInstanceOf('\M6Web\Component\Statsd\Exception')
            ->exception( function () {
                new Statsd\Client(
                    array(
                        'serv1' => array('port' => 8125)
                    )
                );
            })
            ->isInstanceOf('\M6Web\Component\Statsd\Exception')
            ->exception( function () {
                new Statsd\Client(
                    array(
                        'serv1' => array('address' => 'udp://200.22.143.12'),
                        'serv2' => array('port' => 8125, 'address' => 'udp://200.22.143.12')
                    )
                );
            })
            ->isInstanceOf('\M6Web\Component\Statsd\Exception')
            ->exception( function () {
                new Statsd\Client(
                    array(
                        'serv1' => array('port' => 8125, 'address' => 'http://200.22.143.12')
                    )
                );
            })
            ->isInstanceOf('\M6Web\Component\Statsd\Exception');
    }

    /**
     * send back a server config
     * @return array
     */
    protected function getConf()
    {

        return array(
            'serv1' => array('address' => 'udp://200.22.143.xxx', 'port' => '8125'),
            'serv2' => array('address' => 'udp://200.22.143.xxx', 'port' => '8126'),
        );
    }

    /**
     * test of getServers
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
     * @return void
     */
    public function testTiming()
    {
        $this->if($client = new Statsd\Client($this->getConf()))
            ->then()
            ->object($client->timing('service.timer.raoul', 100))
            ->isInstanceOf('\M6Web\Component\Statsd\Client');
    }

    /**
     * testIncrement
     * @return void
     */
    public function testIncrement()
    {
        $this->if($client = new Statsd\Client($this->getConf()))
            ->then()
            ->object($client->increment('service.raoul'))
            ->isInstanceOf('\M6Web\Component\Statsd\Client');
    }

    /**
     * testCount
     *
     * @access public
     * @return void
     */
    public function testCount()
    {
        $this->if($client = new Statsd\Client($this->getConf()))
            ->then()
                ->object($client->count('service.raoul', 5))
                ->isInstanceOf('\M6Web\Component\Statsd\Client');

        $data = $client->getToSend();
        $this->object($data['serv1'][0])
            ->isEqualTo(new Statsd\MessageEntity(
                'service.raoul',
                5,
                'c',
                1.0
            ));
    }

    /**
     * testGauge
     *
     * @access public
     * @return void
     */
    public function testGauge()
    {
        $this->if($client = new Statsd\Client($this->getConf()))
            ->then()
            ->object($client->gauge('service.raoul', 3))
            ->isInstanceOf('\M6Web\Component\Statsd\Client');

        $data = $client->getToSend();
        $this->object($data['serv1'][0])
            ->isEqualTo(new Statsd\MessageEntity(
                'service.raoul',
                3,
                'g',
                1.0
            ));
    }

    /**
     * testSet
     *
     * @access public
     * @return void
     */
    public function testSet()
    {
        $this->if($client = new Statsd\Client($this->getConf()))
            ->then()
            ->object($client->set('service.raoul', 9))
            ->isInstanceOf('\M6Web\Component\Statsd\Client');

        $data = $client->getToSend();
        $this->object($data['serv1'][0])
            ->isEqualTo(new Statsd\MessageEntity(
                'service.raoul',
                9,
                's',
                1.0
            ));
    }

    /**
     * Test send
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
