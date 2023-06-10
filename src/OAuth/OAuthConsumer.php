<?php

namespace ceLTIc\LTI\OAuth;

/**
 * Class to represent an OAuth Consumer
 *
 * @copyright  Andy Smith (http://oauth.googlecode.com/svn/code/php/)
 * @version  2008-08-04
 * @license  https://opensource.org/licenses/MIT The MIT License
 */
class OAuthConsumer
{

    /**
     * Consumer key.
     *
     * @var string $key
     */
    public $key;

    /**
     * Shared secret.
     *
     * @var string $secret
     */
    public $secret;

    /**
     * Callback URL.
     *
     * @var string|null $callback_url
     */
    public $callback_url;

    /**
     * Class constructor.
     *
     * @param string $key                Consumer key
     * @param string $secret             Shared secret
     * @param string|null $callback_url  Callback URL
     */
    function __construct($key, $secret, $callback_url = null)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->callback_url = $callback_url;
    }

    /**
     * Convert object to a string.
     *
     * @return string
     */
    function __toString()
    {
        return "OAuthConsumer[key=$this->key,secret=$this->secret]";
    }

}
