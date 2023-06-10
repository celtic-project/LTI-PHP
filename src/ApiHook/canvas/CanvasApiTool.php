<?php

namespace ceLTIc\LTI\ApiHook\canvas;

use ceLTIc\LTI\ApiHook\ApiTool;

/**
 * Class to implement canvas-specific functions for LTI messages
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class CanvasApiTool extends ApiTool
{

    /**
     * Get the User ID.
     *
     * @return string  User ID value, or empty string if not available.
     */
    public function getUserId()
    {
        $userId = '';
        $messageParameters = $this->tool->getMessageParameters(true, true, false);
        if (isset($messageParameters['custom_canvas_user_id'])) {
            $userId = trim($messageParameters['custom_canvas_user_id']);
        }

        return $userId;
    }

}
