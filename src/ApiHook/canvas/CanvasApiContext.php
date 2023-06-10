<?php

namespace ceLTIc\LTI\ApiHook\canvas;

use ceLTIc\LTI\ApiHook\ApiContext;
use ceLTIc\LTI\Context;

/**
 * Class to implement Resource Link services for a Canvas platform via its proprietary API.
 *
 * @author  Simon Booth <s.p.booth@stir.ac.uk>
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class CanvasApiContext extends ApiContext
{
    use CanvasApi;

    /**
     * Class constructor.
     *
     * @param Context $context
     */
    public function __construct($context)
    {
        parent::__construct($context);
        $this->sourceObject = $context;
    }

    /**
     * Get course group sets and groups.
     *
     * @return bool  True if the request was successful
     */
    public function getGroups()
    {
        $ok = false;
        $platform = $this->sourceObject->getPlatform();
        $this->domain = $platform->getSetting('canvas.domain');
        $this->token = $platform->getSetting('canvas.token');
        $this->courseId = $this->sourceObject->getSetting('custom_canvas_course_id');
        $perPage = $platform->getSetting('canvas.per_page', strval(self::$DEFAULT_PER_PAGE));
        if (!is_numeric($perPage)) {
            $perPage = self::$DEFAULT_PER_PAGE;
        }
        $prefix = $platform->getSetting('canvas.group_set_prefix');
        if ($this->domain && $this->token && $this->courseId) {
            if ($this->setGroupSets($perPage, $prefix)) {
                $ok = $this->setGroups($perPage, array());
            }
        }

        return $ok;
    }

    /**
     * Get memberships.
     *
     * @param bool    $withGroups True is group information is to be requested as well
     *
     * @return array|bool  Array of UserResult objects or false if the request was not successful
     */
    public function getMemberships($withGroups)
    {
        return $this->get($withGroups);
    }

}
