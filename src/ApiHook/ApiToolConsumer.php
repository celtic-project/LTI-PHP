<?php

namespace ceLTIc\LTI\ApiHook;

use ceLTIc\LTI\Util;

/**
 * Class to implement services for a tool consumer via its proprietary API
 *
 * @deprecated Use ApiPlatform instead
 * @see ApiPlatform
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ApiToolConsumer extends ApiPlatform
{

    /**
     * Class constructor.
     *
     * @param \ceLTIc\LTI\ToolConsumer $consumer
     */
    public function __construct($consumer)
    {
        parent::__construct($consumer);
        Util::logDebug('Class ceLTIc\LTI\ApiHook\ApiToolConsumer has been deprecated; please use ceLTIc\LTI\ApiHook\ApiPlatform instead.',
            true);
    }

}
