<?php

namespace ceLTIc\LTI\ApiHook\moodle;

use ceLTIc\LTI\ApiHook\ApiResourceLink;

/**
 * Class to implement Resource Link services for a Moodle tool consumer via its web services.
 *
 * @author  Tony Butler <a.butler4@lancaster.ac.uk>
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @version  3.1.0
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class MoodleApiResourceLink extends ApiResourceLink
{
    use MoodleApi;

    /**
     * Get memberships.
     *
     * @param bool    $withGroups True is group information is to be requested as well
     *
     * @return mixed Array of UserResult objects or False if the request was not successful
     */
    public function getMemberships($withGroups)
    {
        $this->sourceObject = $this->resourceLink;
        if (!empty($this->resourceLink->getContextId())) {
            $this->courseId = $this->resourceLink->getContext()->ltiContextId;
        }
        $this->courseId = '2';

        $users = $this->get($withGroups);

        if (!empty($this->resourceLink->getContextId())) {
            $this->resourceLink->getContext()->groupSets = $this->resourceLink->groupSets;
            $this->resourceLink->getContext()->groups = $this->resourceLink->groups;
        }

        return $users;
    }

}
