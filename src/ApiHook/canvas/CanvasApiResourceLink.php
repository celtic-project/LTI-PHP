<?php

namespace ceLTIc\LTI\ApiHook\canvas;

use ceLTIc\LTI\ApiHook\ApiResourceLink;
use ceLTIc\LTI\UserResult;

/**
 * Class to implement Resource Link services for a Canvas tool consumer via its proprietary API.
 *
 * @author  Simon Booth <s.p.booth@stir.ac.uk>
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class CanvasApiResourceLink extends ApiResourceLink
{
    use CanvasApi;

    /**
     * Class constructor.
     *
     * @param \ceLTIc\LTI\ResourceLink $resourceLink
     */
    public function __construct($resourceLink)
    {
        parent::__construct($resourceLink);
        $this->sourceObject = $resourceLink;
    }

    /**
     * Get memberships.
     *
     * @param bool    $withGroups True is group information is to be requested as well
     *
     * @return mixed Array of UserResult objects or False if the request was not successful
     */
    public function getMemberships($withGroups)
    {
        $users = $this->get($withGroups);

        if (!empty($this->resourceLink->getContextId())) {
            $this->resourceLink->getContext()->groupSets = $this->resourceLink->groupSets;
            $this->resourceLink->getContext()->groups = $this->resourceLink->groups;
        }

        return $users;
    }

}
