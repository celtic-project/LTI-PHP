<?php

namespace ceLTIc\LTI\Content;

/**
 * Class to represent an LTI assignment content-item object
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class LtiAssignmentItem extends LtiLinkItem
{

    /**
     * Class constructor.
     *
     * @param Placement[]|Placement|null $placementAdvices  Array of Placement objects (or single placement object) for item (optional)
     * @param string|null $id   URL of content-item (optional)
     */
    function __construct($placementAdvices = null, $id = null)
    {
        Item::__construct(Item::TYPE_LTI_ASSIGNMENT, $placementAdvices, $id);
        $this->setMediaType(Item::LTI_ASSIGNMENT_MEDIA_TYPE);
    }

}
