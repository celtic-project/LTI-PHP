<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Enum;

/**
 * Enumeration to define alternative service actions
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
enum ServiceAction: int
{

    /**
     * Read action.
     */
    case Read = 1;

    /**
     * Write (create/update) action.
     */
    case Write = 2;

    /**
     * Delete action.
     */
    case Delete = 3;

    /**
     * Create action.
     */
    case Create = 4;

    /**
     * Update action.
     */
    case Update = 5;

}
