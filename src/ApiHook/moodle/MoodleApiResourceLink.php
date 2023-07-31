<?php

namespace ceLTIc\LTI\ApiHook\moodle;

use ceLTIc\LTI\ApiHook\ApiResourceLink;
use ceLTIc\LTI\ResourceLink;

/**
 * Class to implement Resource Link services for a Moodle platform via its web services.
 *
 * @author  Tony Butler <a.butler4@lancaster.ac.uk>
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class MoodleApiResourceLink extends ApiResourceLink
{
    use MoodleApi;

    /**
     * Class constructor.
     *
     * @param ResourceLink $resourceLink
     */
    public function __construct(ResourceLink $resourceLink)
    {
        parent::__construct($resourceLink);
        $this->sourceObject = $resourceLink;
    }

    /**
     * Get memberships.
     *
     * @param bool $withGroups  True is group information is to be requested as well
     *
     * @return array|bool  Array of UserResult objects or false if the request was not successful
     */
    public function getMemberships(bool $withGroups): array|bool
    {
        if (!empty($this->resourceLink->getContextId())) {
            $this->courseId = $this->resourceLink->getContext()->ltiContextId;
        }

        $users = $this->get($withGroups);

        if (!empty($this->resourceLink->getContextId())) {
            $this->resourceLink->getContext()->groupSets = $this->resourceLink->groupSets;
            $this->resourceLink->getContext()->groups = $this->resourceLink->groups;
        }

        return $users;
    }

}
