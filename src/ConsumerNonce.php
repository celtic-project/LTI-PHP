<?php

namespace ceLTIc\LTI;

/**
 * Class to represent a tool consumer nonce
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ConsumerNonce
{

    /**
     * Maximum age nonce values will be retained for (in minutes).
     */
    const MAX_NONCE_AGE = 30;  // in minutes

    /**
     * Maximum length which can be stored.
     *
     * Characters are removed from the beginning of the value when too long.
     *
     * @var int $maximumLength
     */
    public static $maximumLength = 50;

    /**
     * Timestamp for when the nonce value expires.
     *
     * @var int|null $expires
     */
    public $expires = null;

    /**
     * Tool Consumer to which this nonce applies.
     *
     * @var ToolConsumer|null $consumer
     */
    private $consumer = null;

    /**
     * Nonce value.
     *
     * @var string|null $value
     */
    private $value = null;

    /**
     * Class constructor.
     *
     * @param ToolConsumer      $consumer Consumer object
     * @param string            $value    Nonce value (optional, default is null)
     */
    public function __construct($consumer, $value = null)
    {
        $this->consumer = $consumer;
        $this->value = substr($value, -self::$maximumLength);
        $this->expires = time() + (self::MAX_NONCE_AGE * 60);
    }

    /**
     * Load a nonce value from the database.
     *
     * @return bool    True if the nonce value was successfully loaded
     */
    public function load()
    {
        return $this->consumer->getDataConnector()->loadConsumerNonce($this);
    }

    /**
     * Save a nonce value in the database.
     *
     * @return bool    True if the nonce value was successfully saved
     */
    public function save()
    {
        return $this->consumer->getDataConnector()->saveConsumerNonce($this);
    }

    /**
     * Get tool consumer.
     *
     * @return ToolConsumer Consumer for this nonce
     */
    public function getConsumer()
    {
        return $this->consumer;
    }

    /**
     * Get outcome value.
     *
     * @return string Outcome value
     */
    public function getValue()
    {
        return $this->value;
    }

}
