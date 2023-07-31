<?php
declare(strict_types=1);

namespace ceLTIc\LTI\ApiHook;

use ceLTIc\LTI\ResourceLink;
use ceLTIc\LTI\UserResult;
use ceLTIc\LTI\Outcome;
use ceLTIc\LTI\Enum\ServiceAction;
use ceLTIc\LTI\Enum\ToolSettingsMode;

/**
 * Class to implement resource link services for a platform via its proprietary API
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ApiResourceLink
{

    /**
     * Resource link object.
     *
     * @var ResourceLink $resourceLink
     */
    protected ResourceLink $resourceLink;

    /**
     * Class constructor.
     *
     * @param ResourceLink $resourceLink
     */
    public function __construct(ResourceLink $resourceLink)
    {
        $this->resourceLink = $resourceLink;
    }

    /**
     * Check if the API hook has been configured.
     *
     * @return bool  True if the API hook has been configured
     */
    public function isConfigured(): bool
    {
        return true;
    }

    /**
     * Perform an Outcomes service request.
     *
     * @param ServiceAction $action   The action type constant
     * @param Outcome $ltiOutcome     Outcome object
     * @param UserResult $userResult  UserResult object
     *
     * @return string|bool  Outcome value read or true if the request was successfully processed
     */
    public function doOutcomesService(ServiceAction $action, Outcome $ltiOutcome, UserResult $userResult): bool
    {
        return false;
    }

    /**
     * Get memberships.
     *
     * @param bool $withGroups  True is group information is to be requested as well
     *
     * @return array|bool  Array of UserResult objects or false if the request was not successful
     */
    public function getMemberships(bool $withGroups): array|bool
    {
        return false;
    }

    /**
     * Get Tool Settings.
     *
     * @param ToolSettingsMode|null $mode  Mode for request (optional, default is current level only)
     * @param bool $simple                 True if all the simple media type is to be used (optional, default is true)
     *
     * @return array|bool  The array of settings if successful, otherwise false
     */
    public function getToolSettings(?ToolSettingsMode $mode = null, bool $simple = true): array|bool
    {
        return false;
    }

    /**
     * Perform a Tool Settings service request.
     *
     * @param array $settings  An associative array of settings (optional, default is none)
     *
     * @return bool  True if action was successful, otherwise false
     */
    public function setToolSettings(array $settings = []): bool
    {
        return false;
    }

}
