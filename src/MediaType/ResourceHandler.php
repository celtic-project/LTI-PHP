<?php

namespace ceLTIc\LTI\MediaType;

use ceLTIc\LTI\ToolProvider;
use ceLTIc\LTI\Profile;

/**
 * Class to represent an LTI Resource Handler
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @version  3.1.0
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ResourceHandler
{

    /**
     * Class constructor.
     *
     * @param ToolProvider $toolProvider   Tool Provider object
     * @param Profile\ResourceHandler $resourceHandler   Profile resource handler object
     */
    function __construct($toolProvider, $resourceHandler)
    {
        $this->resource_type = new \stdClass;
        $this->resource_type->code = $resourceHandler->item->id;
        $this->resource_name = new \stdClass;
        $this->resource_name->default_value = $resourceHandler->item->name;
        $this->resource_name->key = "{$resourceHandler->item->id}.resource.name";
        $this->description = new \stdClass;
        $this->description->default_value = $resourceHandler->item->description;
        $this->description->key = "{$resourceHandler->item->id}.resource.description";
        $icon_info = new \stdClass;
        $icon_info->default_location = new \stdClass;
        $icon_info->default_location->path = $resourceHandler->icon;
        $icon_info->key = "{$resourceHandler->item->id}.icon.path";
        $this->icon_info = array();
        $this->icon_info[] = $icon_info;
        $this->message = array();
        foreach ($resourceHandler->requiredMessages as $message) {
            $this->message[] = new Message($message, $toolProvider->consumer->profile->capability_offered);
        }
        foreach ($resourceHandler->optionalMessages as $message) {
            if (in_array($message->type, $toolProvider->consumer->profile->capability_offered)) {
                $this->message[] = new Message($message, $toolProvider->consumer->profile->capability_offered);
            }
        }
    }

}
