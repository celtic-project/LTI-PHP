<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Enum;

/**
 * Enumeration to define alternative scopes to use when generating a user ID
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
enum IdScope: int
{

    /**
     * Use ID value only.
     */
    case IdOnly = 0;

    /**
     * Prefix an ID with the consumer key.
     */
    case Platform = 1;

    /**
     * Prefix the ID with the consumer key and context ID.
     */
    case Context = 2;

    /**
     * Prefix the ID with the consumer key and resource ID.
     */
    case Resource = 3;

    /**
     * Character used to separate each element of an ID.
     */
    public const SEPARATOR = ':';

}
