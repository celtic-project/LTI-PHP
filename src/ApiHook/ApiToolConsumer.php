<?php

namespace ceLTIc\LTI\ApiHook;

/**
 * Class to implement Tool Consumer services for a tool consumer via its proprietary API
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ApiToolConsumer
{

    /**
     * Tool Consumer object.
     *
     * @var \ceLTIc\LTI\ToolConsumer|null $consumer
     */
    protected $consumer = null;

    /**
     * Class constructor.
     *
     * @param \ceLTIc\LTI\ToolConsmumer $consumer
     */
    public function __construct($consumer)
    {
        $this->consumer = $consumer;
    }

    /**
     * Check if the API hook has been configured.
     */
    public function isConfigured()
    {
        return true;
    }

    /**
     * Get Tool Settings.
     *
     * @param bool     $simple     True if all the simple media type is to be used (optional, default is true)
     *
     * @return mixed The array of settings if successful, otherwise false
     */
    public function getToolSettings($simple = true)
    {
        return false;
    }

    /**
     * Perform a Tool Settings service request.
     *
     * @param array    $settings   An associative array of settings (optional, default is none)
     *
     * @return bool    True if action was successful, otherwise false
     */
    public function setToolSettings($settings = array())
    {
        return false;
    }

}
