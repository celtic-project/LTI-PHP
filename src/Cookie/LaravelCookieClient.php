<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Cookie;

use Illuminate\Support\Facades\Cookie;

/**
 * Class to implement the user cookie interface using the Laravel framework.
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class LaravelCookieClient implements ClientInterface
{

    /**
     * Get number of cookies.
     *
     * @return int  Number of cookies
     */
    public function numCookies(): int
    {
        return count(Cookie::get());
    }

    /**
     * Check if a cookie exists.
     *
     * @param $name string  Name of Cookie
     *
     * @return bool  True if the cookie exists
     */
    public function hasCookie(string $name): bool
    {
        return Cookie::has($name);
    }

    /**
     * Get a cookie value.
     *
     * @param $name string  Name of cookie
     *
     * @return string|null  Value of cookie or null if the cookie does not exist
     */
    public function getCookie(string $name): ?string
    {
        $value = null;
        if ($this->hasCookie($name)) {
            $value = Cookie::get($name);
        }

        return $value;
    }

}
