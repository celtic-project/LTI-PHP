<?php

namespace ceLTIc\LTI;

use ceLTIc\LTI\Content\Placement;

/**
 * Class to represent a content-item placement object
 *
 * @deprecated Use Content::Placement instead
 * @see Content::Placement
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ContentItemPlacement extends Placement
{

    /**
     * Class constructor.
     *
     * @param int $displayWidth       Width of item location
     * @param int $displayHeight      Height of item location
     * @param string $documentTarget  Location to open content in
     * @param string $windowTarget    Name of window target
     */
    function __construct($displayWidth, $displayHeight, $documentTarget, $windowTarget)
    {
        parent::__construct($documentTarget, $displayWidth, $displayHeight, $windowTarget);
        Util::logDebug('Class ceLTIc\LTI\ContentItemPlacement has been deprecated; please use ceLTIc\LTI\Content\Placement instead ' .
            '(note change in parameter order for constructor).', true);
    }

}
