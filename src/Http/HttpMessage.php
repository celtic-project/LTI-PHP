<?php

namespace ceLTIc\LTI\Http;

use ceLTIc\LTI\Http;
use ceLTIc\LTI\Http\ClientInterface;

/**
 * Class to represent an HTTP message request
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class HttpMessage
{

    /**
     * True if message was processed successfully.
     *
     * @var bool    $ok
     */
    public $ok = false;

    /**
     * Request body.
     *
     * @var string|null $request
     */
    public $request = null;

    /**
     * Request headers.
     *
     * @var string|array $requestHeaders
     */
    public $requestHeaders = '';

    /**
     * Response body.
     *
     * @var string|null $response
     */
    public $response = null;

    /**
     * Response headers.
     *
     * @var string|array $responseHeaders
     */
    public $responseHeaders = '';

    /**
     * Status of response (0 if undetermined).
     *
     * @var int $status
     */
    public $status = 0;

    /**
     * Error message
     *
     * @var string $error
     */
    public $error = '';

    /**
     * Request URL.
     *
     * @var string|null $url
     */
    private $url = null;

    /**
     * Request method.
     *
     * @var string $method
     */
    private $method = null;

    /**
     * The client used to send the request.
     *
     * @var ClientInterface $httpClient
     */
    private static $httpClient;

    /**
     * Class constructor.
     *
     * @param string $url     URL to send request to
     * @param string $method  Request method to use (optional, default is GET)
     * @param mixed  $params  Associative array of parameter values to be passed or message body (optional, default is none)
     * @param string $header  Values to include in the request header (optional, default is none)
     */
    function __construct($url, $method = 'GET', $params = null, $header = null)
    {
        $this->url = $url;
        $this->method = strtoupper($method);
        if (is_array($params)) {
            $this->request = http_build_query($params);
        } else {
            $this->request = $params;
        }
        if (!empty($header)) {
            $this->requestHeaders = explode("\n", $header);
        }
    }

    /**
     * Get the target URL for the request.
     *
     * @return string Request URL
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Get the HTTP method for the request.
     *
     * @return string Message method
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set the HTTP client to use for sending the message.
     *
     * @param Http\ClientInterface|null $httpClient
     */
    public static function setHttpClient($httpClient = null)
    {
        self::$httpClient = $httpClient;
    }

    /**
     * Get the HTTP client to use for sending the message. If one is not set, a default client is created.
     *
     * @return Http\ClientInterface|null  The HTTP client
     */
    public static function getHttpClient()
    {
        if (!self::$httpClient) {
            if (function_exists('curl_init')) {
                self::$httpClient = new Http\CurlClient();
            } elseif (ini_get('allow_url_fopen')) {
                self::$httpClient = new Http\StreamClient();
            }
        }

        return self::$httpClient;
    }

    /**
     * Send the request to the target URL.
     *
     * @return bool    True if the request was successful
     */
    public function send()
    {
        $client = self::getHttpClient();
        $this->ok = !empty($client);
        if ($this->ok) {
            $this->ok = $client->send($this);
        } else {
            $this->error = 'No HTTP client interface is available';
        }

        return $this->ok;
    }

}
