<?php

namespace ceLTIc\LTI\ApiHook\moodle;

use ceLTIc\LTI\ApiHook\ApiContext;

/**
 * Class to implement Context services for a Moodle tool consumer via its web services.
 *
 * @author  Tony Butler <a.butler4@lancaster.ac.uk>
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @version  3.1.0
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class MoodleApiContext extends ApiContext
{
    use MoodleApi;

    /**
     * Get Memberships.
     *
     * @param bool    $withGroups True is group information is to be requested as well
     *
     * @return mixed Array of UserResult objects or False if the request was not successful
     */
    public function getMemberships($withGroups = false)
    {
        $this->sourceObject = $this->context;
        $this->courseId = $this->context->ltiContextId;

        return $this->get($withGroups);
    }

}
