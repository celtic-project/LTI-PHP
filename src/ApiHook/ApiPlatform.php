<?php
declare(strict_types=1);

namespace ceLTIc\LTI\ApiHook;

use ceLTIc\LTI\Platform;

/**
 * Class to implement services for a platform via its proprietary API
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ApiPlatform
{

    /**
     * Platform object.
     *
     * @var Platform $platform
     */
    protected Platform $platform;

    /**
     * Class constructor.
     *
     * @param Platform $platform
     */
    public function __construct(Platform $platform)
    {
        $this->platform = $platform;
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
     * Get Tool Settings.
     *
     * @param bool $simple  True if all the simple media type is to be used (optional, default is true)
     *
     * @return array|bool  The array of settings if successful, otherwise false
     */
    public function getToolSettings(bool $simple = true): array|bool
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
