<?php

namespace ceLTIc\LTI\ApiHook\canvas;

/**
 * Class to implement canvas-specific functions for LTI messages
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
//
class CanvasApiToolProvider extends \ceLTIc\LTI\ApiHook\ApiToolProvider
{

    public function getUserId()
    {
        $userId = '';
        $messageParameters = $this->toolProvider->getMessageParameters();
        if (isset($messageParameters['custom_canvas_user_id'])) {
            $userId = trim($messageParameters['custom_canvas_user_id']);
        }

        return $userId;
    }

}
