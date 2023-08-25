<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Enum;

/**
 * Enumeration to define alternative outcome types
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
enum OutcomeType: string
{

    /**
     * Decimal outcome type.
     */
    case Decimal = 'decimal';

    /**
     * Percentage outcome type.
     */
    case Percentage = 'percentage';

    /**
     * Ratio outcome type.
     */
    case Ratio = 'ratio';

    /**
     * Letter (A-F) outcome type.
     */
    case LetterAF = 'letteraf';

    /**
     * Letter (A-F) with optional +/- outcome type.
     */
    case LetterAFPlus = 'letterafplus';

    /**
     * Pass/fail outcome type.
     */
    case PassFail = 'passfail';

    /**
     * Free text outcome type.
     */
    case Text = 'freetext';

}
