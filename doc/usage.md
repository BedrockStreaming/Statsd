# usage

```php
 $client = new Statsd\Client(
                    array(
                        'serv1' => array('address' => 'udp://200.22.143.12'),
                        'serv2' => array('port' => 8125, 'address' => 'udp://200.22.143.12')
                    )
                );

$client->increment('a.graphite.node');
$client->timing('another.graphite.node', (float) $timing);
```