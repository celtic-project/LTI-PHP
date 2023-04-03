<?php
declare(strict_types=1);

namespace ceLTIc\LTI;

/**
 * Class to represent a platform resource link share
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ResourceLinkShare
{

    /**
     * Consumer name value.
     *
     * @var string|null $consumerName
     */
    public ?string $consumerName = null;

    /**
     * Resource link ID value.
     *
     * @var int|null $resourceLinkId
     */
    public ?int $resourceLinkId = null;

    /**
     * Title of sharing context.
     *
     * @var string|null $title
     */
    public ?string $title = null;

    /**
     * Whether sharing request is to be automatically approved on first use.
     *
     * @var bool|null $approved
     */
    public ?bool $approved = null;

    /**
     * Class constructor.
     */
    public function __construct()
    {

    }

}
