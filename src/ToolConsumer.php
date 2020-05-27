<?php

namespace ceLTIc\LTI;

use ceLTIc\LTI\DataConnector;
use ceLTIc\LTI\Service;
use ceLTIc\LTI\Http\HttpMessage;
use ceLTIc\LTI\OAuth;

/**
 * Class to represent a tool consumer
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ToolConsumer
{

    /**
     * Local name of tool consumer.
     *
     * @var string|null $name
     */
    public $name = null;

    /**
     * Shared secret.
     *
     * @var string|null $secret
     */
    public $secret = null;

    /**
     * LTI version (as reported by last tool consumer connection).
     *
     * @var string|null $ltiVersion
     */
    public $ltiVersion = null;

    /**
     * Method used for signing messages.
     *
     * @var string $signatureMethod
     */
    public $signatureMethod = 'HMAC-SHA1';

    /**
     * Name of tool consumer (as reported by last tool consumer connection).
     *
     * @var string|null $consumerName
     */
    public $consumerName = null;

    /**
     * Tool consumer version (as reported by last tool consumer connection).
     *
     * @var string|null $consumerVersion
     */
    public $consumerVersion = null;

    /**
     * The consumer profile data.
     *
     * @var object|null $profile
     */
    public $profile = null;

    /**
     * Tool consumer GUID (as reported by first tool consumer connection).
     *
     * @var string|null $consumerGuid
     */
    public $consumerGuid = null;

    /**
     * Optional CSS path (as reported by last tool consumer connection).
     *
     * @var string|null $cssPath
     */
    public $cssPath = null;

    /**
     * Whether the tool consumer instance is protected by matching the consumer_guid value in incoming requests.
     *
     * @var bool $protected
     */
    public $protected = false;

    /**
     * Whether the tool consumer instance is enabled to accept incoming connection requests.
     *
     * @var bool $enabled
     */
    public $enabled = false;

    /**
     * Timestamp from which the the tool consumer instance is enabled to accept incoming connection requests.
     *
     * @var int|null $enableFrom
     */
    public $enableFrom = null;

    /**
     * Timestamp until which the tool consumer instance is enabled to accept incoming connection requests.
     *
     * @var int|null $enableUntil
     */
    public $enableUntil = null;

    /**
     * Timestamp for date of last connection from this tool consumer.
     *
     * @var int|null $lastAccess
     */
    public $lastAccess = null;

    /**
     * Default scope to use when generating an Id value for a user.
     *
     * @var int $idScope
     */
    public $idScope = ToolProvider::ID_SCOPE_ID_ONLY;

    /**
     * Default email address (or email domain) to use when no email address is provided for a user.
     *
     * @var string $defaultEmail
     */
    public $defaultEmail = '';

    /**
     * HttpMessage object for last service request.
     *
     * @var HttpMessage|null $lastServiceRequest
     */
    public $lastServiceRequest = null;

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
     * Consumer ID value.
     *
     * @var int|null $id
     */
    private $id = null;

    /**
     * Consumer key value.
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
     * Data connector object or string.
     *
     * @var DataConnector|null $dataConnector
     */
    private $dataConnector = null;

    /**
     * Class constructor.
     *
     * @param string  $key             Consumer key
     * @param DataConnector   $dataConnector   A data connector object
     * @param bool    $autoEnable      true if the tool consumers is to be enabled automatically (optional, default is false)
     */
    public function __construct($key = null, $dataConnector = null, $autoEnable = false)
    {
        $this->initialize();
        if (empty($dataConnector)) {
            $dataConnector = DataConnector\DataConnector::getDataConnector();
        }
        $this->dataConnector = $dataConnector;
        if (!is_null($key) && (strlen($key) > 0)) {
            $this->load($key, $autoEnable);
        } else {
            $this->secret = DataConnector\DataConnector::getRandomString(32);
        }
    }

    /**
     * Initialise the tool consumer.
     */
    public function initialize()
    {
        $this->id = null;
        $this->key = null;
        $this->name = null;
        $this->secret = null;
        $this->signatureMethod = 'HMAC-SHA1';
        $this->ltiVersion = null;
        $this->consumerName = null;
        $this->consumerVersion = null;
        $this->consumerGuid = null;
        $this->profile = null;
        $this->toolProxy = null;
        $this->settings = array();
        $this->protected = false;
        $this->enabled = false;
        $this->enableFrom = null;
        $this->enableUntil = null;
        $this->lastAccess = null;
        $this->idScope = ToolProvider::ID_SCOPE_ID_ONLY;
        $this->defaultEmail = '';
        $this->created = null;
        $this->updated = null;
    }

    /**
     * Initialise the tool consumer.
     *
     * Synonym for initialize().
     */
    public function initialise()
    {
        $this->initialize();
    }

    /**
     * Save the tool consumer to the database.
     *
     * @return bool    True if the object was successfully saved
     */
    public function save()
    {
        $ok = $this->dataConnector->saveToolConsumer($this);
        if ($ok) {
            $this->settingsChanged = false;
        }

        return $ok;
    }

    /**
     * Delete the tool consumer from the database.
     *
     * @return bool    True if the object was successfully deleted
     */
    public function delete()
    {
        return $this->dataConnector->deleteToolConsumer($this);
    }

    /**
     * Get the tool consumer record ID.
     *
     * @return int|null Consumer record ID value
     */
    public function getRecordId()
    {
        return $this->id;
    }

    /**
     * Sets the tool consumer record ID.
     *
     * @param int $id  Consumer record ID value
     */
    public function setRecordId($id)
    {
        $this->id = $id;
    }

    /**
     * Get the tool consumer key.
     *
     * @return string Consumer key value
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set the tool consumer key.
     *
     * @param string $key  Consumer key value
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Get tool consumer family code (as reported by last tool consumer connection).
     *
     * @return string Family code
     */
    public function getFamilyCode()
    {
        $familyCode = '';
        if (!empty($this->consumerVersion)) {
            list($familyCode, $version) = explode('-', $this->consumerVersion, 2);
        }

        return $familyCode;
    }

    /**
     * Get the data connector.
     *
     * @return DataConnector|null Data connector object or string
     */
    public function getDataConnector()
    {
        return $this->dataConnector;
    }

    /**
     * Is the consumer key available to accept launch requests?
     *
     * @return bool    True if the consumer key is enabled and within any date constraints
     */
    public function getIsAvailable()
    {
        $ok = $this->enabled;

        $now = time();
        if ($ok && !is_null($this->enableFrom)) {
            $ok = $this->enableFrom <= $now;
        }
        if ($ok && !is_null($this->enableUntil)) {
            $ok = $this->enableUntil > $now;
        }

        return $ok;
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
     * Check if the Tool Settings service is supported.
     *
     * @return bool    True if this tool consumer supports the Tool Settings service
     */
    public function hasToolSettingsService()
    {
        $has = !empty($this->getSetting('custom_system_setting_url'));
        if (!$has) {
            $has = self::hasApiHook(self::$TOOL_SETTINGS_SERVICE_HOOK, $this->getConsumer()->getFamilyCode());
        }
        return $has;
    }

    /**
     * Get Tool Settings.
     *
     * @param bool     $simple     True if all the simple media type is to be used (optional, default is true)
     *
     * @return mixed The array of settings if successful, otherwise false
     */
    public function getToolSettings($simple = true)
    {
        $ok = false;
        $settings = array();
        if (!empty($this->getSetting('custom_system_setting_url'))) {
            $url = $this->getSetting('custom_system_setting_url');
            $service = new Service\ToolSettings($this, $url, $simple);
            $settings = $service->get($mode);
            $this->lastServiceRequest = $service->getHttpMessage();
            $ok = $settings !== false;
        }
        if (!$ok && $this->hasApiHook(self::$TOOL_SETTINGS_SERVICE_HOOK, $this->getConsumer()->getFamilyCode())) {
            $className = $this->getApiHook(self::$TOOL_SETTINGS_SERVICE_HOOK, $this->getConsumer()->getFamilyCode());
            $hook = new $className($this);
            $settings = $hook->getToolSettings($mode, $simple);
        }

        return $settings;
    }

    /**
     * Perform a Tool Settings service request.
     *
     * @param array    $settings   An associative array of settings (optional, default is none)
     *
     * @return bool    True if action was successful, otherwise false
     */
    public function setToolSettings($settings = array())
    {
        $ok = false;
        if (!empty($this->getSetting('custom_system_setting_url'))) {
            $url = $this->getSetting('custom_system_setting_url');
            $service = new Service\ToolSettings($this, $url);
            $ok = $service->set($settings);
            $this->lastServiceRequest = $service->getHttpMessage();
        }
        if (!$ok && $this->hasApiHook(self::$TOOL_SETTINGS_SERVICE_HOOK, $this->getConsumer()->getFamilyCode())) {
            $className = $this->getApiHook(self::$TOOL_SETTINGS_SERVICE_HOOK, $this->getConsumer()->getFamilyCode());
            $hook = new $className($this);
            $ok = $hook->setToolSettings($settings);
        }

        return $ok;
    }

    /**
     * Add the signature to an LTI message.
     *
     * @param string  $url         URL for message request
     * @param string  $type        LTI message type
     * @param string  $version     LTI version
     * @param array   $params      Message parameters
     *
     * @return array Array of signed message parameters
     */
    public function signParameters($url, $type, $version, $params)
    {
        if (!empty($url)) {
// Add standard parameters
            $params['lti_version'] = $version;
            $params['lti_message_type'] = $type;
// Add signature
            $params = $this->addSignature($url, $params, 'POST', $type);
        }

        return $params;
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
     * Add the signature to an array of message parameters or to a header string.
     *
     * @param string $endpoint          URL to which message is being sent
     * @param mixed $data               Data to be passed
     * @param string $method            HTTP method
     * @param string|null $type         Content type of data being passed
     *
     * @return mixed Array of signed message parameters or header string
     */
    public function addSignature($endpoint, $data, $method = 'POST', $type = null)
    {
        switch ($this->signatureMethod) {
            case 'HMAC-SHA1':
            case 'HMAC-SHA224':
            case 'HMAC-SHA256':
            case 'HMAC-SHA384':
            case 'HMAC-SHA512':
                return $this->addOAuthSignature($endpoint, $data, $method, $type);
                break;
            default:
                return $data;
                break;
        }
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

// Connect to tool consumer
        $http = new HttpMessage($service->endpoint, $method, $data, $header);
// Parse JSON response
        if ($http->send() && !empty($http->response)) {
            $http->responseJson = json_decode($http->response);
            $http->ok = !is_null($http->responseJson);
        }

        return $http;
    }

    /**
     * Load the tool consumer from the database by its record ID.
     *
     * @param string          $id                The consumer key record ID
     * @param DataConnector   $dataConnector    Database connection object
     *
     * @return ToolConsumer       The tool consumer object
     */
    public static function fromRecordId($id, $dataConnector)
    {
        $toolConsumer = new ToolConsumer(null, $dataConnector);

        $toolConsumer->initialize();
        $toolConsumer->setRecordId($id);
        if (!$dataConnector->loadToolConsumer($toolConsumer)) {
            $toolConsumer->initialize();
        }

        return $toolConsumer;
    }

###
###  PRIVATE METHODS
###

    /**
     * Load the tool consumer from the database.
     *
     * @param string  $key        The consumer key value
     * @param bool    $autoEnable True if the consumer should be enabled (optional, default if false)
     *
     * @return bool    True if the consumer was successfully loaded
     */
    private function load($key, $autoEnable = false)
    {
        $this->key = $key;
        $ok = $this->dataConnector->loadToolConsumer($this);
        if (!$ok) {
            $this->enabled = $autoEnable;
        }

        return $ok;
    }

    /**
     * Add the OAuth signature to an array of message parameters or to a header string.
     *
     * @param string $endpoint          URL to which message is being sent
     * @param mixed $data               Data to be passed
     * @param string $method            HTTP method
     * @param string|null $type         Content type of data being passed
     *
     * @return string[]|string Array of signed message parameters or header string
     */
    private function addOAuthSignature($endpoint, $data, $method, $type)
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
// Calculate body hash
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
            $params['oauth_body_hash'] = $hash;
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
        $oauthConsumer = new OAuth\OAuthConsumer($this->key, $this->secret, null);
        $oauthReq = OAuth\OAuthRequest::from_consumer_and_token($oauthConsumer, null, $method, $endpoint, $params);
        $oauthReq->sign_request($hmacMethod, $oauthConsumer, null);
        if (!is_array($data)) {
            $header = $oauthReq->to_header();
            if (empty($data)) {
                if (!empty($type)) {
                    $header .= "\nAccept: {$type}";
                }
            } elseif (isset($type)) {
                $header .= "\nContent-Type: {$type}";
                $header .= "\nContent-Length: " . strlen($data);
            }
            return $header;
        } else {
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
            return $params;
        }
    }

}
