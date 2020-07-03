<?php

namespace ceLTIc\LTI;

use ceLTIc\LTI\DataConnector\DataConnector;
use ceLTIc\LTI\Service;
use ceLTIc\LTI\Http\HttpMessage;

/**
 * Class to represent a platform
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class Platform
{
    use System;

    /**
     * Local name of platform.
     *
     * @var string|null $name
     */
    public $name = null;

    /**
     * Platform ID.
     *
     * @var string $platformId
     */
    public $platformId = null;

    /**
     * Client ID.
     *
     * @var string $clientId
     */
    public $clientId = null;

    /**
     * Deployment ID.
     *
     * @var string $deploymentId
     */
    public $deploymentId = null;

    /**
     * Authorization server ID.
     *
     * @var string $authorizationServerId
     */
    public $authorizationServerId = null;

    /**
     * Login authentication URL.
     *
     * @var string $authenticationUrl
     */
    public $authenticationUrl = null;

    /**
     * Access Token service URL.
     *
     * @var string $accessTokenUrl
     */
    public $accessTokenUrl = null;

    /**
     * LTI version (as reported by last platform connection).
     *
     * @var string|null $ltiVersion
     */
    public $ltiVersion = null;

    /**
     * Name of platform (as reported by last platform connection).
     *
     * @var string|null $consumerName
     */
    public $consumerName = null;

    /**
     * Pplatform version (as reported by last platform connection).
     *
     * @var string|null $consumerVersion
     */
    public $consumerVersion = null;

    /**
     * The platform profile data.
     *
     * @var object|null $profile
     */
    public $profile = null;

    /**
     * Platform GUID (as reported by first platform connection).
     *
     * @var string|null $consumerGuid
     */
    public $consumerGuid = null;

    /**
     * Optional CSS path (as reported by last platform connection).
     *
     * @var string|null $cssPath
     */
    public $cssPath = null;

    /**
     * Access token to authorize service requests.
     *
     * @var AccessToken|null $accessToken
     */
    private $accessToken = null;

    /**
     * Get the authorization access token
     *
     * @return AccessToken Access token
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Set the authorization access token
     *
     * @param AccessToken $accessToken  Access token
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * Whether the platform instance is protected by matching the consumer_guid value in incoming requests.
     *
     * @var bool $protected
     */
    public $protected = false;

    /**
     * Whether the platform instance is enabled to accept incoming connection requests.
     *
     * @var bool $enabled
     */
    public $enabled = false;

    /**
     * Timestamp from which the the platform instance is enabled to accept incoming connection requests.
     *
     * @var int|null $enableFrom
     */
    public $enableFrom = null;

    /**
     * Timestamp until which the platform instance is enabled to accept incoming connection requests.
     *
     * @var int|null $enableUntil
     */
    public $enableUntil = null;

    /**
     * Timestamp for date of last connection from this platform.
     *
     * @var int|null $lastAccess
     */
    public $lastAccess = null;

    /**
     * Default scope to use when generating an Id value for a user.
     *
     * @var int $idScope
     */
    public $idScope = Tool::ID_SCOPE_ID_ONLY;

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
     * Platform ID value.
     *
     * @var int|null $id
     */
    private $id = null;

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
     * Class constructor.
     *
     * @param DataConnector   $dataConnector   A data connector object
     */
    public function __construct($dataConnector = null)
    {
        $this->initialize();
        if (empty($dataConnector)) {
            $dataConnector = DataConnector::getDataConnector();
        }
        $this->dataConnector = $dataConnector;
    }

    /**
     * Initialise the platform.
     */
    public function initialize()
    {
        $this->id = null;
        $this->key = null;
        $this->name = null;
        $this->secret = null;
        $this->signatureMethod = 'HMAC-SHA1';
        $this->encryptionMethod = null;
        $this->rsaKey = null;
        $this->kid = null;
        $this->jku = null;
        $this->platformId = null;
        $this->clientId = null;
        $this->deploymentId = null;
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
        $this->idScope = Tool::ID_SCOPE_ID_ONLY;
        $this->defaultEmail = '';
        $this->created = null;
        $this->updated = null;
    }

    /**
     * Initialise the platform.
     *
     * Synonym for initialize().
     */
    public function initialise()
    {
        $this->initialize();
    }

    /**
     * Save the platform to the database.
     *
     * @return bool    True if the object was successfully saved
     */
    public function save()
    {
        return $this->dataConnector->savePlatform($this);
    }

    /**
     * Delete the platform from the database.
     *
     * @return bool    True if the object was successfully deleted
     */
    public function delete()
    {
        return $this->dataConnector->deletePlatform($this);
    }

    /**
     * Get the platform record ID.
     *
     * @return int|null  Platform record ID value
     */
    public function getRecordId()
    {
        return $this->id;
    }

    /**
     * Sets the platform record ID.
     *
     * @param int $id  Platform record ID value
     */
    public function setRecordId($id)
    {
        $this->id = $id;
    }

    /**
     * Get the platform ID.
     *
     * The ID will be the consumer key if one exists, otherwise a concatenation of the platform/client/deployment IDs
     *
     * @return string  Platform ID value
     */
    public function getId()
    {
        if (!empty($this->key)) {
            $id = $this->key;
        } elseif (!empty($this->platformId)) {
            $id = $this->platformId;
            if (!empty($this->clientId)) {
                $id .= '/' . $this->clientId;
            }
            if (!empty($this->deploymentId)) {
                $id .= '#' . $this->deploymentId;
            }
        } else {
            $id = null;
        }

        return $id;
    }

    /**
     * Get platform family code (as reported by last platform connection).
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
     * Is the platform available to accept launch requests?
     *
     * @return bool    True if the platform is enabled and within any date constraints
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
     * @return bool    True if this platform supports the Tool Settings service
     */
    public function hasToolSettingsService()
    {
        $has = !empty($this->getSetting('custom_system_setting_url'));
        if (!$has) {
            $has = self::hasConfiguredApiHook(self::$TOOL_SETTINGS_SERVICE_HOOK, $this->getFamilyCode(), $this);
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
            $settings = $service->get();
            $this->lastServiceRequest = $service->getHttpMessage();
            $ok = $settings !== false;
        }
        if (!$ok && $this->hasConfiguredApiHook(self::$TOOL_SETTINGS_SERVICE_HOOK, $this->getFamilyCode(), $this)) {
            $className = $this->getApiHook(self::$TOOL_SETTINGS_SERVICE_HOOK, $this->getFamilyCode());
            $hook = new $className($this);
            $settings = $hook->getToolSettings($simple);
        }

        return $settings;
    }

    /**
     * Set Tool Settings.
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
        if (!$ok && $this->hasConfiguredApiHook(self::$TOOL_SETTINGS_SERVICE_HOOK, $this->getFamilyCode(), $this)) {
            $className = $this->getApiHook(self::$TOOL_SETTINGS_SERVICE_HOOK, $this->getFamilyCode());
            $hook = new $className($this);
            $ok = $hook->setToolSettings($settings);
        }

        return $ok;
    }

    /**
     * Check if the Access Token service is supported.
     *
     * @return bool    True if this platform supports the Access Token service
     */
    public function hasAccessTokenService()
    {
        $has = !empty($this->getSetting('custom_oauth2_access_token_url'));
        if (!$has) {
            $has = self::hasConfiguredApiHook(self::$ACCESS_TOKEN_SERVICE_HOOK, $this->getFamilyCode(), $this);
        }
        return $has;
    }

    /**
     * Get the message parameters
     *
     * @return array The message parameter array
     */
    public function getMessageParameters()
    {
        if ($this->ok && is_null($this->messageParameters)) {
            $this->parseMessage();
        }

        return $this->messageParameters;
    }

    /**
     * Process an incoming request
     */
    public function handleRequest()
    {
        Util::logRequest();
        if ($this->ok) {
            $this->getMessageParameters();
            if ($this->ok) {
                $this->ok = $this->authenticate();
            }
            if (!$this->ok) {
                Util::logError("Request failed with reason: '{$this->reason}'");
            }
        }
    }

    /**
     * Load the platform from the database by its consumer key.
     *
     * @param string          $key             Consumer key
     * @param DataConnector   $dataConnector   A data connector object
     * @param bool            $autoEnable      true if the platform is to be enabled automatically (optional, default is false)
     *
     * @return Platform       The platform object
     */
    public static function fromConsumerKey($key = null, $dataConnector = null, $autoEnable = false)
    {
        $platform = new Platform($dataConnector);
        $platform->key = $key;
        if (!empty($dataConnector)) {
            $ok = $dataConnector->loadPlatform($platform);
            if ($ok && $autoEnable) {
                $platform->enabled = true;
            }
        }

        return $platform;
    }

    /**
     * Load the platform from the database by its platform, client and deployment IDs.
     *
     * @param string          $platformId       The platform ID
     * @param string          $clientId         The client ID
     * @param string          $deploymentId     The deployment ID
     * @param DataConnector   $dataConnector    A data connector object
     * @param bool            $autoEnable       True if the platform is to be enabled automatically (optional, default is false)
     *
     * @return Platform       The platform object
     */
    public static function fromPlatformId($platformId, $clientId, $deploymentId, $dataConnector = null, $autoEnable = false)
    {
        $platform = new Platform($dataConnector);
        $platform->platformId = $platformId;
        $platform->clientId = $clientId;
        $platform->deploymentId = $deploymentId;
        if ($dataConnector->loadPlatform($platform)) {
            if ($autoEnable) {
                $platform->enabled = true;
            }
        }

        return $platform;
    }

    /**
     * Load the platform from the database by its record ID.
     *
     * @param string          $id               The platform record ID
     * @param DataConnector   $dataConnector    A data connector object
     *
     * @return Platform       The platform object
     */
    public static function fromRecordId($id, $dataConnector)
    {
        $platform = new Platform($dataConnector);
        $platform->setRecordId($id);
        $dataConnector->loadPlatform($platform);

        return $platform;
    }

###
###  PRIVATE METHODS
###

    /**
     * Check the authenticity of the LTI launch request.
     *
     * The platform, resource link and user objects will be initialised if the request is valid.
     *
     * @return bool    True if the request has been successfully validated.
     */
    private function authenticate()
    {
        return $this->verifySignature();
    }

}
