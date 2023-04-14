<?php
declare(strict_types=1);

namespace ceLTIc\LTI\MediaType;

use ceLTIc\LTI\Tool;
use ceLTIc\LTI\Profile;

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
     * Resource type.
     *
     * @var object|null $resource_type
     */
    public ?object $resource_type = null;

    /**
     * Resource name.
     *
     * @var object|null $resource_name
     */
    public ?object $resource_name = null;

    /**
     * Resource description.
     *
     * @var object|null $description
     */
    public ?object $description = null;

    /**
     * Resource icon information.
     *
     * @var object|null $icon_info
     */
    public ?object $icon_info = null;

    /**
     * Resource messages.
     *
     * @var array|null $message
     */
    public ?array $message = null;

    /**
     * Class constructor.
     *
     * @param Tool $tool                                Tool object
     * @param Profile\ResourceHandler $resourceHandler  Profile resource handler object
     */
    function __construct(Tool $tool, Profile\ResourceHandler $resourceHandler)
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
