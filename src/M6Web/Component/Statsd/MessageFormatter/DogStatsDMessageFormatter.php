<?php
namespace M6Web\Component\Statsd\MessageFormatter;

use M6Web\Component\Statsd\MessageEntity;

/**
 * Formats a StatsD message using the DogStatsD style:
 * https://docs.datadoghq.com/developers/dogstatsd/datagram_shell/?tab=metrics#tagging
 */
class DogStatsDMessageFormatter implements MessageFormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function format(MessageEntity $message)
    {
        $formatted = sprintf('%s:%s|%s', $message->getNode(), $message->getValue(), $message->getUnit());

        if ($message->useSampleRate()) {
            $formatted .= sprintf('|@%s', $message->getSampleRate());
        }

        if ($message->getTags()) {
            $formatted .= '|#' . $this->getTagsAsString($message);
        }

        return $formatted . "\n";
    }

    /**
     * @param MessageEntity $message
     *
     * @return string
     */
    private function getTagsAsString(MessageEntity $message)
    {
        $tags = array_map(static function($k, $v) {
            return $k . ':' . $v;
        }, array_keys($message->getTags()), $message->getTags());

        return implode(',', $tags);
    }
}
