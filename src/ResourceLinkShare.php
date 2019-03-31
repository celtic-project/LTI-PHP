<?php

namespace ceLTIc\LTI;

/**
 * Class to represent a tool consumer resource link share
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
    public $consumerName = null;

    /**
     * Resource link ID value.
     *
     * @var string|null $resourceLinkId
     */
    public $resourceLinkId = null;

    /**
     * Title of sharing context.
     *
     * @var string|null $title
     */
    public $title = null;

    /**
     * Whether sharing request is to be automatically approved on first use.
     *
     * @var bool|null $approved
     */
    public $approved = null;

    /**
     * Class constructor.
     */
    public function __construct()
    {

    }

}
