<?php

namespace ceLTIc\LTI\ApiHook;

/**
 * Class to implement resource link services for a tool consumer via its proprietary API
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @version  3.1.0
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ApiResourceLink
{

    /**
     * Resource link object.
     *
     * @var \ceLTIc\LTI\ResourceLink|null $resourceLink
     */
    protected $resourceLink = null;

    /**
     * Class constructor.
     *
     * @param \ceLTIc\LTI\ResourceLink $resourceLink
     */
    public function __construct($resourceLink)
    {
        $this->resourceLink = $resourceLink;
    }

    /**
     * Perform an Outcomes service request.
     *
     * @param int $action The action type constant
     * @param Outcome $ltiOutcome Outcome object
     * @param UserResult $userresult UserResult object
     *
     * @return string|bool    Outcome value read or true if the request was successfully processed
     */
    public function doOutcomesService($action, $ltiOutcome, $userresult)
    {
        return false;
    }

    /**
     * Get memberships.
     *
     * @param bool    $withGroups True is group information is to be requested as well
     *
     * @return mixed Array of UserResult objects or False if the request was not successful
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
