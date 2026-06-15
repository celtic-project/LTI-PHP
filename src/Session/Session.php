<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Session;

use ceLTIc\LTI\Util;

/**
 * Class to represent a user session
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class Session
{

    /**
     * The client used to handle user sessions.
     *
     * @var ClientInterface|null $sessionClient
     */
    private static ?ClientInterface $sessionClient = null;

    /**
     * Class constructor.
     */
    function __construct()
    {

    }

    /**
     * Set the client to use for handling user sessions.
     *
     * @param ClientInterface|null $sessionClient
     *
     * @return void
     */
    public static function setSessionClient(?ClientInterface $sessionClient = null): void
    {
        self::$sessionClient = $sessionClient;
        Util::logDebug('SessionClient set to \'' . get_class(self::$sessionClient) . '\'');
    }

    /**
     * Get the client to use for handling user sessions. If one is not set, a default client is created.
     *
     * @return ClientInterface|null  The user session client
     */
    public static function getSessionClient(): ?ClientInterface
    {
        if (empty(self::$sessionClient)) {
            self::setSessionClient(new PHPSessionClient());
        }

        return self::$sessionClient;
    }

}
