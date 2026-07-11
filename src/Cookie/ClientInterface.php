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
     * @param string $name  Name of Cookie
     *
     * @return bool  True if the cookie exists
     */
    public function hasCookie(string $name): bool;

    /**
     * Get a cookie value.
     *
     * @param string $name  Name of cookie
     *
     * @return string|null  Value of cookie or null if the cookie does not exist
     */
    public function getValue(string $name): ?string;

    /**
     * Define a new cookie.
     *
     * @param string $name      Name of cookie
     * @param string $value     Value of cookie
     * @param int $expires      Life of cookie in minutes
     * @param string $path      Cookie path
     * @param string $domain    Cookie domain
     * @param bool $secure      True if cookie is for a secure connection
     * @param bool $httpOnly    True if cookie is for HTTP connections only
     * @param string $sameSite  SameSite value
     *
     * @return bool  True if the cookie was successfully created
     */
    public function createCookie(string $name, string $value, int $expires, string $path, string $domain, bool $secure,
        bool $httpOnly, string $sameSite): bool;

}
