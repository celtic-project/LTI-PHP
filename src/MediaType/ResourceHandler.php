<?php
declare(strict_types=1);

namespace ceLTIc\LTI\MediaType;

use ceLTIc\LTI\Tool;
use ceLTIc\LTI\Profile\ResourceHandler;

/**
 * Class to represent an LTI Resource Handler
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ResourceHandler
{

    /**
     * Class constructor.
     *
     * @param Tool $tool                        Tool object
     * @param ResourceHandler $resourceHandler  Profile resource handler object
     */
    function __construct(Tool $tool, ResourceHandler $resourceHandler)
    {
        $this->resource_type = (object) ['code' => $resourceHandler->item->id];
        $this->resource_name = (object) [
                'default_value' => $resourceHandler->item->name,
                'key' => "{$resourceHandler->item->id}.resource.name"
        ];
        $this->description = (object) [
                'default_value' => $resourceHandler->item->description,
                'key' => "{$resourceHandler->item->id}.resource.description"
        ];
        $this->icon_info = [
            (object) [
                'default_location' => (object) ['path' => $resourceHandler->icon],
                'key' => "{$resourceHandler->item->id}.icon.path"
            ]
        ];
        $this->message = [];
        foreach ($resourceHandler->requiredMessages as $message) {
            $this->message[] = new Message($message, $tool->platform->profile->capability_offered);
        }
        foreach ($resourceHandler->optionalMessages as $message) {
            if (in_array($message->type, $tool->platform->profile->capability_offered)) {
                $this->message[] = new Message($message, $tool->platform->profile->capability_offered);
            }
        }
    }

}
