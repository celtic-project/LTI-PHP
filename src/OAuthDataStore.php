<?php
declare(strict_types=1);

namespace ceLTIc\LTI;

use ceLTIc\LTI\Platform;
use ceLTIc\LTI\Tool;
use ceLTIc\LTI\OAuth;
use ceLTIc\LTI\OAuth\OAuthConsumer;
use ceLTIc\LTI\OAuth\OAuthToken;
use ceLTIc\LTI\OAuth\OAuthException;

/**
 * Class to represent an OAuth datastore
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class OAuthDataStore extends OAuth\OAuthDataStore
{

    /**
     * System object.
     *
     * @var Platform|Tool $system
     */
    private Platform|Tool $system;

    /**
     * Class constructor.
     *
     * @param Platform|Tool $system  System object
     */
    public function __construct(Platform|Tool $system)
    {
        $this->system = $system;
    }

    /**
     * Create an OAuthConsumer object for the system.
     *
     * @param string $consumerKey  Consumer key value
     *
     * @return OAuthConsumer  OAuthConsumer object
     */
    function lookup_consumer(string $consumerKey): OAuthConsumer
    {
        $key = $this->system->getKey();
        $secret = '';
        if (!empty($key)) {
            $secret = $this->system->secret;
        } elseif (($this->system instanceof Tool) && !empty($this->system->platform)) {
            $key = $this->system->platform->getKey();
            $secret = $this->system->platform->secret;
        } elseif (($this->system instanceof Platform) && !empty(Tool::$defaultTool)) {
            $key = Tool::$defaultTool->getKey();
            $secret = Tool::$defaultTool->secret;
        }
        if ($key !== $consumerKey) {
            throw new OAuthException('Consumer key not found');
        }

        return new OAuthConsumer($key, $secret);
    }

    /**
     * Create an OAuthToken object for the system.
     *
     * @param string $consumer        OAuthConsumer object
     * @param string|null $tokenType  Token type
     * @param string|null $token      Token value
     *
     * @return OAuthToken  OAuthToken object
     */
    function lookup_token(OAuthConsumer $consumer, ?string $tokenType, ?string $token): OAuthToken
    {
        return new OAuthToken('', '');
    }

    /**
     * Lookup nonce value for the system.
     *
     * @param OAuthConsumer $consumer  OAuthConsumer object
     * @param OAuthToken $token        Token value
     * @param string $value            Nonce value
     * @param string $timestamp        Date/time of request
     *
     * @return bool    True if the nonce value already exists
     */
    function lookup_nonce(OAuthConsumer $consumer, OAuthToken $token, string $value, string $timestamp): bool
    {
        if ($this->system instanceof Platform) {
            $platform = $this->system;
        } else {
            $platform = $this->system->platform;
        }
        $nonce = new PlatformNonce($platform, $value);
        $ok = !$nonce->load();
        if ($ok) {
            $ok = $nonce->save();
        }
        if (!$ok) {
            $this->system->reason = 'Invalid nonce.';
        }

        return !$ok;
    }

    /**
     * Get new request token.
     *
     * @param OAuthConsumer $consumer  OAuthConsumer object
     * @param string|null $callback    Callback URL
     *
     * @return string|null Null value
     */
    function new_request_token(OAuthConsumer $consumer, ?string $callback = null): ?string
    {
        return null;
    }

    /**
     * Get new access token.
     *
     * @param string $token            Token value
     * @param OAuthConsumer $consumer  OAuthConsumer object
     * @param string|null $verifier    Verification code
     *
     * @return string|null Null value
     */
    function new_access_token(string $token, OAuthConsumer $consumer, ?string $verifier = null): ?string
    {
        return null;
    }

}
