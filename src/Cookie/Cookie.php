<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Cookie;

use ceLTIc\LTI\Util;

/**
 * Class to represent a user cookie
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class Cookie
{

    /**
     * The client used to handle user cookies.
     *
     * @var ClientInterface|null $cookieClient
     */
    private static ?ClientInterface $cookieClient = null;

    /**
     * Class constructor.
     */
    function __construct()
    {

    }

    /**
     * Set the client to use for handling cookie sessions.
     *
     * @param ClientInterface|null $cookieClient
     *
     * @return void
     */
    public static function setCookieClient(?ClientInterface $cookieClient = null): void
    {
        self::$cookieClient = $cookieClient;
        Util::logDebug('CookieClient set to \'' . get_class(self::$cookieClient) . '\'');
    }

    /**
     * Get the client to use for handling cookie sessions. If one is not set, a default client is created.
     *
     * @return ClientInterface|null  The user cookie client
     */
    public static function getCookieClient(): ?ClientInterface
    {
        if (empty(self::$cookieClient)) {
            self::setCookieClient(new PHPCookieClient());
        }

        return self::$cookieClient;
    }

}
