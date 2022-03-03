# usage

```php
 $client = new \M6Web\Component\Statsd\Client(
                    array(
                        'serv1' => array('address' => 'udp://200.22.143.12'),
                        'serv2' => array('port' => 8125, 'address' => 'udp://200.22.143.12')
                    ),
                    new \M6Web\Component\Statsd\MessageFormatter\InfluxDBStatsDMessageFormatter()
                );

$client->increment('a.graphite.node');
$client->count('a.graphite.node', 5);
$client->timing('another.graphite.node', (float) $timing);
$client->set('a.graphite.set', 12);
$client->gauge('a.gauge.node', 8);

// with tags for influxDb 0.9 : add the tags array at the end of each function
$tags = ['foo' => 'bar', 'site' => 'www', 'country' => 'fr'];
$sampleRate = 1;

$client->increment('a.influxDb.node',  $sampleRate, $tags);
$client->count('a.influxDb.node', 5, $sampleRate, $tags);
$client->timing('another.influxDb.node', (float) $timing, $sampleRate, $tags);
$client->set('a.influxDb.set', 12, $sampleRate, $tags);
$client->gauge('a.influxDb.node', 8, $sampleRate, $tags);

$client->send(); // Send the metrics to the servers
```
