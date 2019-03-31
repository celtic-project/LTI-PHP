<?php

namespace ceLTIc\LTI\ApiHook;

/**
 * Class to implement tool consumer specific functions for LTI messages
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ApiToolProvider
{

    /**
     * Tool Provider object.
     *
     * @var \ceLtic\LTI\ToolProvider|null $toolProvider
     */
    protected $toolProvider = null;

    /**
     * Class constructor.
     *
     * @param \ceLtic\LTI\ToolProvider|null $toolProvider
     */
    public function __construct($toolProvider)
    {
        $this->toolProvider = $toolProvider;
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
