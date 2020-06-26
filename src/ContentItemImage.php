<?php

namespace ceLTIc\LTI;

use ceLTIc\LTI\Content\Image;

/**
 * Class to represent a content-item image object
 *
 * @deprecated Use Content::Image instead
 * @see Content::Image
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ContentItemImage extends Image
{

    /**
     * Class constructor.
     *
     * @param string $id      URL of image
     * @param int    $height  Height of image in pixels (optional)
     * @param int    $width   Width of image in pixels (optional)
     */
    function __construct($id, $height = null, $width = null)
    {
        parent::__construct($id, $width, $height);
        Util::logDebug('Class ceLTIc\LTI\ContentItemImage has been deprecated; please use ceLTIc\LTI\Content\Image instead ' .
            '(note change of parameter order in constructor).', true);
    }

}
