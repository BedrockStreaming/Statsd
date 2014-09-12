<?php
/**
 * class sending udp packets to statsd
 *
 */
namespace M6Web\Component\Statsd;

/**
 * client Statsd
 */
class Client
{
    /**
     * commands to send
     * @var \SplQueue
     */
    protected $toSend;

    /**
     * statsd servers
     * @var array
     */
    protected $servers = array();

    /**
     * number of servers
     * @var integer
     */
    private $nbServers = 0;

    /**
     * list of server keys
     * @var array
     */
    private $serverKeys = array();

    /**
     * contructeur
     * @param array $servers les serveurs
     */
    public function __construct(array $servers)
    {
        $this->init($servers);
        $this->initQueue();
    }

    /**
     * set the params from config
     * @param array $servers les serveurs
     *
     * @throws Exception
     *
     * @return void
     */
    protected function init(array $servers)
    {
        if (0 === count($servers)) {
            throw new Exception("dont have any servers ?");
        }
        // check server
        foreach ($servers as $serName => $server) {
            // backward compatibility
            if (!isset($server['address']) && isset($server['adress'])) {
                $server['address'] = $server['adress'];
            }

            if (!isset($server['address']) or !isset($server['port'])) {
                throw new Exception($serName." : no address or port in the configuration ?!");
            }
            if (strpos($server['address'], 'udp://') !== 0) {
                throw new Exception($serName." : address should begin with udp:// ?!");
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
        $this->toSend->setIteratorMode(\SplQueue::IT_MODE_DELETE);
    }

    /**
     * get servers
     * @return array
     */
    public function getServers()
    {
        return $this->servers;
    }

    /**
     * get commands to send
     * @return array
     */
    public function getToSend()
    {
        return $this->toSend;
    }

    /**
     * erase commands
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
     * @param string $stats      grahite node
     * @param string $v          value
     * @param float  $sampleRate sampling rate
     * @param string $unit       unit
     *
     * @return Client
     */
    protected function addToSend($stats, $v, $sampleRate, $unit)
    {
        $queue = [
            'server'       => $this->getServerKey($stats)
            , 'stats'      => $stats
            , 'value'      => $v
            , 'sampleRate' => (float) $sampleRate
            , 'unit'       => $unit
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
            if ($metric['sampleRate'] < 1) {
                if ((mt_rand() / mt_getrandmax()) <= $metric['sampleRate']) {
                    $sampledData[$server][] = $metric['stats'].':'.$metric['value'].'|'.$metric['unit'].'|@'.$metric[2];
                }
            } else {
                $sampledData[$server][] = $metric['stats'].':'.$metric['value'].'|'.$metric['unit'];
            }
        }

        return $sampledData;
    }

    /**
     * Log timing information
     *
     * @param string    $stats      The metric to in log timing info for.
     * @param float     $time       The ellapsed time (ms) to log
     * @param float|int $sampleRate the rate (0-1) for sampling.
     *
     * @return Client
     */
    public function timing($stats, $time, $sampleRate = 1)
    {
        $this->addToSend($stats, $time, $sampleRate, 'ms');

        return $this;
    }


    /**
     * Increments one or more stats counters
     *
     * @param string $stats      The metric(s) to increment.
     * @param int    $sampleRate SamplingRate
     *
     * @internal param $ float|1 $sampleRate the rate (0-1) for sampling.
     *
     * @return Client
     */
    public function increment($stats, $sampleRate = 1)
    {
        $this->count($stats, '1', $sampleRate);

        return $this;
    }


    /**
     * Decrements one or more stats counters.
     *
     * @param string    $stats      The metric(s) to decrement.
     * @param float|int $sampleRate the rate (0-1) for sampling.
     *
     * @return Client
     */
    public function decrement($stats, $sampleRate = 1)
    {
        $this->count($stats, '-1', $sampleRate);

        return $this;
    }

    /**
     * Count is the default statsd method for counting
     *
     * @param string    $stats      The metric(s) to count
     * @param int       $value      The count value
     * @param float|int $sampleRate the rate (0-1) for sampling.
     *
     * @access public
     *
     * @return Client
     */
    public function count($stats, $value, $sampleRate = 1)
    {
        $this->addToSend($stats, (string) $value, $sampleRate, 'c');

        return $this;
    }

    /**
     * gauge
     *
     * @param string    $stats      The metric(s) to count
     * @param int       $value      The value
     * @param float|int $sampleRate the rate (0-1) for sampling.
     *
     * @access public
     * @return Client
     */
    public function gauge($stats, $value, $sampleRate = 1)
    {
        $this->addToSend($stats, (string) $value, $sampleRate, 'g');

        return $this;
    }

    /**
     * set
     *
     * @param string    $stats      The metric(s) to count
     * @param int       $value      The value
     * @param float|int $sampleRate the rate (0-1) for sampling.
     *
     * @access public
     * @return Client
     */
    public function set($stats, $value, $sampleRate = 1)
    {
        $this->addToSend($stats, (string) $value, $sampleRate, 's');

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

        // for any server
        foreach ($sampledData as $server => $data) {
            $packets = array_chunk($data, 30);

            foreach ($packets as $packet) {
                $this->writeDatas($server, $packet);
            }
        }

        return true;
    }

    /**
     * send datas to servers
     *
     * @param string $server server key
     * @param array  $datas  array de data à env
     *
     * @throws Exception
     * @return bool
     */
    public function writeDatas($server, $datas)
    {
        if (!isset($this->getServers()[$server])) {
            throw new Exception($server." undefined in the configuration");
        }
        $s = $this->getServers()[$server];
        $fp = fsockopen($s['address'], $s['port']);
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
