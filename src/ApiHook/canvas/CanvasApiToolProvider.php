<?php

namespace ceLTIc\LTI\ApiHook\canvas;

use ceLTIc\LTI\Util;
use ceLTIc\LTI\Tool;

/**
 * Class to implement canvas-specific functions for LTI messages
 *
 * @deprecated Use CanvasApiTool instead
 * @see CanvasApiTool
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class CanvasApiToolProvider extends CanvasApiTool
{

    /**
     * Class constructor.
     *
     * @param Tool|null $tool
     */
    public function __construct($tool)
    {
        parent::__construct($tool);
        Util::logDebug('Class ceLTIc\LTI\ApiHook\canvas\CanvasApiToolProvider has been deprecated; please use ceLTIc\LTI\ApiHook\canvas\CanvasApiTool instead.',
            true);
    }

}
