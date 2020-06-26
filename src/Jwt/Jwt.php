<?php

namespace ceLTIc\LTI\Jwt;

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
     * @param Jwt\ClientInterface|null $jwtClient
     */
    public static function setJwtClient($jwtClient = null)
    {
        self::$jwtClient = $jwtClient;
    }

    /**
     * Get the JWT client to use for handling JWTs. If one is not set, a default client is created.
     *
     * @return ClientInterface|null  The JWT client
     */
    public static function getJwtClient()
    {
        if (!self::$jwtClient) {
            self::$jwtClient = new FirebaseClient();
        }

        return self::$jwtClient;
    }

}
