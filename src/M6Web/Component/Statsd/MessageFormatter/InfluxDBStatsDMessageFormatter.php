<?php

namespace M6Web\Component\Statsd\MessageFormatter;

use M6Web\Component\Statsd\MessageEntity;

/**
 * Formats a StatsD message using the InfluxDB StatsD style:
 * https://www.influxdata.com/blog/getting-started-with-sending-statsd-metrics-to-telegraf-influxdb/#introducing-influx-statsd
 */
class InfluxDBStatsDMessageFormatter implements MessageFormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function format(MessageEntity $message)
    {
        $node = $message->getNode();

        if ($message->getTags()) {
            $node .= ','.$this->getTagsAsString($message);
        }

        $formatted = sprintf('%s:%s|%s', $node, $message->getValue(), $message->getUnit());

        if ($message->useSampleRate()) {
            $formatted .= sprintf('|@%s', $message->getSampleRate());
        }

        return $formatted;
    }

    /**
     * @return string
     */
    private function getTagsAsString(MessageEntity $message)
    {
        $tags = array_map(static function ($k, $v) {
            return $k.'='.$v;
        }, array_keys($message->getTags()), $message->getTags());

        return implode(',', $tags);
    }
}
