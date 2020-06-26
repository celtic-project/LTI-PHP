<?php

namespace ceLTIc\LTI;

use ceLTIc\LTI\Tool;
use ceLTIc\LTI\DataConnector\DataConnector;
use ceLTIc\LTI\Util;

/**
 * Class to represent an LTI Tool Provider
 *
 * @deprecated Use Tool instead
 * @see Tool
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ToolProvider extends Tool
{

    /**
     * LTI version 1 for messages.
     *
     * @deprecated Use Util::LTI_VERSION1 instead
     * @see Util::LTI_VERSION1
     */
    const LTI_VERSION1 = 'LTI-1p0';

    /**
     * LTI version 2 for messages.
     *
     * @deprecated Use Util::LTI_VERSION2 instead
     * @see Util::LTI_VERSION2
     */
    const LTI_VERSION2 = 'LTI-2p0';

    /**
     * Class constructor
     *
     * @param DataConnector     $dataConnector    Object containing a database connection object
     */
    function __construct($dataConnector)
    {
        Util::logDebug('Class ceLTIc\LTI\ToolProvider has been deprecated; please use ceLTIc\LTI\Tool instead.', true);
        parent::__construct($dataConnector);
    }

}
