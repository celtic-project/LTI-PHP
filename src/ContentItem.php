<?php

namespace ceLTIc\LTI;

use ceLTIc\LTI\Content\Item;
use ceLTIc\LTI\Content\ContentItemPlacement;

/**
 * Class to represent a content-item object
 *
 * @deprecated Use Content::Item instead
 * @see Content::Item
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ContentItem extends Item
{

    /**
     * Class constructor.
     *
     * @param string $type Class type of content-item
     * @param ContentItemPlacement $placementAdvice  Placement object for item (optional)
     * @param string $id   URL of content-item (optional)
     */
    function __construct($type, $placementAdvice = null, $id = null)
    {
        parent::__construct($type, $placementAdvice, $id);
        Util::logDebug('Class ceLTIc\LTI\ContentItem has been deprecated; please use ceLTIc\LTI\Content\Item instead.', true);
    }

}
