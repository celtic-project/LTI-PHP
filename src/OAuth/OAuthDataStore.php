<?php

namespace ceLTIc\LTI\OAuth;

/**
 * Class to represent an OAuth Data Store
 *
 * @copyright  Andy Smith (http://oauth.googlecode.com/svn/code/php/)
 * @version  2008-08-04
 * @license  https://opensource.org/licenses/MIT The MIT License
 */
class OAuthDataStore
{

    /**
     * Find consumer based on key.
     *
     * @param string $consumer_key
     *
     * @return OAuthConsumer
     */
    function lookup_consumer($consumer_key)
    {
        // implement me
    }

    /**
     * Find token.
     *
     * @param OAuthConsumer $consumer  Consumer
     * @param string|null $token_type  Token type
     * @param string|null $token       Token value
     *
     * @return OAuthToken
     */
    function lookup_token($consumer, $token_type, $token)
    {
        // implement me
    }

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
    function lookup_nonce($consumer, $token, $nonce, $timestamp)
    {
        // implement me
    }

    /**
     * Get new request token.
     *
     * @param OAuthConsumer $consumer  Consumer
     * @param string|null $callback    Callback URL
     *
     * @return string|null
     */
    function new_request_token($consumer, $callback = null)
    {
        // return a new token attached to this consumer
    }

    /**
     * Get new access token.
     *
     * @param string $token            Token value
     * @param OAuthConsumer $consumer  OAuthConsumer object
     * @param string|null $verifier    Verification code
     *
     * @return string|null
     */
    function new_access_token($token, $consumer, $verifier = null)
    {
        // return a new access token attached to this consumer
        // for the user associated with this token if the request token
        // is authorized
        // should also invalidate the request token
    }

}
