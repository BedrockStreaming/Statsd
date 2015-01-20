# usage

```php
 $client = new \M6Web\Component\Statsd\Client(
                    array(
                        'serv1' => array('address' => 'udp://200.22.143.12'),
                        'serv2' => array('port' => 8125, 'address' => 'udp://200.22.143.12')
                    )
                );

$client->increment('a.graphite.node');
$client->count('a.graphite.node', 5);
$client->timing('another.graphite.node', (float) $timing);
$client->set('a.graphite.set', 12);
$client->gauge('a.gauge.node', 8);

$client->send(); // Send the metrics to the servers
```
