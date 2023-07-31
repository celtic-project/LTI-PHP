<?php
declare(strict_types=1);

namespace ceLTIc\LTI\ApiHook;

use ceLTIc\LTI\Context;
use ceLTIc\LTI\Enum\ToolSettingsMode;

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
     * @var Context $context
     */
    protected Context $context;

    /**
     * Class constructor.
     *
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
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
     * Get course group sets and groups.
     *
     * @return bool  True if the request was successful
     */
    public function getGroups(): bool
    {
        return false;
    }

    /**
     * Get Memberships.
     *
     * @param bool $withGroups  True is group information is to be requested as well
     *
     * @return array|bool  The array of UserResult objects if successful, otherwise false
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
