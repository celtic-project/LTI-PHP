<?php

namespace ceLTIc\LTI\ApiHook;

use ceLTIc\LTI\Service;

/**
 * Class to implement context services for a platform via its proprietary API
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ApiContext
{

    /**
     * Context object.
     *
     * @var \ceLTIc\LTI\Context|null $context
     */
    protected $context = null;

    /**
     * Class constructor.
     *
     * @param \ceLTIc\LTI\Context $context
     */
    public function __construct($context)
    {
        $this->context = $context;
    }

    /**
     * Check if the API hook has been configured.
     *
     * @return bool  True if the API hook has been configured
     */
    public function isConfigured()
    {
        return true;
    }

    /**
     * Get course group sets and groups.
     *
     * @return bool  True if the request was successful
     */
    public function getGroups()
    {
        return false;
    }

    /**
     * Get Memberships.
     *
     * @param bool    $withGroups True is group information is to be requested as well
     *
     * @return mixed The array of UserResult objects if successful, otherwise false
     */
    public function getMemberships($withGroups)
    {
        return false;
    }

    /**
     * Get Tool Settings.
     *
     * @param int      $mode       Mode for request (optional, default is current level only)
     * @param bool     $simple     True if all the simple media type is to be used (optional, default is true)
     *
     * @return mixed The array of settings if successful, otherwise false
     */
    public function getToolSettings($mode = Service\ToolSettings::MODE_CURRENT_LEVEL, $simple = true)
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
