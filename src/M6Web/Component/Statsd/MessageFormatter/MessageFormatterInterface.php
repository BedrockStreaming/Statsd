<?php

declare(strict_types=1);

namespace M6Web\Component\Statsd\MessageFormatter;

use M6Web\Component\Statsd\MessageEntity;

/**
 * Interface for formatting StatsD messages for different StatsD server implementations.
 */
interface MessageFormatterInterface
{
    /**
     * @return string
     */
    public function format(MessageEntity $message);
}
