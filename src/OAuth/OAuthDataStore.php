<?php
declare(strict_types=1);

namespace ceLTIc\LTI\OAuth;

/**
 * Class to represent an OAuth Data Store
 *
 * @copyright  Andy Smith (http://oauth.googlecode.com/svn/code/php/)
 * @version  2008-08-04
 * @license  https://opensource.org/licenses/MIT The MIT License
 */
abstract class OAuthDataStore
{

    /**
     * Find consumer based on key.
     *
     * @param string $consumer_key
     *
     * @return OAuthConsumer
     */
    abstract function lookup_consumer(string $consumer_key): OAuthConsumer;

    /**
     * Find token.
     *
     * @param OAuthConsumer $consumer  Consumer
     * @param string|null $token_type  Token type
     * @param string|null $token       Token value
     *
     * @return OAuthToken
     */
    abstract function lookup_token(OAuthConsumer $consumer, ?string $token_type, ?string $token): OAuthToken;

    /**
     * Check nonce value.
     *
     * @param OAuthConsumer $consumer  Consumer
     * @param OAuthToken $token        Token value
     * @param string $nonce            Nonce value
     * @param string $timestamp        Date/time of request
     *
     * @return bool
     */
    abstract function lookup_nonce(OAuthConsumer $consumer, OAuthToken $token, string $nonce, string $timestamp): bool;

    /**
     * Get new request token.
     *
     * @param OAuthConsumer $consumer  Consumer
     * @param string|null $callback    Callback URL
     *
     * @return string|null
     */
    abstract function new_request_token(OAuthConsumer $consumer, ?string $callback = null): ?string;

    /**
     * Get new access token.
     *
     * @param string $token            Token value
     * @param OAuthConsumer $consumer  OAuthConsumer object
     * @param string|null $verifier    Verification code
     *
     * @return string|null
     */
    abstract function new_access_token(string $token, OAuthConsumer $consumer, ?string $verifier = null): ?string;

}
