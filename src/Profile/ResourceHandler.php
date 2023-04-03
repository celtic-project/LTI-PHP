<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Profile;

/**
 * Class to represent a resource handler object
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ResourceHandler
{

    /**
     * General details of resource handler.
     *
     * @var Item|null $item
     */
    public ?Item $item = null;

    /**
     * URL of icon.
     *
     * @var string|null $icon
     */
    public ?string $icon = null;

    /**
     * Required Message objects for resource handler.
     *
     * @var array|null $requiredMessages
     */
    public ?array $requiredMessages = null;

    /**
     * Optional Message objects for resource handler.
     *
     * @var array|null $optionalMessages
     */
    public ?array $optionalMessages = null;

    /**
     * Class constructor.
     *
     * @param Item $item               General details of resource handler
     * @param string $icon             URL of icon
     * @param array $requiredMessages  Array of required Message objects for resource handler
     * @param array $optionalMessages  Array of optional Message objects for resource handler
     */
    function __construct(Item $item, string $icon, array $requiredMessages, array $optionalMessages)
    {
        $this->item = $item;
        $this->icon = $icon;
        $this->requiredMessages = $requiredMessages;
        $this->optionalMessages = $optionalMessages;
    }

}
