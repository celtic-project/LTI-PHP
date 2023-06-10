<?php

namespace ceLTIc\LTI\ApiHook;

use ceLTIc\LTI\Tool;

/**
 * Class to implement tool specific functions for LTI messages
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ApiTool
{

    /**
     * Tool object.
     *
     * @var Tool|null $tool
     */
    protected $tool = null;

    /**
     * Class constructor.
     *
     * @param Tool|null $tool
     */
    public function __construct($tool)
    {
        $this->tool = $tool;
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
     * Get the User ID.
     *
     * @return string User ID value, or empty string if not available.
     */
    public function getUserId()
    {
        return '';
    }

    /**
     * Get the Context ID.
     *
     * @return string Context ID value, or empty string if not available.
     */
    public function getContextId()
    {
        return '';
    }

}
