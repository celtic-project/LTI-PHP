<?php
declare(strict_types=1);

/**
 * Enumerations for LTI properties
 */

namespace ceLTIc\LTI\Enum;

/**
 * Enumeration to define alternative modes when requesting tool settings
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
enum ToolSettingsMode: string
{

    /**
     * Settings at all levels mode.
     */
    case All = 'all';

    /**
     * Settings with distinct names at all levels mode.
     */
    case Distinct = 'distinct';

}
