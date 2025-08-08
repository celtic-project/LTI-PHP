<?php
declare(strict_types=1);

namespace ceLTIc\LTI;

use ceLTIc\LTI\Tool;
use ceLTIc\LTI\Http\HttpMessage;
use ceLTIc\LTI\Util;

/**
 * Class to represent an HTTP message
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @version  3.0.0
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class AccessToken
{

    /**
     * Access token string.
     *
     * @var string|null $token
     */
    public ?string $token = null;

    /**
     * Timestamp at which the token string expires.
     *
     * @var int|null $expires
     */
    public ?int $expires = null;

    /**
     * Scope(s) for which the access token is valid.
     *
     * @var array $scopes
     */
    public array $scopes = [];

    /**
     * Platform for this token.
     *
     * @var Platform $platform
     */
    private Platform $platform;

    /**
     * Timestamp for when the object was created.
     *
     * @var int|null $created
     */
    public ?int $created = null;

    /**
     * Timestamp for when the object was last updated.
     *
     * @var int|null $updated
     */
    public ?int $updated = null;

    /**
     * Class constructor.
     *
     * @param Platform $platform  Platform
     * @param array|null $scopes  Scopes for which the access token is valid
     * @param string|null $token  Access token string
     * @param int|null $expires   Time in seconds after which the token string will expire
     */
    public function __construct(Platform $platform, ?array $scopes = [], ?string $token = null, ?int $expires = null)
    {
        $this->platform = $platform;
        if (is_null($scopes)) {
            $scopes = [];
        }
        $this->scopes = $scopes;
        if (!empty($token)) {
            $this->token = $token;
        }
        if (!empty($expires)) {
            $this->expires = time() + $expires;
        }
        $this->created = null;
        $this->updated = null;
        if (empty($scopes)) {
            $this->load();
        }
    }

    /**
     * Get platform.
     *
     * @return Platform  Platform object for this resource link.
     */
    public function getPlatform(): Platform
    {
        return $this->platform;
    }

    /**
     * Load a nonce value from the database.
     *
     * @return bool  True if the nonce value was successfully loaded
     */
    public function load(): bool
    {
        return $this->platform->getDataConnector()->loadAccessToken($this);
    }

    /**
     * Save a nonce value in the database.
     *
     * @return bool  True if the nonce value was successfully saved
     */
    public function save(): bool
    {
        sort($this->scopes);
        return $this->platform->getDataConnector()->saveAccessToken($this);
    }

    /**
     * Check if a valid access token exists for a specific scope (or any scope if none specified).
     *
     * @param string $scope  Access scope
     *
     * @return bool  True if there is an unexpired access token for specified scope
     */
    public function hasScope(string $scope = ''): bool
    {
        if (str_ends_with($scope, '.readonly')) {
            $scope2 = substr($scope, 0, -9);
        } else {
            $scope2 = $scope;
        }
        return !empty($this->token) && (empty($this->expires) || ($this->expires > time())) &&
            (empty($scope) || empty($this->scopes) || (in_array($scope, $this->scopes) || in_array($scope2, $this->scopes)));
    }

    /**
     * Obtain a valid access token for a scope.
     *
     * @param string $scope    Access scope
     * @param bool $scopeOnly  If true, a token is requested just for the specified scope
     *
     * @return AccessToken  New access token
     */
    public function get(string $scope = '', bool $scopeOnly = false): AccessToken
    {
        $this->scopes = [];
        $this->token = null;
        $this->expires = null;
        $this->created = null;
        $this->updated = null;

        $url = $this->platform->accessTokenUrl;
        if (empty($url)) {
            Util::logError('Unable to obtain an access token from platform: no URL found');
        } elseif (empty(Tool::$defaultTool)) {
            Util::logError('Unable to obtain an access token from platform: default tool not defined');
        } elseif (empty(Tool::$defaultTool->rsaKey)) {
            Util::logError('Unable to obtain an access token from platform: no private key set for default tool');
        } else {
            if ($scopeOnly) {
                $scopesRequested = [$scope];
            } else {
                $scopesRequested = Tool::$defaultTool->requiredScopes;
                if (str_ends_with($scope, '.readonly')) {
                    $scope2 = substr($scope, 0, -9);
                } else {
                    $scope2 = $scope;
                }
                if (!empty($scope) && !in_array($scope, $scopesRequested) && !in_array($scope2, $scopesRequested)) {
                    $scopesRequested[] = $scope;
                }
            }
            if (!empty($scopesRequested)) {
                $retry = false;
                do {
                    $method = 'POST';
                    $type = 'application/x-www-form-urlencoded';
                    $body = [
                        'grant_type' => 'client_credentials',
                        'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
                        'scope' => implode(' ', $scopesRequested)
                    ];
                    if (!empty(Tool::$defaultTool)) {
                        Tool::$defaultTool->platform = $this->platform;
                        $body = Tool::$defaultTool->signServiceRequest($url, $method, $type, $body);
                    } else {
                        $body = $this->platform->signServiceRequest($url, $method, $type, $body);
                    }
                    $http = new HttpMessage($url, $method, $body, 'Accept: application/json');
                    if ($http->send() && !empty($http->response)) {
                        $http->responseJson = Util::jsonDecode($http->response);
                        if (!is_null($http->responseJson) && !empty($http->responseJson->access_token) && !empty($http->responseJson->expires_in)) {
                            if (isset($http->responseJson->scope)) {
                                $scopesAccepted = explode(' ', $http->responseJson->scope);
                            } else {
                                $scopesAccepted = $scopesRequested;
                            }
                            $this->scopes = $scopesAccepted;
                            $this->token = $http->responseJson->access_token;
                            $this->expires = time() + $http->responseJson->expires_in;
                            if (!$scopeOnly) {
                                $this->save();
                            }
                        }
                        $retry = false;
                    } elseif ($retry) {
                        $retry = false;
                    } elseif (!empty($scope) && (count($scopesRequested) > 1)) {  // Just ask for the single scope requested
                        $retry = true;
                        $scopesRequested = [$scope];
                    }
                } while ($retry);
            }
        }

        return $this;
    }

}
