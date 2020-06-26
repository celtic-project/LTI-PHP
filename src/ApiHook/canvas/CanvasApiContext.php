<?php

namespace ceLTIc\LTI\ApiHook\canvas;

use ceLTIc\LTI\ApiHook\ApiContext;

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
     * @param \ceLTIc\LTI\Context $context
     */
    public function __construct($context)
    {
        parent::__construct($context);
        $this->sourceObject = $context;
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
        return $this->get($withGroups);
    }

}
