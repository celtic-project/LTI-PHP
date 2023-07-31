<?php
declare(strict_types=1);

namespace ceLTIc\LTI\OAuth;

/**
 * Class to represent an OAuth token
 *
 * @copyright  Andy Smith (http://oauth.googlecode.com/svn/code/php/)
 * @version  2008-08-04
 * @license  https://opensource.org/licenses/MIT The MIT License
 */
class OAuthToken
{
    // Access tokens and request tokens

    /**
     * Name.
     *
     * @var string $key
     */
    public string $key;

    /**
     * Secret.
     *
     * @var string $secret
     */
    public string $secret;

    /**
     * Class constructor.
     *
     * @param string $key     The token
     * @param string $secret  The token secret
     */
    function __construct(string $key, string $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
    }

    /**
     * Generates the basic string serialization of a token that a server
     * would respond to request_token and access_token calls with.
     *
     * @return string
     */
    function to_string(): string
    {
        return 'oauth_token=' .
            OAuthUtil::urlencode_rfc3986($this->key) .
            '&oauth_token_secret=' .
            OAuthUtil::urlencode_rfc3986($this->secret);
    }

    /**
     * Convert object to a string.
     *
     * @return string
     */
    function __toString(): string
    {
        return $this->to_string();
    }

}
