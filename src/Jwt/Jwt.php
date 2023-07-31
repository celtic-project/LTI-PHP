<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Jwt;

use ceLTIc\LTI\Util;

/**
 * Class to represent an HTTP message request
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class Jwt
{

    /**
     * Life (in seconds) of an issued JWT (default is 1 minute).
     *
     * @var int $life
     */
    public static $life = 60;

    /**
     * Leeway (in seconds) to allow when checking timestamps (default is 3 minutes).
     *
     * @var int $leeway
     */
    public static $leeway = 180;

    /**
     * Allow use of jku header in JWT.
     *
     * @var bool $allowJkuHeader
     */
    public static $allowJkuHeader = false;

    /**
     * The client used to handle JWTs.
     *
     * @var ClientInterface $jwtClient
     */
    private static $jwtClient;

    /**
     * Class constructor.
     */
    function __construct()
    {

    }

    /**
     * Set the JWT client to use for handling JWTs.
     *
     * @param ClientInterface|null $jwtClient
     */
    public static function setJwtClient(?ClientInterface $jwtClient = null): void
    {
        self::$jwtClient = $jwtClient;
        Util::logDebug('JwtClient set to \'' . get_class(self::$jwtClient) . '\'');
    }

    /**
     * Get the JWT client to use for handling JWTs. If one is not set, a default client is created.
     *
     * @return ClientInterface|null  The JWT client
     */
    public static function getJwtClient(): ?ClientInterface
    {
        if (!self::$jwtClient) {
            self::$jwtClient = new FirebaseClient();
            Util::logDebug('JwtClient set to \'' . get_class(self::$jwtClient) . '\'');
        }

        return self::$jwtClient;
    }

}
