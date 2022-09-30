<?php

namespace ceLTIc\LTI;

use ceLTIc\LTI\DataConnector\DataConnector;
use ceLTIc\LTI\Content\Item;
use ceLTIc\LTI\Http\HttpMessage;
use ceLTIc\LTI\OAuth;
use ceLTIc\LTI\Jwt\Jwt;
use ceLTIc\LTI\Jwt\ClientInterface;
use ceLTIc\LTI\Tool;

/**
 * Class to represent an LTI system
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
trait System
{

    /**
     * True if the last request was successful.
     *
     * @var bool $ok
     */
    public $ok = true;

    /**
     * Shared secret.
     *
     * @var string|null $secret
     */
    public $secret = null;

    /**
     * Method used for signing messages.
     *
     * @var string $signatureMethod
     */
    public $signatureMethod = 'HMAC-SHA1';

    /**
     * Algorithm used for encrypting messages.
     *
     * @var string $encryptionMethod
     */
    public $encryptionMethod = '';

    /**
     * Data connector object.
     *
     * @var DataConnector|null $dataConnector
     */
    public $dataConnector = null;

    /**
     * RSA key in PEM or JSON format.
     *
     * Set to the private key for signing outgoing messages and service requests, and to the public key
     * for verifying incoming messages and service requests.
     *
     * @var string|null $rsaKey
     */
    public $rsaKey = null;

    /**
     * Scopes to request when obtaining an access token.
     *
     * @var array  $requiredScopes
     */
    public $requiredScopes = array();

    /**
     * Key ID.
     *
     * @var string|null $kid
     */
    public $kid = null;

    /**
     * Endpoint for public key.
     *
     * @var string|null $jku
     */
    public $jku = null;

    /**
     * Error message for last request processed.
     *
     * @var string|null $reason
     */
    public $reason = null;

    /**
     * Details for error message relating to last request processed.
     *
     * @var array $details
     */
    public $details = array();

    /**
     * Whether debug level messages are to be reported.
     *
     * @var bool $debugMode
     */
    public $debugMode = false;

    /**
     * Whether the system instance is enabled to accept connection requests.
     *
     * @var bool $enabled
     */
    public $enabled = false;

    /**
     * Timestamp from which the the system instance is enabled to accept connection requests.
     *
     * @var int|null $enableFrom
     */
    public $enableFrom = null;

    /**
     * Timestamp until which the system instance is enabled to accept connection requests.
     *
     * @var int|null $enableUntil
     */
    public $enableUntil = null;

    /**
     * Timestamp for date of last connection to this system.
     *
     * @var int|null $lastAccess
     */
    public $lastAccess = null;

    /**
     * Timestamp for when the object was created.
     *
     * @var int|null $created
     */
    public $created = null;

    /**
     * Timestamp for when the object was last updated.
     *
     * @var int|null $updated
     */
    public $updated = null;

    /**
     * JWT object, if any.
     *
     * @var JWS|null $jwt
     */
    protected $jwt = null;

    /**
     * Raw message parameters.
     *
     * @var array $rawParameters
     */
    protected $rawParameters = null;

    /**
     * LTI message parameters.
     *
     * @var array|null $messageParameters
     */
    protected $messageParameters = null;

    /**
     * System ID value.
     *
     * @var int|null $id
     */
    private $id = null;

    /**
     * Consumer key/client ID value.
     *
     * @var string|null $key
     */
    private $key = null;

    /**
     * Setting values (LTI parameters, custom parameters and local parameters).
     *
     * @var array $settings
     */
    private $settings = null;

    /**
     * Whether the settings value have changed since last saved.
     *
     * @var bool $settingsChanged
     */
    private $settingsChanged = false;

    /**
     * Get the system record ID.
     *
     * @return int|null  System record ID value
     */
    public function getRecordId()
    {
        return $this->id;
    }

    /**
     * Sets the system record ID.
     *
     * @param int $id  System record ID value
     */
    public function setRecordId($id)
    {
        $this->id = $id;
    }

    /**
     * Get the consumer key.
     *
     * @return string  Consumer key value
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set the consumer key.
     *
     * @param string $key  Consumer key value
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Get a setting value.
     *
     * @param string $name    Name of setting
     * @param string $default Value to return if the setting does not exist (optional, default is an empty string)
     *
     * @return string Setting value
     */
    public function getSetting($name, $default = '')
    {
        if (array_key_exists($name, $this->settings)) {
            $value = $this->settings[$name];
        } else {
            $value = $default;
        }

        return $value;
    }

    /**
     * Set a setting value.
     *
     * @param string $name  Name of setting
     * @param string $value Value to set, use an empty value to delete a setting (optional, default is null)
     */
    public function setSetting($name, $value = null)
    {
        $old_value = $this->getSetting($name);
        if ($value !== $old_value) {
            if (!empty($value)) {
                $this->settings[$name] = $value;
            } else {
                unset($this->settings[$name]);
            }
            $this->settingsChanged = true;
        }
    }

    /**
     * Get an array of all setting values.
     *
     * @return array Associative array of setting values
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Set an array of all setting values.
     *
     * @param array $settings  Associative array of setting values
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;
    }

    /**
     * Save setting values.
     *
     * @return bool    True if the settings were successfully saved
     */
    public function saveSettings()
    {
        if ($this->settingsChanged) {
            $ok = $this->save();
        } else {
            $ok = true;
        }

        return $ok;
    }

    /**
     * Check whether a JWT exists
     *
     * @return bool True if a JWT exists
     */
    public function hasJwt()
    {
        return !empty($this->jwt) && $this->jwt->hasJwt();
    }

    /**
     * Get the JWT
     *
     * @return ClientInterface The JWT
     */
    public function getJwt()
    {
        return $this->jwt;
    }

    /**
     * Get the raw POST parameters
     *
     * @return array The POST parameter array
     */
    public function getRawParameters()
    {
        if (is_null($this->rawParameters)) {
            $this->rawParameters = OAuth\OAuthUtil::parse_parameters(file_get_contents(OAuth\OAuthRequest::$POST_INPUT));
        }

        return $this->rawParameters;
    }

    /**
     * Get the message claims
     *
     * @param bool $fullyQualified  True if claims should be fully qualified rather than grouped (default is false)
     *
     * @return array The message claim array
     */
    public function getMessageClaims($fullyQualified = false)
    {
        $messageClaims = null;
        if (!is_null($this->messageParameters)) {
            $messageParameters = $this->messageParameters;
            $messageType = '';
            if (!empty($messageParameters['lti_message_type'])) {
                if (array_key_exists($messageParameters['lti_message_type'], Util::MESSAGE_TYPE_MAPPING)) {
                    $messageParameters['lti_message_type'] = Util::MESSAGE_TYPE_MAPPING[$messageParameters['lti_message_type']];
                }
                $messageType = $messageParameters['lti_message_type'];
            }
            if (!empty($messageParameters['accept_media_types'])) {
                $mediaTypes = array_map('trim', explode(',', $messageParameters['accept_media_types']));
                $mediaTypes = array_filter($mediaTypes);
                $types = array();
                if (!empty($messageParameters['accept_types'])) {
                    $types = array_map('trim', explode(',', $this->messageParameters['accept_types']));
                    $types = array_filter($types);
                    foreach ($mediaTypes as $mediaType) {
                        if (strpos($mediaType, 'application/vnd.ims.lti.') === 0) {
                            unset($mediaTypes[array_search($mediaType, $mediaTypes)]);
                        }
                    }
                    $messageParameters['accept_media_types'] = implode(',', $mediaTypes);
                } else {
                    foreach ($mediaTypes as $mediaType) {
                        if ($mediaType === Item::LTI_LINK_MEDIA_TYPE) {
                            unset($mediaTypes[array_search(Item::LTI_LINK_MEDIA_TYPE, $mediaTypes)]);
                            $messageParameters['accept_media_types'] = implode(',', $mediaTypes);
                            $types[] = Item::TYPE_LTI_LINK;
                        } elseif ($mediaType === Item::LTI_ASSIGNMENT_MEDIA_TYPE) {
                            unset($mediaTypes[array_search(Item::LTI_ASSIGNMENT_MEDIA_TYPE, $mediaTypes)]);
                            $messageParameters['accept_media_types'] = implode(',', $mediaTypes);
                            $types[] = Item::TYPE_LTI_ASSIGNMENT;
                        } elseif (substr($mediaType, 0, 6) === 'image/') {
                            $types[] = 'image';
                            $types[] = 'link';
                            $types[] = 'file';
                        } elseif ($mediaType === 'text/html') {
                            $types[] = 'html';
                            $types[] = 'link';
                            $types[] = 'file';
                        } elseif ($mediaType === '*/*') {
                            $types[] = 'html';
                            $types[] = 'image';
                            $types[] = 'file';
                            $types[] = 'link';
                        } else {
                            $types[] = 'file';
                        }
                    }
                    $types = array_unique($types);
                    $messageParameters['accept_types'] = implode(',', $types);
                }
            }
            if (!empty($messageParameters['accept_presentation_document_targets'])) {
                $documentTargets = array_map('trim', explode(',', $messageParameters['accept_presentation_document_targets']));
                $documentTargets = array_filter($documentTargets);
                $targets = array();
                foreach ($documentTargets as $documentTarget) {
                    switch ($documentTarget) {
                        case 'frame':
                        case 'popup':
                        case 'overlay':
                        case 'none':
                            break;
                        default:
                            $targets[] = $documentTarget;
                            break;
                    }
                }
                $targets = array_unique($targets);
                $messageParameters['accept_presentation_document_targets'] = implode(',', $targets);
            }
            $messageClaims = array();
            if (!empty($messageParameters['oauth_consumer_key'])) {
                $messageClaims['aud'] = array($messageParameters['oauth_consumer_key']);
            }
            foreach ($messageParameters as $key => $value) {
                $ok = true;
                if (array_key_exists($key, Util::JWT_CLAIM_MAPPING)) {
                    if (array_key_exists("{$key}.{$messageType}", Util::JWT_CLAIM_MAPPING)) {
                        $mapping = Util::JWT_CLAIM_MAPPING["{$key}.{$messageType}"];
                    } else {
                        $mapping = Util::JWT_CLAIM_MAPPING[$key];
                    }
                    if (isset($mapping['isObject']) && $mapping['isObject']) {
                        $value = json_decode($value);
                    } elseif (isset($mapping['isArray']) && $mapping['isArray']) {
                        $value = array_map('trim', explode(',', $value));
                        $value = array_filter($value);
                        sort($value);
                    } elseif (isset($mapping['isBoolean']) && $mapping['isBoolean']) {
                        $value = (is_bool($value)) ? $value : $value === 'true';
                    } elseif (isset($mapping['isInteger']) && $mapping['isInteger']) {
                        $value = intval($value);
                    } elseif (is_bool($value)) {
                        $value = ($value) ? 'true' : 'false';
                    } else {
                        $value = strval($value);
                    }
                    $group = '';
                    $claim = Util::JWT_CLAIM_PREFIX;
                    if (!empty($mapping['suffix'])) {
                        $claim .= "-{$mapping['suffix']}";
                    }
                    $claim .= '/claim/';
                    if (is_null($mapping['group'])) {
                        $claim = $mapping['claim'];
                    } elseif (empty($mapping['group'])) {
                        $claim .= $mapping['claim'];
                    } else {
                        $group = $claim . $mapping['group'];
                        $claim = $mapping['claim'];
                    }
                } elseif (substr($key, 0, 7) === 'custom_') {
                    $group = Util::JWT_CLAIM_PREFIX . '/claim/custom';
                    $claim = substr($key, 7);
                } elseif (substr($key, 0, 4) === 'ext_') {
                    if ($key === 'ext_d2l_username') {
                        $group = 'http://www.brightspace.com';
                        $claim = 'username';
                    } else {
                        $group = Util::JWT_CLAIM_PREFIX . '/claim/ext';
                        $claim = substr($key, 4);
                    }
                } elseif (substr($key, 0, 7) === 'lti1p1_') {
                    $group = Util::JWT_CLAIM_PREFIX . '/claim/lti1p1';
                    $claim = substr($key, 7);
                    if (empty($value)) {
                        $value = null;
                    } else {
                        $json = json_decode($value);
                        if (!is_null($json)) {
                            $value = $json;
                        }
                    }
                } else {
                    $ok = false;
                }
                if ($ok) {
                    if ($fullyQualified) {
                        if (empty($group)) {
                            $messageClaims = array_merge($messageClaims, self::fullyQualifyClaim($claim, $value));
                        } else {
                            $messageClaims = array_merge($messageClaims, self::fullyQualifyClaim("{$group}/{$claim}", $value));
                        }
                    } elseif (empty($group)) {
                        $messageClaims[$claim] = $value;
                    } else {
                        $messageClaims[$group][$claim] = $value;
                    }
                }
            }
            if (!empty($messageParameters['unmapped_claims'])) {
                $claims = json_decode($messageParameters['unmapped_claims']);
                foreach ($claims as $claim => $value) {
                    if ($fullyQualified) {
                        $messageClaims = array_merge($messageClaims, self::fullyQualifyClaim($claim, $value));
                    } elseif (!is_object($value)) {
                        $messageClaims[$claim] = $value;
                    } elseif (!isset($messageClaims[$claim])) {
                        $messageClaims[$claim] = $value;
                    } else {
                        $objVars = get_object_vars($value);
                        foreach ($objVars as $attrName => $attrValue) {
                            if (is_object($messageClaims[$claim])) {
                                $messageClaims[$claim]->{$attrName} = $attrValue;
                            } else {
                                $messageClaims[$claim][$attrName] = $attrValue;
                            }
                        }
                    }
                }
            }
        }

        return $messageClaims;
    }

    /**
     * Get an array of fully qualified user roles
     *
     * @param mixed  $roles       Comma-separated list of roles or array of roles
     * @param string $ltiVersion  LTI version (default is LTI-1p0)
     *
     * @return array Array of roles
     */
    public static function parseRoles($roles, $ltiVersion = Util::LTI_VERSION1)
    {
        if (!is_array($roles)) {
            $roles = array_map('trim', explode(',', $roles));
            $roles = array_filter($roles);
        }
        $parsedRoles = array();
        foreach ($roles as $role) {
            $role = trim($role);
            if (!empty($role)) {
                if ($ltiVersion === Util::LTI_VERSION1) {
                    if ((substr($role, 0, 4) !== 'urn:') &&
                        (substr($role, 0, 7) !== 'http://') && (substr($role, 0, 8) !== 'https://')) {
                        $role = 'urn:lti:role:ims/lis/' . $role;
                    }
                } elseif ((substr($role, 0, 7) !== 'http://') && (substr($role, 0, 8) !== 'https://')) {
                    $role = 'http://purl.imsglobal.org/vocab/lis/v2/membership#' . $role;
                }
                $parsedRoles[] = $role;
            }
        }

        return $parsedRoles;
    }

    /**
     * Add the signature to an LTI message.
     *
     * @param string  $url         URL for message request
     * @param string  $type        LTI message type
     * @param string  $version     LTI version
     * @param array   $params      Message parameters
     *
     * @return array|string  Array of signed message parameters or request headers
     */
    public function signParameters($url, $type, $version, $params)
    {
        if (!empty($url)) {
// Add standard parameters
            $params['lti_version'] = $version;
            $params['lti_message_type'] = $type;
// Add signature
            $params = $this->addSignature($url, $params, 'POST', 'application/x-www-form-urlencoded');
        }

        return $params;
    }

    /**
     * Add the signature to an LTI message.
     *
     * If the message is being sent from a platform using LTI 1.3, then the parameters and URL will be saved and replaced with an
     * initiate login request.
     *
     * @param string  $url             URL for message request
     * @param string  $type            LTI message type
     * @param string  $version         LTI version
     * @param array   $params          Message parameters
     * @param string  $loginHint       ID of user (optional)
     * @param string  $ltiMessageHint  LTI message hint (optional, use null for none)
     *
     * @return array|string  Array of signed message parameters or request headers
     */
    public function signMessage(&$url, $type, $version, $params, $loginHint = null, $ltiMessageHint = null)
    {
        if (($this instanceof Platform) && ($this->ltiVersion === Util::LTI_VERSION1P3)) {
            if (!isset($loginHint) || (strlen($loginHint) <= 0)) {
                if (isset($params['user_id']) && (strlen($params['user_id']) > 0)) {
                    $loginHint = $params['user_id'];
                } else {
                    $loginHint = 'Anonymous';
                }
            }
// Add standard parameters
            $params['lti_version'] = $version;
            $params['lti_message_type'] = $type;
            $this->onInitiateLogin($url, $loginHint, $ltiMessageHint, $params);

            $params = array(
                'iss' => $this->platformId,
                'target_link_uri' => $url,
                'login_hint' => $loginHint
            );
            if (!is_null($ltiMessageHint)) {
                $params['lti_message_hint'] = $ltiMessageHint;
            }
            if (!empty($this->clientId)) {
                $params['client_id'] = $this->clientId;
            }
            if (!empty($this->deploymentId)) {
                $params['lti_deployment_id'] = $this->deploymentId;
            }
            if (!empty(Tool::$defaultTool)) {
                $url = Tool::$defaultTool->initiateLoginUrl;
            }
        } else {
            $params = $this->signParameters($url, $type, $version, $params);
        }

        return $params;
    }

    /**
     * Generate a web page containing an auto-submitted form of LTI message parameters.
     *
     * @param string $url              URL to which the form should be submitted
     * @param string $type             LTI message type
     * @param array  $messageParams    Array of form parameters
     * @param string $target           Name of target (optional)
     * @param string $userId           ID of user (optional)
     * @param string $hint             LTI message hint (optional, use null for none)
     *
     * @return string
     */
    public function sendMessage($url, $type, $messageParams, $target = '', $userId = null, $hint = '')
    {
        $sendParams = $this->signMessage($url, $type, $this->ltiVersion, $messageParams, $userId, $hint);
        $html = Util::sendForm($url, $sendParams, $target);

        return $html;
    }

    /**
     * Generates the headers for an LTI service request.
     *
     * @param string  $url         URL for message request
     * @param string  $method      HTTP method
     * @param string  $type        Media type
     * @param string  $data        Data being passed in request body (optional)
     *
     * @return string Headers to include with service request
     */
    public function signServiceRequest($url, $method, $type, $data = null)
    {
        $header = '';
        if (!empty($url)) {
            $header = $this->addSignature($url, $data, $method, $type);
        }

        return $header;
    }

    /**
     * Perform a service request
     *
     * @param object $service  Service object to be executed
     * @param string $method   HTTP action
     * @param string $format   Media type
     * @param mixed  $data     Array of parameters or body string
     *
     * @return HttpMessage HTTP object containing request and response details
     */
    public function doServiceRequest($service, $method, $format, $data)
    {
        $header = $this->addSignature($service->endpoint, $data, $method, $format);

// Connect to platform
        $http = new HttpMessage($service->endpoint, $method, $data, $header);
// Parse JSON response
        if ($http->send() && !empty($http->response)) {
            $http->responseJson = json_decode($http->response);
            $http->ok = !is_null($http->responseJson);
        }

        return $http;
    }

    /**
     * Determine whether this consumer is using the OAuth 1 security model.
     *
     * @return bool  True if OAuth 1 security model should be used
     */
    public function useOAuth1()
    {
        return empty($this->signatureMethod) || (substr($this->signatureMethod, 0, 2) !== 'RS');
    }

    /**
     * Add the signature to an array of message parameters or to a header string.
     *
     * @param string $endpoint          URL to which message is being sent
     * @param mixed $data               Data to be passed
     * @param string $method            HTTP method
     * @param string|null $type         Content type of data being passed
     * @param string|null $nonce        Nonce value for JWT
     * @param string|null $hash         OAuth body hash value
     * @param int|null $timestamp       Timestamp
     *
     * @return mixed Array of signed message parameters or header string
     */
    public function addSignature($endpoint, $data, $method = 'POST', $type = null, $nonce = '', $hash = null, $timestamp = null)
    {
        if ($this->useOAuth1()) {
            return $this->addOAuth1Signature($endpoint, $data, $method, $type, $hash, $timestamp);
        } else {
            return $this->addJWTSignature($endpoint, $data, $method, $type, $nonce, $timestamp);
        }
    }

    /**
     * Verify the required properties of an LTI message.
     *
     * @return bool  True if it is a valid LTI message
     */
    public function checkMessage()
    {
        $ok = $_SERVER['REQUEST_METHOD'] === 'POST';
        if (!$ok) {
            $this->reason = 'LTI messages must use HTTP POST';
        } elseif (!empty($this->jwt) && !empty($this->jwt->hasJwt())) {
            $ok = false;
            if (is_null($this->messageParameters['oauth_consumer_key']) || (strlen($this->messageParameters['oauth_consumer_key']) <= 0)) {
                $this->reason = 'Missing iss claim';
            } elseif (empty($this->jwt->getClaim('iat', ''))) {
                $this->reason = 'Missing iat claim';
            } elseif (empty($this->jwt->getClaim('exp', ''))) {
                $this->reason = 'Missing exp claim';
            } elseif (intval($this->jwt->getClaim('iat')) > intval($this->jwt->getClaim('exp'))) {
                $this->reason = 'iat claim must not have a value greater than exp claim';
            } elseif (empty($this->jwt->getClaim('nonce', ''))) {
                $this->reason = 'Missing nonce claim';
            } else {
                $ok = true;
            }
        }
// Set signature method from request
        if (isset($this->messageParameters['oauth_signature_method'])) {
            $this->signatureMethod = $this->messageParameters['oauth_signature_method'];
            if (($this instanceof Tool) && !empty($this->platform)) {
                $this->platform->signatureMethod = $this->signatureMethod;
            }
        }
// Check all required launch parameters
        if ($ok) {
            $ok = isset($this->messageParameters['lti_message_type']);
            if (!$ok) {
                $this->reason = 'Missing lti_message_type parameter.';
            }
        }
        if ($ok) {
            $ok = isset($this->messageParameters['lti_version']) && in_array($this->messageParameters['lti_version'],
                    Util::$LTI_VERSIONS);
            if (!$ok) {
                $this->reason = 'Invalid or missing lti_version parameter.';
            }
        }

        return $ok;
    }

    /**
     * Verify the signature of a message.
     *
     * @return bool  True if the signature is valid
     */
    public function verifySignature()
    {
        $ok = false;
        $key = $this->key;
        if (!empty($key)) {
            $secret = $this->secret;
        } elseif (($this instanceof Tool) && !empty($this->platform)) {
            $key = $this->platform->getKey();
            $secret = $this->platform->secret;
        } elseif (($this instanceof Platform) && !empty(Tool::$defaultTool)) {
            $key = Tool::$defaultTool->getKey();
            $secret = Tool::$defaultTool->secret;
        }
        if ($this instanceof Tool) {
            $platform = $this->platform;
            $publicKey = $this->platform->rsaKey;
            $jku = $this->platform->jku;
        } else {
            $platform = $this;
            if (!empty(Tool::$defaultTool)) {
                $publicKey = Tool::$defaultTool->rsaKey;
                $jku = Tool::$defaultTool->jku;
            } else {
                $publicKey = $this->rsaKey;
                $jku = $this->jku;
            }
        }
        if (empty($this->jwt) || empty($this->jwt->hasJwt())) {  // OAuth-signed message
            try {
                $store = new OAuthDataStore($this);
                $server = new OAuth\OAuthServer($store);
                $method = new OAuth\OAuthSignatureMethod_HMAC_SHA224();
                $server->add_signature_method($method);
                $method = new OAuth\OAuthSignatureMethod_HMAC_SHA256();
                $server->add_signature_method($method);
                $method = new OAuth\OAuthSignatureMethod_HMAC_SHA384();
                $server->add_signature_method($method);
                $method = new OAuth\OAuthSignatureMethod_HMAC_SHA512();
                $server->add_signature_method($method);
                $method = new OAuth\OAuthSignatureMethod_HMAC_SHA1();
                $server->add_signature_method($method);
                $request = OAuth\OAuthRequest::from_request();
                if (isset($request->get_parameters()['_new_window']) && !isset($this->messageParameters['_new_window'])) {
                    $request->unset_parameter('_new_window');
                }
                $server->verify_request($request);
                $ok = true;
            } catch (\Exception $e) {
                if (empty($this->reason)) {
                    $oauthConsumer = new OAuth\OAuthConsumer($key, $secret);
                    $signature = $request->build_signature($method, $oauthConsumer, false);
                    if ($this->debugMode) {
                        $this->reason = $e->getMessage();
                    }
                    if (empty($this->reason)) {
                        $this->reason = 'OAuth signature check failed - perhaps an incorrect secret or timestamp.';
                    }
                    $this->details[] = "Shared secret: '{$secret}'";
                    $this->details[] = 'Current timestamp: ' . time();
                    $this->details[] = "Expected signature: {$signature}";
                    $this->details[] = "Base string: {$request->base_string}";
                }
            }
        } else {  // JWT-signed message
            $nonce = new PlatformNonce($platform, $this->jwt->getClaim('nonce'));
            $ok = !$nonce->load();
            if ($ok) {
                $ok = $nonce->save();
            }
            if (!$ok) {
                $this->reason = 'Invalid nonce.';
            } elseif (!empty($publicKey) || !empty($jku) || Jwt::$allowJkuHeader) {
                $ok = $this->jwt->verify($publicKey, $jku);
                if (!$ok) {
                    $this->reason = 'JWT signature check failed - perhaps an invalid public key or timestamp';
                }
            } else {
                $ok = false;
                $this->reason = 'Unable to verify JWT signature as neither a public key nor a JSON Web Key URL is specified';
            }
        }

        return $ok;
    }

###
###    PRIVATE METHODS
###

    /**
     * Parse the message.
     *
     * @param bool    $strictMode      True if full compliance with the LTI specification is required
     * @param bool    $disableCookieCheck  True if no cookie check should be made
     * @param bool    $generateWarnings    True if warning messages should be generated
     */
    private function parseMessage($strictMode, $disableCookieCheck, $generateWarnings)
    {
        if (is_null($this->messageParameters)) {
            $this->getRawParameters();
            if (isset($this->rawParameters['id_token']) || isset($this->rawParameters['JWT'])) {  // JWT-signed message
                try {
                    $this->jwt = Jwt::getJwtClient();
                    if (isset($this->rawParameters['id_token'])) {
                        $this->ok = $this->jwt->load($this->rawParameters['id_token'], $this->rsaKey);
                    } else {
                        $this->ok = $this->jwt->load($this->rawParameters['JWT'], $this->rsaKey);
                    }
                    if (!$this->ok) {
                        $this->reason = 'Message does not contain a valid JWT';
                    } else {
                        $this->ok = $this->jwt->hasClaim('iss') && $this->jwt->hasClaim('aud') &&
                            $this->jwt->hasClaim(Util::JWT_CLAIM_PREFIX . '/claim/deployment_id');
                        if ($this->ok) {
                            $iss = $this->jwt->getClaim('iss');
                            $aud = $this->jwt->getClaim('aud');
                            $deploymentId = $this->jwt->getClaim(Util::JWT_CLAIM_PREFIX . '/claim/deployment_id');
                            $this->ok = !empty($iss) && !empty($aud) && !empty($deploymentId);
                            if (!$this->ok) {
                                $this->reason = 'iss, aud and/or deployment_id claim is empty';
                            } elseif (is_array($aud)) {
                                if ($this->jwt->hasClaim('azp')) {
                                    $this->ok = !empty($this->jwt->getClaim('azp'));
                                    if (!$this->ok) {
                                        $this->reason = 'azp claim is empty';
                                    } else {
                                        $this->ok = in_array($this->jwt->getClaim('azp'), $aud);
                                        if ($this->ok) {
                                            $aud = $this->jwt->getClaim('azp');
                                        } else {
                                            $this->reason = 'azp claim value is not included in aud claim';
                                        }
                                    }
                                } else {
                                    $aud = $aud[0];
                                    $this->ok = !empty($aud);
                                    if (!$this->ok) {
                                        $this->reason = 'First element of aud claim is empty';
                                    }
                                }
                            } elseif ($this->jwt->hasClaim('azp')) {
                                $this->ok = $this->jwt->getClaim('azp') === $aud;
                                if (!$this->ok) {
                                    $this->reason = 'aud claim does not match the azp claim';
                                }
                            }
                            if ($this->ok) {
                                if ($this instanceof Tool) {
                                    $this->platform = Platform::fromPlatformId($iss, $aud, $deploymentId, $this->dataConnector);
                                    $this->platform->platformId = $iss;
                                    if (isset($this->rawParameters['id_token'])) {
                                        $this->ok = !empty($this->rawParameters['state']);
                                        if ($this->ok) {
                                            $state = $this->rawParameters['state'];
                                            if (!$disableCookieCheck) {
                                                $parts = explode('.', $state);
                                                if (empty($_COOKIE) && !isset($_POST['_new_window'])) {  // Reopen in a new window
                                                    Util::setTestCookie();
                                                    $_POST['_new_window'] = '';
                                                    echo Util::sendForm($_SERVER['REQUEST_URI'], $_POST, '_blank');
                                                    exit;
                                                } elseif (!empty(session_id()) && (count($parts) > 1) && (session_id() !== $parts[1])) {  // Reset to original session
                                                    session_abort();
                                                    session_id($parts[1]);
                                                    session_start();
                                                    $this->onResetSessionId();
                                                }
                                                Util::setTestCookie(true);
                                            }
                                            $nonce = new PlatformNonce($this->platform, $state);
                                            $this->ok = $nonce->load();
                                            if (!$this->ok) {
                                                $platform = Platform::fromPlatformId($iss, $aud, null, $this->dataConnector);
                                                $nonce = new PlatformNonce($platform, $state);
                                                $this->ok = $nonce->load();
                                            }
                                            if (!$this->ok) {
                                                $platform = Platform::fromPlatformId($iss, null, null, $this->dataConnector);
                                                $nonce = new PlatformNonce($platform, $state);
                                                $this->ok = $nonce->load();
                                            }
                                            if ($this->ok) {
                                                $this->ok = $nonce->delete();
                                            }
                                        }
                                    }
                                }
                                if ($this->ok) {
                                    $this->messageParameters = array();
                                    $this->messageParameters['oauth_consumer_key'] = $aud;
                                    $this->messageParameters['oauth_signature_method'] = $this->jwt->getHeader('alg');
                                    $this->parseClaims($strictMode, $generateWarnings);
                                } else {
                                    $this->reason = 'state parameter is invalid or missing';
                                }
                            }
                        } else {
                            $this->reason = 'iss, aud and/or deployment_id claim not found';
                        }
                    }
                } catch (\Exception $e) {
                    $this->ok = false;
                    $this->reason = 'Message does not contain a valid JWT';
                }
            } elseif (isset($this->rawParameters['error'])) {  // Error with JWT-signed message
                $this->ok = false;
                $this->reason = $this->rawParameters['error'];
                if (!empty($this->rawParameters['error_description'])) {
                    $this->reason .= ": {$this->rawParameters['error_description']}";
                }
            } else {  // OAuth
                if ($this instanceof Tool) {
                    if (isset($this->rawParameters['oauth_consumer_key'])) {
                        $this->platform = Platform::fromConsumerKey($this->rawParameters['oauth_consumer_key'], $this->dataConnector);
                    }
                    if (isset($this->rawParameters['tool_state'])) {  // Relaunch?
                        $state = $this->rawParameters['tool_state'];
                        if (!$disableCookieCheck) {
                            $parts = explode('.', $state);
                            if (empty($_COOKIE) && !isset($_POST['_new_window'])) {  // Reopen in a new window
                                Util::setTestCookie();
                                $_POST['_new_window'] = '';
                                echo Util::sendForm($_SERVER['REQUEST_URI'], $_POST, '_blank');
                                exit;
                            } elseif (!empty(session_id()) && (count($parts) > 1) && (session_id() !== $parts[1])) {  // Reset to original session
                                session_abort();
                                session_id($parts[1]);
                                session_start();
                                $this->onResetSessionId();
                            }
                            unset($this->rawParameters['_new_window']);
                            Util::setTestCookie(true);
                        }
                        $nonce = new PlatformNonce($this->platform, $state);
                        $this->ok = $nonce->load();
                        if (!$this->ok) {
                            $this->reason = "Invalid tool_state parameter: '{$state}'";
                        }
                    }
                }
                $this->messageParameters = $this->rawParameters;
            }
        }
    }

    /**
     * Parse the claims
     *
     * @param bool    $strictMode      True if full compliance with the LTI specification is required
     * @param bool    $generateWarnings    True if warning messages should be generated
     */
    private function parseClaims($strictMode, $generateWarnings)
    {
        $payload = Util::cloneObject($this->jwt->getPayload());
        $errors = array();
        foreach (Util::JWT_CLAIM_MAPPING as $key => $mapping) {
            $claim = Util::JWT_CLAIM_PREFIX;
            if (!empty($mapping['suffix'])) {
                $claim .= "-{$mapping['suffix']}";
            }
            $claim .= '/claim/';
            if (is_null($mapping['group'])) {
                $claim = $mapping['claim'];
            } elseif (empty($mapping['group'])) {
                $claim .= $mapping['claim'];
            } else {
                $claim .= $mapping['group'];
            }
            if ($this->jwt->hasClaim($claim)) {
                $value = null;
                if (empty($mapping['group'])) {
                    unset($payload->{$claim});
                    $value = $this->jwt->getClaim($claim);
                } else {
                    $group = $this->jwt->getClaim($claim);
                    if (is_array($group) && array_key_exists($mapping['claim'], $group)) {
                        unset($payload->{$claim}[$mapping['claim']]);
                        $value = $group[$mapping['claim']];
                    } elseif (is_object($group) && isset($group->{$mapping['claim']})) {
                        unset($payload->{$claim}->{$mapping['claim']});
                        $value = $group->{$mapping['claim']};
                    }
                }
                if (!is_null($value)) {
                    if (isset($mapping['isArray']) && $mapping['isArray']) {
                        if (!is_array($value)) {
                            $errors[] = "'{$claim}' claim must be an array";
                        } else {
                            $value = implode(',', $value);
                        }
                    } elseif (isset($mapping['isObject']) && $mapping['isObject']) {
                        $value = json_encode($value);
                    } elseif (isset($mapping['isBoolean']) && $mapping['isBoolean']) {
                        $value = $value ? 'true' : 'false';
                    } elseif (isset($mapping['isInteger']) && $mapping['isInteger']) {
                        $value = strval($value);
                    } elseif (!is_string($value)) {
                        if ($generateWarnings) {
                            $this->warnings[] = "Value of claim '{$claim}' is not a string: '{$value}'";
                        }
                        if (!$strictMode) {
                            $value = strval($value);
                        }
                    }
                }
                if (!is_null($value) && is_string($value)) {
                    $this->messageParameters[$key] = $value;
                }
            }
        }
        if (!empty($this->messageParameters['lti_message_type']) &&
            in_array($this->messageParameters['lti_message_type'], array_values(Util::MESSAGE_TYPE_MAPPING))) {
            $this->messageParameters['lti_message_type'] = array_search($this->messageParameters['lti_message_type'],
                Util::MESSAGE_TYPE_MAPPING);
        }
        if (!empty($this->messageParameters['accept_types'])) {
            $types = array_map('trim', explode(',', $this->messageParameters['accept_types']));
            $types = array_filter($types);
            $mediaTypes = array();
            if (!empty($this->messageParameters['accept_media_types'])) {
                $mediaTypes = array_map('trim', explode(',', $this->messageParameters['accept_media_types']));
                $mediaTypes = array_filter($mediaTypes);
            }
            if (in_array(Item::TYPE_LTI_LINK, $types)) {
                $mediaTypes[] = Item::LTI_LINK_MEDIA_TYPE;
            }
            if (in_array(Item::TYPE_LTI_ASSIGNMENT, $types)) {
                $mediaTypes[] = Item::LTI_ASSIGNMENT_MEDIA_TYPE;
            }
            if (in_array('html', $types) && !in_array('*/*', $mediaTypes)) {
                $mediaTypes[] = 'text/html';
            }
            if (in_array('image', $types) && !in_array('*/*', $mediaTypes)) {
                $mediaTypes[] = 'image/*';
            }
            $mediaTypes = array_unique($mediaTypes);
            $this->messageParameters['accept_media_types'] = implode(',', $mediaTypes);
        }
        $claim = Util::JWT_CLAIM_PREFIX . '/claim/custom';
        if ($this->jwt->hasClaim($claim)) {
            unset($payload->{$claim});
            $custom = $this->jwt->getClaim($claim);
            if (!is_array($custom) && !is_object($custom)) {
                $errors[] = "'{$claim}' claim must be an object";
            } else {
                foreach ($custom as $key => $value) {
                    $this->messageParameters["custom_{$key}"] = $value;
                }
            }
        }
        $claim = Util::JWT_CLAIM_PREFIX . '/claim/ext';
        if ($this->jwt->hasClaim($claim)) {
            unset($payload->{$claim});
            $ext = $this->jwt->getClaim($claim);
            if (!is_array($ext) && !is_object($ext)) {
                $errors[] = "'{$claim}' claim must be an object";
            } else {
                foreach ($ext as $key => $value) {
                    $this->messageParameters["ext_{$key}"] = $value;
                }
            }
        }
        $claim = Util::JWT_CLAIM_PREFIX . '/claim/lti1p1';
        if ($this->jwt->hasClaim($claim)) {
            unset($payload->{$claim});
            $lti1p1 = $this->jwt->getClaim($claim);
            if (!is_array($lti1p1) && !is_object($lti1p1)) {
                $errors[] = "'{$claim}' claim must be an object";
            } else {
                foreach ($lti1p1 as $key => $value) {
                    if (is_null($value)) {
                        $value = '';
                    } elseif (is_object($value)) {
                        $value = json_encode($value);
                    }
                    $this->messageParameters["lti1p1_{$key}"] = $value;
                }
            }
        }
        $claim = 'http://www.brightspace.com';
        if ($this->jwt->hasClaim($claim)) {
            $d2l = $this->jwt->getClaim($claim);
            if (is_array($d2l)) {
                if (!empty($d2l['username'])) {
                    $this->messageParameters['ext_d2l_username'] = $d2l['username'];
                    unset($payload->{$claim}['username']);
                }
            } else if (is_object($ext)) {
                if (!empty($d2l->username)) {
                    $this->messageParameters['ext_d2l_username'] = $d2l->username;
                    unset($payload->{$claim}->username);
                }
            }
        }
        if (!empty($payload)) {
            $objVars = get_object_vars($payload);
            foreach ($objVars as $attrName => $attrValue) {
                if (empty((array) $attrValue)) {
                    unset($payload->{$attrName});
                }
            }
            $this->messageParameters['unmapped_claims'] = json_encode($payload);
        }
        if (!empty($errors)) {
            $this->ok = false;
            $this->reason = 'Invalid JWT: ' . implode(', ', $errors);
        }
    }

    /**
     * Call any callback function for the requested action.
     *
     * This function may set the redirect_url and output properties.
     */
    private function doCallback()
    {
        if (array_key_exists($this->messageParameters['lti_message_type'], Util::$METHOD_NAMES)) {
            $callback = Util::$METHOD_NAMES[$this->messageParameters['lti_message_type']];
        } else {
            $callback = "on{$this->messageParameters['lti_message_type']}";
        }
        if (method_exists($this, $callback)) {
            $this->$callback();
        } elseif ($this->ok) {
            $this->ok = false;
            $this->reason = "Message type not supported: {$this->messageParameters['lti_message_type']}";
        }
    }

    /**
     * Add the OAuth 1 signature to an array of message parameters or to a header string.
     *
     * @param string $endpoint          URL to which message is being sent
     * @param mixed $data               Data to be passed
     * @param string $method            HTTP method
     * @param string|null $type         Content type of data being passed
     * @param string|null $hash         OAuth body hash value
     * @param int|null $timestamp       Timestamp
     *
     * @return string[]|string Array of signed message parameters or header string
     */
    private function addOAuth1Signature($endpoint, $data, $method, $type, $hash, $timestamp)
    {
        $params = array();
        if (is_array($data)) {
            $params = $data;
            $params['oauth_callback'] = 'about:blank';
        }
// Check for query parameters which need to be included in the signature
        $queryString = parse_url($endpoint, PHP_URL_QUERY);
        $queryParams = OAuth\OAuthUtil::parse_parameters($queryString);
        $params = array_merge_recursive($queryParams, $params);

        if (!is_array($data)) {
            if (empty($hash)) {  // Calculate body hash
                switch ($this->signatureMethod) {
                    case 'HMAC-SHA224':
                        $hash = base64_encode(hash('sha224', $data, true));
                        break;
                    case 'HMAC-SHA256':
                        $hash = base64_encode(hash('sha256', $data, true));
                        break;
                    case 'HMAC-SHA384':
                        $hash = base64_encode(hash('sha384', $data, true));
                        break;
                    case 'HMAC-SHA512':
                        $hash = base64_encode(hash('sha512', $data, true));
                        break;
                    default:
                        $hash = base64_encode(sha1($data, true));
                        break;
                }
            }
            $params['oauth_body_hash'] = $hash;
        }
        if (!empty($timestamp)) {
            $params['oauth_timestamp'] = $timestamp;
        }

// Add OAuth signature
        switch ($this->signatureMethod) {
            case 'HMAC-SHA224':
                $hmacMethod = new OAuth\OAuthSignatureMethod_HMAC_SHA224();
                break;
            case 'HMAC-SHA256':
                $hmacMethod = new OAuth\OAuthSignatureMethod_HMAC_SHA256();
                break;
            case 'HMAC-SHA384':
                $hmacMethod = new OAuth\OAuthSignatureMethod_HMAC_SHA384();
                break;
            case 'HMAC-SHA512':
                $hmacMethod = new OAuth\OAuthSignatureMethod_HMAC_SHA512();
                break;
            default:
                $hmacMethod = new OAuth\OAuthSignatureMethod_HMAC_SHA1();
                break;
        }
        $key = $this->key;
        $secret = $this->secret;
        if (empty($key)) {
            if (($this instanceof Tool) && !empty($this->platform)) {
                $key = $this->platform->getKey();
                $secret = $this->platform->secret;
            } elseif (($this instanceof Platform) && !empty(Tool::$defaultTool)) {
                $key = Tool::$defaultTool->getKey();
                $secret = Tool::$defaultTool->secret;
            }
        }
        $oauthConsumer = new OAuth\OAuthConsumer($key, $secret, null);
        $oauthReq = OAuth\OAuthRequest::from_consumer_and_token($oauthConsumer, null, $method, $endpoint, $params);
        $oauthReq->sign_request($hmacMethod, $oauthConsumer, null);
        if (!is_array($data)) {
            $header = $oauthReq->to_header();
            if (empty($data)) {
                if (!empty($type)) {
                    $header .= "\nAccept: {$type}";
                }
            } elseif (isset($type)) {
                $header .= "\nContent-Type: {$type}; charset=UTF-8";
                $header .= "\nContent-Length: " . strlen($data);
            }
            return $header;
        } else {
// Remove parameters from query string
            $params = $oauthReq->get_parameters();
            foreach ($queryParams as $key => $value) {
                if (!is_array($value)) {
                    if (!is_array($params[$key])) {
                        if ($params[$key] === $value) {
                            unset($params[$key]);
                        }
                    } else {
                        $params[$key] = array_diff($params[$key], array($value));
                    }
                } else {
                    foreach ($value as $element) {
                        $params[$key] = array_diff($params[$key], array($value));
                    }
                }
            }
// Remove any parameters comprising an empty array of values
            foreach ($params as $key => $value) {
                if (is_array($value)) {
                    if (count($value) <= 0) {
                        unset($params[$key]);
                    } elseif (count($value) === 1) {
                        $params[$key] = reset($value);
                    }
                }
            }
            return $params;
        }
    }

    /**
     * Add the JWT signature to an array of message parameters or to a header string.
     *
     * @param string $endpoint          URL to which message is being sent
     * @param mixed $data               Data to be passed
     * @param string $method            HTTP method
     * @param string|null $type         Content type of data being passed
     * @param string|null $nonce        Nonce value for JWT
     * @param int|null $timestamp       Timestamp
     *
     * @return string[]|string Array of signed message parameters or header string
     */
    private function addJWTSignature($endpoint, $data, $method, $type, $nonce, $timestamp)
    {
        $ok = false;
        if (is_array($data)) {
            $ok = true;
            if (empty($nonce)) {
                $nonce = Util::getRandomString(32);
            }
            $publicKey = null;
            if (!array_key_exists('grant_type', $data)) {
                $this->messageParameters = $data;
                $payload = $this->getMessageClaims();
                $privateKey = $this->rsaKey;
                $kid = $this->kid;
                $jku = $this->jku;
                if ($this instanceof Platform) {
                    if (!empty(Tool::$defaultTool)) {
                        $publicKey = Tool::$defaultTool->rsaKey;
                    }
                    $payload['iss'] = $this->platformId;
                    $payload['aud'] = array($this->clientId);
                    $payload['azp'] = $this->clientId;
                    $payload[Util::JWT_CLAIM_PREFIX . '/claim/deployment_id'] = $this->deploymentId;
                    $payload[Util::JWT_CLAIM_PREFIX . '/claim/target_link_uri'] = $endpoint;
                    $paramName = 'id_token';
                } else {
                    if (!empty($this->platform)) {
                        $publicKey = $this->platform->rsaKey;
                        $payload['iss'] = $this->platform->clientId;
                        $payload['aud'] = array($this->platform->platformId);
                        $payload['azp'] = $this->platform->platformId;
                        $payload[Util::JWT_CLAIM_PREFIX . '/claim/deployment_id'] = $this->platform->deploymentId;
                    }
                    $paramName = 'JWT';
                }
                $payload['nonce'] = $nonce;
            } else {
                $authorizationId = '';
                if ($this instanceof Tool) {
                    $sub = '';
                    if (!empty($this->platform)) {
                        $sub = $this->platform->clientId;
                        $authorizationId = $this->platform->authorizationServerId;
                        $publicKey = $this->platform->rsaKey;
                    }
                    $privateKey = $this->rsaKey;
                    $kid = $this->kid;
                    $jku = $this->jku;
                } else {  // Tool-hosted services not yet defined in LTI
                    $sub = $this->clientId;
                    $kid = $this->kid;
                    $jku = $this->jku;
                    $privateKey = $this->rsaKey;
                    if (!empty(Tool::$defaultTool)) {
                        $publicKey = Tool::$defaultTool->rsaKey;
                    }
                }
                $payload['iss'] = $sub;
                $payload['sub'] = $sub;
                if (empty($authorizationId)) {
                    $authorizationId = $endpoint;
                }
                $payload['aud'] = array($authorizationId);
                $payload['jti'] = $nonce;
                $params = $data;
                $paramName = 'client_assertion';
            }
        }
        if ($ok) {
            if (empty($timestamp)) {
                $timestamp = time();
            }
            $payload['iat'] = $timestamp;
            $payload['exp'] = $timestamp + Jwt::$life;
            try {
                $jwt = Jwt::getJwtClient();
                $params[$paramName] = $jwt::sign($payload, $this->signatureMethod, $privateKey, $kid, $jku, $this->encryptionMethod,
                        $publicKey);
            } catch (\Exception $e) {
                $params = array();
            }

            return $params;
        } else {
            $header = '';
            if ($this instanceof Tool) {
                $platform = $this->platform;
            } else {
                $platform = $this;
            }
            $accessToken = $platform->getAccessToken();
            if (empty($accessToken)) {
                $accessToken = new AccessToken($platform);
                $platform->setAccessToken($accessToken);
            }
            if (!$accessToken->hasScope()) {  // Check token has not expired
                $accessToken->get();
            }
            if (!empty($accessToken->token)) {
                $header = "Authorization: Bearer {$accessToken->token}";
            }
            if (empty($data) && ($method !== 'DELETE')) {
                if (!empty($type)) {
                    $header .= "\nAccept: {$type}";
                }
            } elseif (isset($type)) {
                $header .= "\nContent-Type: {$type}; charset=UTF-8";
                if (!empty($data) && is_string($data)) {
                    $header .= "\nContent-Length: " . strlen($data);
                }
            }

            return $header;
        }
    }

    /**
     * Expand a claim into an array of individual fully-qualified claims.
     *
     * @param string $claim          Name of claim
     * @param string $value          Value of claim
     *
     * @return string[] Array of individual claims and values
     */
    private static function fullyQualifyClaim($claim, $value)
    {
        $claims = array();
        $empty = true;
        if (is_object($value)) {
            foreach ($value as $c => $v) {
                $empty = false;
                $claims = array_merge($claims, static::fullyQualifyClaim("{$claim}/{$c}", $v));
            }
        }
        if ($empty) {
            $claims[$claim] = $value;
        }

        return $claims;
    }

}
