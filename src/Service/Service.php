<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Service;

use ceLTIc\LTI\AccessToken;
use ceLTIc\LTI\Platform;
use ceLTIc\LTI\Http\HttpMessage;
use ceLTIc\LTI\Util;

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
    public bool $unsigned = false;

    /**
     * Service endpoint.
     *
     * @var string|null $endpoint
     */
    protected ?string $endpoint = null;

    /**
     * Service access scope.
     *
     * @var string|null $scope
     */
    protected ?string $scope = null;

    /**
     * Media type of message body.
     *
     * @var string|null $mediaType
     */
    protected ?string $mediaType = null;

    /**
     * Platform for this service request.
     *
     * @var Platform|null $platform
     */
    private ?Platform $platform = null;

    /**
     * HttpMessage object for last service request.
     *
     * @var HttpMessage|null $http
     */
    private ?HttpMessage $http = null;

    /**
     * Class constructor.
     *
     * @param Platform $platform     Platform object for this service request
     * @param string|null $endpoint  Service endpoint
     */
    public function __construct(Platform $platform, ?string $endpoint)
    {
        $this->platform = $platform;
        $this->endpoint = $endpoint;
    }

    /**
     * Get platform.
     *
     * @return Platform  Platform for this service
     */
    public function getPlatform(): Platform
    {
        return $this->platform;
    }

    /**
     * Get access scope.
     *
     * @return string  Access scope
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * Send a service request.
     *
     * @param string $method          The action type constant (optional, default is GET)
     * @param array|null $parameters  Query parameters to add to endpoint (optional, default is none)
     * @param string $body            Body of request (optional, default is null)
     *
     * @return HttpMessage HTTP object containing request and response details
     */
    public function send(string $method, ?array $parameters = [], string $body = ''): HttpMessage
    {
        $url = $this->endpoint;
        if (!empty($parameters)) {
            if (strpos($url, '?') === false) {
                $sep = '?';
            } else {
                $sep = '&';
            }
            foreach ($parameters as $name => $value) {
                $url .= $sep . Util::urlEncode(strval($name)) . '=' . Util::urlEncode(strval($value));
                $sep = '&';
            }
        }
        $header = null;
        $retry = !$this->platform->useOAuth1();
        $newToken = false;
        $retried = false;
        do {
            if (!$this->unsigned) {
                $accessToken = $this->platform->getAccessToken();
                if (!$this->platform->useOAuth1()) {
                    if (empty($accessToken)) {
                        $accessToken = new AccessToken($this->platform);
                        $this->platform->setAccessToken($accessToken);
                    }
                    if (!$accessToken->hasScope($this->scope)) {
                        $accessToken->get($this->scope);
                        $newToken = true;
                        if (!$accessToken->hasScope($this->scope)) {  // Try obtaining a token for just this scope
                            $accessToken->expires = time();
                            $accessToken->get($this->scope, true);
                            $retried = true;
                            if (!$accessToken->hasScope($this->scope)) {
                                if (empty($this->http)) {
                                    $this->http = new HttpMessage($url);
                                    $this->http->error = "Unable to obtain an access token for scope: {$this->scope}";
                                }
                                break;
                            }
                        }
                    }
                }
                $header = $this->platform->signServiceRequest($url, $method, $this->mediaType, $body);
            }
// Connect to platform and parse JSON response
            $this->http = new HttpMessage($url, $method, $body, $header);
            if ($this->http->send() && !empty($this->http->response)) {
                $this->http->responseJson = Util::jsonDecode($this->http->response);
                $this->http->ok = !is_null($this->http->responseJson);
            }
            $retry = $retry && !$retried && !$this->http->ok;
            if ($retry) {
                if (!$newToken) {  // Invalidate existing token to force a new one to be obtained
                    $accessToken->expires = time();
                    $newToken = true;
                } elseif (count($accessToken->scopes) !== 1) {  // Try obtaining a token for just this scope
                    $accessToken->expires = time();
                    $accessToken->get($this->scope, true);
                    $retried = true;
                } else {
                    $retry = false;
                }
            }
        } while ($retry);

        return $this->http;
    }

    /**
     * Get HttpMessage object for last request.
     *
     * @return HttpMessage  HTTP object containing request and response details
     */
    public function getHttpMessage(): ?HttpMessage
    {
        return $this->http;
    }

###
###  PROTECTED METHODS
###

    /**
     * Parse the JSON for context references.
     *
     * @param object|array $contexts  JSON contexts
     * @param array $arr              Array to be parsed
     *
     * @return array Parsed array
     */
    protected function parseContextsInArray(object|array $contexts, array $arr): array
    {
        if (is_array($contexts)) {
            $contextdefs = [];
            foreach ($contexts as $context) {
                if (is_object($context)) {
                    $contextdefs = array_merge(get_object_vars($context), $contexts);
                }
            }
            $parsed = [];
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
