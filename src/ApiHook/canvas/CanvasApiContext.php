<?php

namespace ceLTIc\LTI\ApiHook\canvas;

use ceLTIc\LTI\ApiHook\ApiContext;

/**
 * Class to implement Resource Link services for a Canvas tool consumer via its proprietary API.
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
     * Get memberships.
     *
     * @param bool    $withGroups True is group information is to be requested as well
     *
     * @return mixed Array of UserResult objects or False if the request was not successful
     */
    public function getMemberships($withGroups)
    {
        $this->sourceObject = $this->context;

        return $this->get($withGroups);
    }

}
