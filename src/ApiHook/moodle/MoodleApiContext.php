<?php

namespace ceLTIc\LTI\ApiHook\moodle;

use ceLTIc\LTI\ApiHook\ApiContext;

/**
 * Class to implement Context services for a Moodle platform via its web services.
 *
 * @author  Tony Butler <a.butler4@lancaster.ac.uk>
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class MoodleApiContext extends ApiContext
{
    use MoodleApi;

    /**
     * Class constructor.
     *
     * @param \ceLTIc\LTI\Context $context
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
        $this->courseId = $this->context->ltiContextId;
        $platform = $this->sourceObject->getPlatform();
        $this->url = $platform->getSetting('moodle.url');
        $this->token = $platform->getSetting('moodle.token');
        $perPage = $platform->getSetting('moodle.per_page', '');
        if (!is_numeric($perPage)) {
            $perPage = self::$DEFAULT_PER_PAGE;
        } else {
            $perPage = intval($perPage);
        }
        $prefix = $platform->getSetting('moodle.grouping_prefix');
        if ($this->url && $this->token && $this->courseId) {
            $ok = $this->setGroupings($prefix);
        } else {
            $ok = false;
        }

        return $ok;
    }

    /**
     * Get Memberships.
     *
     * @param bool    $withGroups True is group information is to be requested as well
     *
     * @return mixed Array of UserResult objects or False if the request was not successful
     */
    public function getMemberships($withGroups = false)
    {
        $this->courseId = $this->context->ltiContextId;

        return $this->get($withGroups);
    }

}
