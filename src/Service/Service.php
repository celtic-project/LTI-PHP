<?php

namespace ceLTIc\LTI\Service;

use ceLTIc\LTI;
use ceLTIc\LTI\ToolConsumer;
use ceLTIc\LTI\Http\HttpMessage;

/**
 * Class to implement a service
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class Service
{

    /**
     * Whether service request should be sent unsigned.
     *
     * @var bool $unsigned
     */
    public $unsigned = false;

    /**
     * Service endpoint.
     *
     * @var string $endpoint
     */
    protected $endpoint;

    /**
     * Tool Consumer for this service request.
     *
     * @var ToolConsumer $consumer
     */
    private $consumer;

    /**
     * Media type of message body.
     *
     * @var string $mediaType
     */
    private $mediaType;

    /**
     * HttpMessage object for last service request.
     *
     * @var HttpMessage|null $http
     */
    private $http = null;

    /**
     * Class constructor.
     *
     * @param ToolConsumer $consumer   Tool consumer object for this service request
     * @param string       $endpoint   Service endpoint
     * @param string       $mediaType  Media type of message body
     */
    public function __construct($consumer, $endpoint, $mediaType)
    {
        $this->consumer = $consumer;
        $this->endpoint = $endpoint;
        $this->mediaType = $mediaType;
    }

    /**
     * Send a service request.
     *
     * @param string  $method      The action type constant (optional, default is GET)
     * @param array   $parameters  Query parameters to add to endpoint (optional, default is none)
     * @param string  $body        Body of request (optional, default is null)
     *
     * @return HttpMessage HTTP object containing request and response details
     */
    public function send($method, $parameters = array(), $body = null)
    {
        $url = $this->endpoint;
        if (!empty($parameters)) {
            if (strpos($url, '?') === false) {
                $sep = '?';
            } else {
                $sep = '&';
            }
            foreach ($parameters as $name => $value) {
                $url .= $sep . urlencode($name) . '=' . urlencode($value);
                $sep = '&';
            }
        }
        if (!$this->unsigned) {
            $header = $this->consumer->signServiceRequest($url, $method, $this->mediaType, $body);
        } else {
            $header = null;
        }

// Connect to tool consumer
        $this->http = new HttpMessage($url, $method, $body, $header);
// Parse JSON response
        if ($this->http->send() && !empty($this->http->response)) {
            $this->http->responseJson = json_decode($this->http->response);
            $this->http->ok = !is_null($this->http->responseJson);
        }

        return $this->http;
    }

    /**
     * Get HttpMessage object for last request.
     *
     * @return HttpMessage HTTP object containing request and response details
     */
    public function getHttpMessage()
    {
        return $this->http;
    }

###
###  PROTECTED METHODS
###

    /**
     * Parse the JSON for context references.
     *
     * @param object       $contexts   JSON contexts
     * @param array        $arr        Array to be parsed
     *
     * @return array Parsed array
     */
    protected function parseContextsInArray($contexts, $arr)
    {
        if (is_array($contexts)) {
            $contextdefs = array();
            foreach ($contexts as $context) {
                if (is_object($context)) {
                    $contextdefs = array_merge(get_object_vars($context), $contexts);
                }
            }
            $parsed = array();
            foreach ($arr as $key => $value) {
                $parts = explode(':', $value, 2);
                if (count($parts) > 1) {
                    if (array_key_exists($parts[0], $contextdefs)) {
                        $parsed[$key] = $contextdefs[$parts[0]] . $parts[1];
                        break;
                    }
                }
                $parsed[$key] = $value;
            }
        } else {
            $parsed = $arr;
        }

        return $parsed;
    }

}
