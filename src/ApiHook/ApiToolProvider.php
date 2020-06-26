<?php

namespace ceLTIc\LTI\ApiHook;

use ceLTIc\LTI\Util;

/**
 * Class to implement tool provider specific functions for LTI messages
 *
 * @deprecated Use ApiTool instead
 * @see ApiTool
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ApiToolProvider extends ApiTool
{

    /**
     * Class constructor.
     *
     * @param \ceLtic\LTI\ToolProvider|null $toolProvider
     */
    public function __construct($toolProvider)
    {
        parent::__construct($toolProvider);
        Util::logDebug('Class ceLTIc\LTI\ApiHook\ApiToolProvider has been deprecated; please use ceLTIc\LTI\ApiHook\ApiTool instead.',
            true);
    }

}
