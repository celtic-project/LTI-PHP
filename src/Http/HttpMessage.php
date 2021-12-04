<?php

namespace ceLTIc\LTI\Http;

use ceLTIc\LTI\Util;

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
     * Relative links in response headers.
     *
     * @var array $relativeLinks
     */
    public $relativeLinks = array();

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
     * @param ClientInterface|null $httpClient
     */
    public static function setHttpClient($httpClient = null)
    {
        self::$httpClient = $httpClient;
        Util::logDebug('HttpClient set to \'' . get_class(self::$httpClient) . '\'');
    }

    /**
     * Get the HTTP client to use for sending the message. If one is not set, a default client is created.
     *
     * @return ClientInterface|null  The HTTP client
     */
    public static function getHttpClient()
    {
        if (!self::$httpClient) {
            if (function_exists('curl_init')) {
                self::$httpClient = new CurlClient();
            } elseif (ini_get('allow_url_fopen')) {
                self::$httpClient = new StreamClient();
            }
            if (self::$httpClient) {
                Util::logDebug('HttpClient set to \'' . get_class(self::$httpClient) . '\'');
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
        $this->relativeLinks = array();
        if (empty($client)) {
            $this->ok = false;
            $message = 'No HTTP client interface is available';
            $this->error = $message;
            Util::logError($message, true);
        } elseif (empty($this->url)) {
            $this->ok = false;
            $message = 'No URL provided for HTTP request';
            $this->error = $message;
            Util::logError($message, true);
        } else {
            $this->ok = $client->send($this);
            $this->parseRelativeLinks();
            if (Util::$logLevel > Util::LOGLEVEL_NONE) {
                $message = "Http\\HttpMessage->send {$this->method} request to '{$this->url}'";
                if (!empty($this->requestHeaders)) {
                    $message .= "\n{$this->requestHeaders}";
                }
                if (!empty($this->request)) {
                    $message .= "\n\n{$this->request}";
                }
                $message .= "\nResponse:";
                if (!empty($this->responseHeaders)) {
                    $message .= "\n{$this->responseHeaders}";
                }
                if (!empty($this->response)) {
                    $message .= "\n\n{$this->response}";
                }
                if ($this->ok) {
                    Util::logInfo($message);
                } else {
                    if (!empty($this->error)) {
                        $message .= "\nError: {$this->error}";
                    }
                    Util::logError($message);
                }
            }
        }

        return $this->ok;
    }

    /**
     * Check whether a relative link of the specified type exists.
     *
     * @param string $rel
     *
     * @return bool  True if it exists
     */
    public function hasRelativeLink($rel)
    {
        return array_key_exists($rel, $this->relativeLinks);
    }

    /**
     * Get the URL from the relative link with the specified type.
     *
     * @param string $rel
     *
     * @return string|null  The URL associated with the relative link, null if it is not defined
     */
    public function getRelativeLink($rel)
    {
        $url = null;
        if ($this->hasRelativeLink($rel)) {
            $url = $this->relativeLinks[$rel];
        }

        return $url;
    }

    /**
     * Get the relative links.
     *
     * @return array  Associative array of relative links
     */
    public function getRelativeLinks()
    {
        return $this->relativeLinks;
    }

###
###  PRIVATE METHOD
###

    /**
     * Parse the response headers for relative links.
     */
    private function parseRelativeLinks()
    {
        $matched = preg_match_all('/^(Link|link): *(.*)$/m', $this->responseHeaders, $matches);
        if ($matched) {
            for ($i = 0; $i < $matched; $i++) {
                $links = explode(',', $matches[2][$i]);
                foreach ($links as $link) {
                    if (preg_match('/^\<([^\>]+)\>; *rel=([^ ]+)$/', trim($link), $match)) {
                        $rel = strtolower(utf8_decode($match[2]));
                        if ((strpos($rel, '"') === 0) || (strpos($rel, '?') === 0)) {
                            $rel = substr($rel, 1, strlen($rel) - 2);
                        }
                        if ($rel === 'previous') {
                            $rel = 'prev';
                        }
                        $this->relativeLinks[$rel] = $match[1];
                    }
                }
            }
        }
    }

}
