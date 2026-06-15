<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Cookie;

/**
 * Interface to represent a user cookie
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license   GNU Lesser General Public License, version 3 (<http://www.gnu.org/licenses/lgpl.html>)
 */
interface ClientInterface
{

    /**
     * Get number of cookies.
     *
     * @return int  Number of cookies
     */
    public function numCookies(): int;

    /**
     * Check if a cookie exists.
     *
     * @param $name string  Name of Cookie
     *
     * @return bool  True if the cookie exists
     */
    public function hasCookie(string $name): bool;

    /**
     * Get a cookie value.
     *
     * @param $name string  Name of cookie
     *
     * @return string|null  Value of cookie or null if the cookie does not exist
     */
    public function getCookie(string $name): ?string;

}
