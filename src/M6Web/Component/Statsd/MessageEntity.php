<?php
/**
 * Entity class for a statsd message
 */

namespace M6Web\Component\Statsd;

/**
 * Class MessageEntity
 *
 * @package M6Web\Component\Statsd
 */
class MessageEntity
{
    /**
     * @var string
     */
    protected $node;

    /**
     * @var string
     */
    protected $value;

    /**
     * @var float
     */
    protected $sampleRate;

    /**
     * @var string
     */
    protected $unit;

    /**
     * @param string $node       node
     * @param int    $value      value of the node
     * @param string $unit       units (ms for timer, c for counting ...)
     * @param float  $sampleRate sampling rate
     *
     * @return MessageEntity
     */
    public function __construct($node, $value, $unit = '', $sampleRate = 1.0)
    {
        $this->node  = $node;
        $this->value = $value;
        if (!is_null($sampleRate)) {
            $this->sampleRate = $sampleRate;
        }
        if (!is_null($unit)) {
            $this->unit = $unit;
        }

        $this->checkConstructor();
    }

    /**
     * check if object is correct
     *
     * @throws Exception
     */
    protected function checkConstructor()
    {
        if (!is_string($this->node) or !is_string($this->unit))
        {
            throw new Exception ('node and unit have to be a string');
        }
        if (!is_int($this->value)) {
            throw new Exception('value has to be an integer');
        }
        if (!is_float($this->sampleRate)) {
            throw new Exception ('sampleRate has to be a float');
        }
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
     * format a statsd message
     *
     * @param bool $withSampleRate
     *
     * @return string
     */
    public function getStatsdMessage($withSampleRate = false)
    {
        $message = sprintf('%s:%s|%s', $this->getNode(), $this->getValue(), $this->getUnit());
        if ($withSampleRate) {
            $message .= sprintf('|@%s', $this->getSampleRate());
        }

        return $message;
    }

} 