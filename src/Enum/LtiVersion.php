<?php
declare(strict_types=1);

/**
 * Enumerations for LTI properties
 */

namespace ceLTIc\LTI\Enum;

/**
 * Enumeration to define alternative LTI version strings
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
enum LtiVersion: string
{

    /**
     * LTI version 1 for messages.
     */
    case V1 = 'LTI-1p0';

    /**
     * LTI version 1.3 for messages.
     */
    case V1P3 = '1.3.0';

    /**
     * LTI version 2 for messages.
     */
    case V2 = 'LTI-2p0';

}
