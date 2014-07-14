<?php
/**
 *
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
    protected $sampleRate = 1;

    /**
     * @var string
     */
    protected $unit = '';

    /**
     * @param string      $node       node
     * @param string      $value      value of the node
     * @param float|null  $sampleRate sampling rate
     * @param string|null $unit       units (ms for timer, c for counting ...)
     *
     * @return MessageEntity
     */
    public function __construct($node, $value, $sampleRate = null, $unit = null)
    {
        $this->node  = $node;
        $this->value = $value;
        if (!is_null($sampleRate)) {
            $this->sampleRate = $sampleRate;
        }
        if (!is_null($unit)) {
            $this->unit = $unit;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getNode()
    {
        return (string) $this->node;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return (string) $this->value;
    }

    /**
     * @return float
     */
    public function getSampleRate()
    {
        return (float) $this->sampleRate;
    }

    /**
     * @return string
     */
    public function getUnit()
    {
        return (string) $this->unit;
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
        $message = $this->getNode().':'.$this->getValue().'|'.$this->getUnit();
        if ($withSampleRate) {
            $message .= '|@'.(string) $this->getSampleRate();
        }

        return $message;
    }

} 