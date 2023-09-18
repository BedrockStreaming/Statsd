<?php

declare(strict_types=1);
/**
 * class sending udp packets to statsd
 */

namespace M6Web\Component\Statsd;

use M6Web\Component\Statsd\MessageFormatter\InfluxDBStatsDMessageFormatter;
use M6Web\Component\Statsd\MessageFormatter\MessageFormatterInterface;

/**
 * client Statsd
 */
class Client
{
    /**
     * commands to send
     *
     * @var \SplQueue
     */
    protected $toSend;

    /**
     * statsd servers
     *
     * @var array
     */
    protected $servers = [];

    /**
     * number of servers
     *
     * @var int
     */
    private $nbServers = 0;

    /**
     * list of server keys
     *
     * @var array
     */
    private $serverKeys = [];

    /** @var MessageFormatterInterface */
    private $messageFormatter;

    /**
     * contructeur
     *
     * @param array $servers les serveurs
     */
    public function __construct(array $servers, MessageFormatterInterface $messageFormatter = null)
    {
        $this->init($servers);
        $this->initQueue();
        $this->messageFormatter = $messageFormatter ?: new InfluxDBStatsDMessageFormatter();
    }

    /**
     * set the params from config
     *
     * @param array $servers les serveurs
     *
     * @throws Exception
     *
     * @return void
     */
    protected function init(array $servers)
    {
        if (0 === count($servers)) {
            throw new Exception('dont have any servers ?');
        }
        // check server
        foreach ($servers as $serName => $server) {
            // backward compatibility
            if (!isset($server['address']) && isset($server['adress'])) {
                $server['address'] = $server['adress'];
            }

            if (!isset($server['address']) or !isset($server['port'])) {
                throw new Exception($serName.' : no address or port in the configuration ?!');
            }
            if (strpos($server['address'], 'udp://') !== 0) {
                throw new Exception($serName.' : address should begin with udp:// ?!');
            }
            // TODO : address format ?
        }
        $this->servers = $servers;
        $this->nbServers = count($servers);
        $this->serverKeys = array_keys($servers);
    }

    /**
     * Init spl queue
     */
    protected function initQueue()
    {
        $this->toSend = new \SplQueue();
    }

    /**
     * get servers
     *
     * @return array
     */
    public function getServers()
    {
        return $this->servers;
    }

    /**
     * get commands to send
     *
     * @return array
     */
    public function getToSend()
    {
        return $this->toSend;
    }

    /**
     * erase commands
     *
     * @return Client
     */
    public function clearToSend()
    {
        $this->initQueue();

        return $this;
    }

    /**
     * find a server according to the key
     *
     * @param string $stats service.m6replay.raoul
     *
     * @return string
     */
    public function getServerKey($stats)
    {
        return $this->serverKeys[(int) (crc32($stats) % $this->nbServers)];
    }

    /**
     * addToSend
     *
     * @param string $stats      grahite node
     * @param string $value      value
     * @param float  $sampleRate sampling rate
     * @param string $unit       unit
     * @param array  $tags       Tags key => value for influxDb
     *
     * @return Client
     */
    protected function addToSend($stats, $value, $sampleRate, $unit, $tags)
    {
        $message = new MessageEntity(
            (string) $stats, $value, (string) $unit, (float) $sampleRate, $tags
        );

        $queue = [
            'server' => $this->getServerKey($stats), 'message' => $message,
        ];

        $this->toSend->enqueue($queue);
    }

    /**
     * Build data to send
     *
     * @return array
     */
    protected function buildSampledData()
    {
        $sampledData = [];

        foreach ($this->getToSend() as $metric) {
            $server = $metric['server'];
            /** @var MessageEntity $message */
            $message = $metric['message'];
            $sampledData[$server][] = $this->messageFormatter->format($message);
        }

        return $sampledData;
    }

    /**
     * Log timing information
     *
     * @param string    $stats      the metric to in log timing info for
     * @param int       $time       The ellapsed time (ms) to log
     * @param float|int $sampleRate the rate (0-1) for sampling
     * @param array     $tags       Tags key => value for influxDb
     *
     * @return Client
     */
    public function timing($stats, $time, $sampleRate = 1.0, $tags = [])
    {
        $this->addToSend($stats, $time, $sampleRate, 'ms', $tags);

        return $this;
    }

    /**
     * Increments one or more stats counters
     *
     * @param string $stats      the metric(s) to increment
     * @param float  $sampleRate SamplingRate
     * @param array  $tags       Tags key => value for influxDb
     *
     * @internal param $ float|1 $sampleRate the rate (0-1) for sampling
     *
     * @return Client
     */
    public function increment($stats, $sampleRate = 1.0, $tags = [])
    {
        $this->count($stats, '1', $sampleRate, $tags);

        return $this;
    }

    /**
     * Decrements one or more stats counters.
     *
     * @param string    $stats      the metric(s) to decrement
     * @param float|int $sampleRate the rate (0-1) for sampling
     * @param array     $tags       Tags key => value for influxDb
     *
     * @return Client
     */
    public function decrement($stats, $sampleRate = 1, $tags = [])
    {
        $this->count($stats, '-1', $sampleRate, $tags);

        return $this;
    }

    /**
     * Count is the default statsd method for counting
     *
     * @param string    $stats      The metric(s) to count
     * @param int       $value      The count value
     * @param float|int $sampleRate the rate (0-1) for sampling
     * @param array     $tags       Tags key => value for influxDb
     *
     * @return Client
     */
    public function count($stats, $value, $sampleRate = 1, $tags = [])
    {
        $this->addToSend($stats, $value, $sampleRate, 'c', $tags);

        return $this;
    }

    /**
     * gauge
     *
     * @param string    $stats      The metric(s) to count
     * @param int       $value      The value
     * @param float|int $sampleRate the rate (0-1) for sampling
     * @param array     $tags       Tags key => value for influxDb
     *
     * @return Client
     */
    public function gauge($stats, $value, $sampleRate = 1, $tags = [])
    {
        $this->addToSend($stats, $value, $sampleRate, 'g', $tags);

        return $this;
    }

    /**
     * set
     *
     * @param string    $stats      The metric(s) to count
     * @param int       $value      The value
     * @param float|int $sampleRate the rate (0-1) for sampling
     * @param array     $tags       Tags key => value for influxDb
     *
     * @return Client
     */
    public function set($stats, $value, $sampleRate = 1, $tags = [])
    {
        $this->addToSend($stats, $value, $sampleRate, 's', $tags);

        return $this;
    }

    /**
     * Squirt the metrics over UDP
     * return always true
     * clear the ToSend datas weanwhile
     *
     * @return bool
     **/
    public function send()
    {
        if ($this->toSend->isEmpty()) {
            return true;
        }

        $sampledData = $this->buildSampledData();

        foreach ($sampledData as $server => $data) {
            $packets = array_chunk($data, 30);

            foreach ($packets as $packet) {
                $this->writeDatas($server, $packet);
            }
        }
        $this->clearToSend();

        return true;
    }

    /**
     * send datas to servers
     *
     * @param string $server server key
     * @param array  $datas  array de data à env
     *
     * @throws Exception
     *
     * @return bool
     */
    public function writeDatas($server, $datas)
    {
        if (!isset($this->getServers()[$server])) {
            throw new Exception($server.' undefined in the configuration');
        }
        $s = $this->getServers()[$server];
        $fp = @fsockopen($s['address'], $s['port']);
        if ($fp !== false) {
            foreach ($datas as $value) {
                // write packets
                if (!@fwrite($fp, $value)) {
                    return false;
                }
            }
            // close conn
            if (!fclose($fp)) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }
}
