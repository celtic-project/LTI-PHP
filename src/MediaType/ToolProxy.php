<?php

namespace ceLTIc\LTI\MediaType;

use ceLTIc\LTI\ToolProvider;
use ceLTIc\LTI\Profile\ServiceDefinition;

/**
 * Class to represent an LTI Tool Proxy media type
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @version  3.1.0
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ToolProxy
{

    /**
     * Class constructor.
     *
     * @param ToolProvider $toolProvider   Tool Provider object
     * @param ServiceDefinition $toolProxyService  Tool Proxy service
     * @param string $secret  Shared secret
     */
    function __construct($toolProvider, $toolProxyService, $secret)
    {
        $contexts = array();

        $this->{'@context'} = array_merge(array('http://purl.imsglobal.org/ctx/lti/v2/ToolProxy'), $contexts);
        $this->{'@type'} = 'ToolProxy';
        $this->{'@id'} = "{$toolProxyService->endpoint}";
        $this->lti_version = 'LTI-2p0';
        $this->tool_consumer_profile = $toolProvider->consumer->profile->{'@id'};
        $this->tool_profile = new ToolProfile($toolProvider);
        $this->security_contract = new SecurityContract($toolProvider, $secret);
    }

}
