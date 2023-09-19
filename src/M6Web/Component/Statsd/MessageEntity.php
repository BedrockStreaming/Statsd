<?php

declare(strict_types=1);

namespace M6Web\Component\Statsd;

/**
 * Class MessageEntity
 */
class MessageEntity
{
    /** @var string */
    protected $node;

    /** @var string */
    protected $value;

    /** @var float */
    protected $sampleRate;

    /** @var string */
    protected $unit;

    /** @var array */
    protected $tags = [];

    /**
     * @param string        $node       node
     * @param int|string    $value      value of the node
     * @param string        $unit       units (ms for timer, c for counting ...)
     * @param float         $sampleRate sampling rate
     * @param array         $tags       Tags key => value for influxDb
     *
     * @return MessageEntity
     */
    public function __construct($node, $value, $unit = '', $sampleRate = 1.0, $tags = [])
    {
        $this->node = $node;
        $this->value = $value;
        if (!is_null($sampleRate)) {
            $this->sampleRate = $sampleRate;
        }
        if (!is_null($unit)) {
            $this->unit = $unit;
        }

        $this->tags = $tags ?: [];

        $this->checkConstructor();
    }

    /**
     * check if object is correct
     *
     * @throws Exception
     */
    protected function checkConstructor()
    {
        if (!is_string($this->node) or !is_string($this->unit)) {
            throw new Exception('node and unit have to be a string');
        }

        if (!is_int($this->value) || !is_string($this->value)) {
            throw new Exception('value has to be an integer or a string');
        }

        if (!is_float($this->sampleRate) or ($this->sampleRate <= 0)) {
            throw new Exception('sampleRate has to be a non-zero posivite float');
        }

        if (!is_array($this->tags)) {
            throw new Exception('Tags has to be an array');
        }
    }

    /**
     * Should we use sampleRate in message ?
     *
     * @return bool
     */
    public function useSampleRate()
    {
        if (($this->getSampleRate() < 1) && (mt_rand() / mt_getrandmax()) <= $this->getSampleRate()) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return float
     */
    public function getSampleRate()
    {
        return $this->sampleRate;
    }

    /**
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @return string Tags formatted for sending
     *                ex: "server=5,country=fr"
     */
    private function getTagsAsString()
    {
        $tags = array_map(function ($k, $v) {
            return $k.'='.$v;
        }, array_keys($this->tags), $this->tags);

        return implode(',', $tags);
    }

    /**
     * @return string the node with tags as string
     *                ex : node "foo.bar" and tag ["country" => "fr" ] Into "foo.bar,country=fr"
     */
    private function getFullNode()
    {
        if ($this->tags) {
            return $this->getNode().','.$this->getTagsAsString();
        }

        return $this->getNode();
    }

    /**
     * format a statsd message
     *
     * @return string
     *
     * @deprecated
     */
    public function getStatsdMessage()
    {
        trigger_error(
            sprintf(
                '%s is deprecated and will be removed in the next major version. '.
                'Update your code to use %s::%s.',
                __METHOD__,
                'M6Web\Component\Statsd\MessageFormatter\MessageFormatterInterface',
                'format'
            ),
            E_USER_DEPRECATED
        );

        $message = sprintf('%s:%s|%s', $this->getFullNode(), $this->getValue(), $this->getUnit());
        if ($this->useSampleRate()) {
            $message .= sprintf('|@%s', $this->getSampleRate());
        }

        return $message;
    }
}
