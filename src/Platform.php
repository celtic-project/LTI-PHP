<?php
declare(strict_types=1);

namespace ceLTIc\LTI;

use ceLTIc\LTI\DataConnector\DataConnector;
use ceLTIc\LTI\Service;
use ceLTIc\LTI\Http\HttpMessage;
use ceLTIc\LTI\Enum\IdScope;
use ceLTIc\LTI\Enum\LogLevel;
use ceLTIc\LTI\ApiHook\ApiHook;

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
    use ApiHook;

    /**
     * List of supported incoming message types.
     *
     * @var array $MESSAGE_TYPES
     */
    public static array $MESSAGE_TYPES = [
        'ContentItemSelection',
        'LtiStartAssessment'
    ];

    /**
     * Name of browser storage frame.
     *
     * @var string|null $browserStorageFrame
     */
    public static ?string $browserStorageFrame = null;

    /**
     * Platform ID.
     *
     * @var string|null $platformId
     */
    public ?string $platformId = null;

    /**
     * Client ID.
     *
     * @var string|null $clientId
     */
    public ?string $clientId = null;

    /**
     * Deployment ID.
     *
     * @var string|null $deploymentId
     */
    public ?string $deploymentId = null;

    /**
     * Authorization server ID.
     *
     * @var string|null $authorizationServerId
     */
    public ?string $authorizationServerId = null;

    /**
     * Login authentication URL.
     *
     * @var string|null $authenticationUrl
     */
    public ?string $authenticationUrl = null;

    /**
     * Access Token service URL.
     *
     * @var string|null $accessTokenUrl
     */
    public ?string $accessTokenUrl = null;

    /**
     * Name of platform (as reported by last platform connection).
     *
     * @var string|null $consumerName
     */
    public ?string $consumerName = null;

    /**
     * Platform version (as reported by last platform connection).
     *
     * @var string|null $consumerVersion
     */
    public ?string $consumerVersion = null;

    /**
     * The platform profile data.
     *
     * @var object|null $profile
     */
    public ?object $profile = null;

    /**
     * The tool proxy.
     *
     * @var object|null $toolProxy
     */
    public ?object $toolProxy = null;

    /**
     * Platform GUID (as reported by first platform connection).
     *
     * @var string|null $consumerGuid
     */
    public ?string $consumerGuid = null;

    /**
     * Optional CSS path (as reported by last platform connection).
     *
     * @var string|null $cssPath
     */
    public ?string $cssPath = null;

    /**
     * Whether the platform instance is protected by matching the consumer_guid value in incoming requests.
     *
     * @var bool $protected
     */
    public bool $protected = false;

    /**
     * Default email address (or email domain) to use when no email address is provided for a user.
     *
     * @var string $defaultEmail
     */
    public string $defaultEmail = '';

    /**
     * HttpMessage object for last service request.
     *
     * @var HttpMessage|null $lastServiceRequest
     */
    public ?HttpMessage $lastServiceRequest = null;

    /**
     * Access token to authorize service requests.
     *
     * @var AccessToken|null $accessToken
     */
    private ?AccessToken $accessToken = null;

    /**
     * Class constructor.
     *
     * @param DataConnector|null $dataConnector  A data connector object
     */
    public function __construct(?DataConnector $dataConnector = null)
    {
        $this->initialize();
        if (empty($dataConnector)) {
            $dataConnector = DataConnector::getDataConnector();
        }
        $this->dataConnector = $dataConnector;
    }

    /**
     * Initialise the platform.
     *
     * @return void
     */
    public function initialize(): void
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
        $this->settings = [];
        $this->protected = false;
        $this->enabled = false;
        $this->enableFrom = null;
        $this->enableUntil = null;
        $this->lastAccess = null;
        $this->idScope = IdScope::IdOnly;
        $this->defaultEmail = '';
        $this->created = null;
        $this->updated = null;
    }

    /**
     * Initialise the platform.
     *
     * Synonym for initialize().
     *
     * @return void
     */
    public function initialise(): void
    {
        $this->initialize();
    }

    /**
     * Save the platform to the database.
     *
     * @return bool  True if the object was successfully saved
     */
    public function save(): bool
    {
        return $this->dataConnector->savePlatform($this);
    }

    /**
     * Delete the platform from the database.
     *
     * @return bool  True if the object was successfully deleted
     */
    public function delete(): bool
    {
        return $this->dataConnector->deletePlatform($this);
    }

    /**
     * Get the platform ID.
     *
     * The ID will be the consumer key if one exists, otherwise a concatenation of the platform/client/deployment IDs
     *
     * @return string|null  Platform ID value
     */
    public function getId(): ?string
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
     * @return string  Family code
     */
    public function getFamilyCode(): string
    {
        $familyCode = '';
        if (!empty($this->consumerVersion)) {
            $familyCode = $this->consumerVersion;
            $pos = strpos($familyCode, '-');
            if ($pos !== false) {
                $familyCode = substr($familyCode, 0, $pos);
            }
        }

        return $familyCode;
    }

    /**
     * Get the data connector.
     *
     * @return DataConnector|null  Data connector object or string
     */
    public function getDataConnector(): ?DataConnector
    {
        return $this->dataConnector;
    }

    /**
     * Get the authorization access token
     *
     * @return AccessToken|null  Access token
     */
    public function getAccessToken(): ?AccessToken
    {
        return $this->accessToken;
    }

    /**
     * Set the authorization access token
     *
     * @param AccessToken $accessToken  Access token
     *
     * @return void
     */
    public function setAccessToken(AccessToken $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    /**
     * Is the platform available to accept launch requests?
     *
     * @return bool  True if the platform is enabled and within any date constraints
     */
    public function getIsAvailable(): bool
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
     * Check if the Tool Settings service is supported.
     *
     * @return bool  True if this platform supports the Tool Settings service
     */
    public function hasToolSettingsService(): bool
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
     * @param bool $simple  True if all the simple media type is to be used (optional, default is true)
     *
     * @return array|bool  The array of settings if successful, otherwise false
     */
    public function getToolSettings(bool $simple = true): array|bool
    {
        $ok = false;
        $settings = [];
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
     * @param array $settings  An associative array of settings (optional, default is none)
     *
     * @return bool  True if action was successful, otherwise false
     */
    public function setToolSettings(array $settings = []): bool
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
     * Get an array of defined tools
     *
     * @return array  Array of Tool objects
     */
    public function getTools(): array
    {
        return $this->dataConnector->getTools();
    }

    /**
     * Check if the Access Token service is supported.
     *
     * @return bool  True if this platform supports the Access Token service
     */
    public function hasAccessTokenService(): bool
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
     * @return array  The message parameter array
     */
    public function getMessageParameters(): array
    {
        if ($this->ok && is_null($this->messageParameters)) {
            $this->parseMessage(true, true, false);
        }

        return $this->messageParameters;
    }

    /**
     * Process an incoming request
     *
     * @return void
     */
    public function handleRequest(): void
    {
        $parameters = Util::getRequestParameters();
        if ($this->debugMode) {
            Util::$logLevel = LogLevel::Debug;
        }
        if ($this->ok) {
            if (!empty($parameters['client_id'])) {  // Authentication request
                Util::logRequest();
                $this->handleAuthenticationRequest();
            } else {  // LTI message
                $this->getMessageParameters();
                Util::logRequest();
                if ($this->ok && $this->authenticate()) {
                    $this->doCallback();
                }
            }
        }
        if (!$this->ok) {
            $this->onError();
        }
        if (!$this->ok) {
            $errorMessage = "Request failed with reason: '{$this->reason}'";
            if (!empty($this->details)) {
                $errorMessage .= PHP_EOL . 'Debug information:';
                foreach ($this->details as $detail) {
                    $errorMessage .= PHP_EOL . "  {$detail}";
                }
            }
            Util::logError($errorMessage);
        }
    }

    /**
     * Load the platform from the database by its consumer key.
     *
     * @param string|null $key              Consumer key
     * @param DataConnector $dataConnector  A data connector object
     * @param bool $autoEnable              True if the platform is to be enabled automatically (optional, default is false)
     *
     * @return Platform  The platform object
     */
    public static function fromConsumerKey(?string $key = null, ?DataConnector $dataConnector = null, bool $autoEnable = false): Platform
    {
        $platform = new static($dataConnector);
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
     * @param string $platformId            The platform ID
     * @param string|null $clientId         The client ID
     * @param string|null $deploymentId     The deployment ID
     * @param DataConnector $dataConnector  A data connector object
     * @param bool $autoEnable              True if the platform is to be enabled automatically (optional, default is false)
     *
     * @return Platform  The platform object
     */
    public static function fromPlatformId(string $platformId, ?string $clientId, ?string $deploymentId,
        DataConnector $dataConnector = null, bool $autoEnable = false): Platform
    {
        $platform = new static($dataConnector);
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
     * @param int|string $id                The platform record ID
     * @param DataConnector $dataConnector  A data connector object
     *
     * @return Platform  The platform object
     */
    public static function fromRecordId(int|string $id, DataConnector $dataConnector): Platform
    {
        $platform = new static($dataConnector);
        $platform->setRecordId($id);
        $dataConnector->loadPlatform($platform);

        return $platform;
    }

    /**
     * Get the JavaScript for handling storage postMessages from a tool.
     *
     * @return string  The JavaScript to handle storage postMessages
     */
    public static function getStorageJS(): string
    {
        $javascript = <<< EOD
(function () {
  let storageData = {};

  window.addEventListener('message', function (event) {
    let ok = true;
    if (typeof event.data !== "object") {
      ok = false;
      event.source.postMessage({
        subject: '.response',
        message_id: 0,
        error: {
          code: 'bad_request',
          message: 'Event data is not an object'
        }
      }, event.origin);
    }
    let messageid = '';
    if (event.data.message_id) {
      messageid = event.data.message_id;
    }
    if (!event.data.subject) {
      ok = false;
      event.source.postMessage({
        subject: '.response',
        message_id: messageid,
        error: {
          code: 'bad_request',
          message: 'There is no subject specified'
        }
      }, event.origin);
    } else if (!event.data.message_id) {
      ok = false;
      event.source.postMessage({
        subject: event.data.subject + '.response',
        message_id: messageid,
        error: {
          code: 'bad_request',
          message: 'There is no message ID specified'
        }
      }, event.origin);
    }
    if (ok) {
      switch (event.data.subject) {
        case 'lti.capabilities':
          event.source.postMessage({
            subject: 'lti.capabilities.response',
            message_id: event.data.message_id,
            supported_messages: [
              {
                subject: 'lti.capabilities'
              },
              {
                subject: 'lti.get_data'
              },
              {
                subject: 'lti.put_data'
              }
            ]
          }, event.origin);
          break;
        case 'lti.put_data':
          if (!event.data.key) {
            event.source.postMessage({
              subject: event.data.subject + '.response',
              message_id: messageid,
              error: {
                code: 'bad_request',
                message: 'There is no key specified'
              }
            }, event.origin);
          } else if (!event.data.value) {
            event.source.postMessage({
              subject: event.data.subject + '.response',
              message_id: messageid,
              error: {
                code: 'bad_request',
                message: 'There is no value specified'
              }
            }, event.origin);
          } else {
            if (!storageData[event.origin]) {
              storageData[event.origin] = {};
            }
            storageData[event.origin][event.data.key] = event.data.value;
            event.source.postMessage({
              subject: 'lti.put_data.response',
              message_id: event.data.message_id,
              key: event.data.key,
              value: event.data.value
            }, event.origin);
          }
          break;
        case 'lti.get_data':
          if (!event.data.key) {
            event.source.postMessage({
              subject: event.data.subject + '.response',
              message_id: messageid,
              error: {
                code: 'bad_request',
                message: 'There is no key specified'
              }
            }, event.origin);
          } else if (storageData[event.origin] && storageData[event.origin][event.data.key]) {
            event.source.postMessage({
              subject: 'lti.get_data.response',
              message_id: event.data.message_id,
              key: event.data.key,
              value: storageData[event.origin][event.data.key]
            }, event.origin);
          } else {
            console.log('There is no value stored with origin/key of \'' + event.origin + '/' + event.data.key + '\'');
            event.source.postMessage({
              subject: 'lti.get_data.response',
              message_id: event.data.message_id,
              error: {
                code: 'bad_request',
                message: 'There is no value stored for this key'
              }
            }, event.origin);
          }
          break;
        default:
          event.source.postMessage({
            subject: event.data.subject + '.response',
            message_id: event.data.message_id,
            error: {
              code: 'unsupported_subject',
              message: 'Subject \'' + event.data.subject + '\' not recognised'
            }
          }, event.origin);
          break;
      }
    }
  }, false);
})();

EOD;

        return $javascript;
    }

###
###    PROTECTED METHODS
###

    /**
     * Save the hint and message parameters when sending an initiate login request.
     *
     * Override this method to save the data elsewhere.
     *
     * @param string $url                  The message URL
     * @param string $loginHint            The ID of the user
     * @param string|null $ltiMessageHint  The message hint being sent to the tool
     * @param array $params                An associative array of message parameters
     *
     * @return void
     */
    protected function onInitiateLogin(string &$url, string &$loginHint, ?string &$ltiMessageHint, array $params): void
    {
        $hasSession = !empty(session_id());
        if (!$hasSession) {
            session_start();
        }
        $_SESSION['ceLTIc_lti_initiated_login'] = [
            'messageUrl' => $url,
            'login_hint' => $loginHint,
            'lti_message_hint' => $ltiMessageHint,
            'params' => $params
        ];
        if (!$hasSession) {
            session_write_close();
        }
    }

    /**
     * Check the hint and recover the message parameters for an authentication request.
     *
     * Override this method if the data has been saved elsewhere.
     *
     * @return void
     */
    protected function onAuthenticate(): void
    {
        $hasSession = !empty(session_id());
        if (!$hasSession) {
            session_start();
        }
        if (isset($_SESSION['ceLTIc_lti_initiated_login'])) {
            $login = $_SESSION['ceLTIc_lti_initiated_login'];
            $parameters = Util::getRequestParameters();
            if ($parameters['login_hint'] !== $login['login_hint'] ||
                (isset($login['lti_message_hint']) && (!isset($parameters['lti_message_hint']) || ($parameters['lti_message_hint'] !== $login['lti_message_hint'])))) {
                $this->ok = false;
                $this->messageParameters['error'] = 'access_denied';
            } else {
                Tool::$defaultTool->messageUrl = $login['messageUrl'];
                $this->messageParameters = $login['params'];
            }
            unset($_SESSION['ceLTIc_lti_initiated_login']);
        }
        if (!$hasSession) {
            session_write_close();
        }
    }

    /**
     * Process a valid content-item message
     *
     * @return void
     */
    protected function onContentItem(): void
    {
        $this->reason = 'No onContentItem method found for platform';
        $this->onError();
    }

    /**
     * Process a valid start assessment message
     *
     * @return void
     */
    protected function onLtiStartAssessment(): void
    {
        $this->reason = 'No onLtiStartAssessment method found for platform';
        $this->onError();
    }

    /**
     * Process a response to an invalid message
     *
     * @return void
     */
    protected function onError(): void
    {
        $this->ok = false;
    }

###
###  PRIVATE METHODS
###

    /**
     * Check the authenticity of the LTI message.
     *
     * The platform, resource link and user objects will be initialised if the request is valid.
     *
     * @return bool  True if the request has been successfully validated.
     */
    private function authenticate(): bool
    {
        $this->ok = $this->checkMessage();
        if ($this->ok) {
            $this->ok = $this->verifySignature();
        }

        return $this->ok;
    }

    /**
     * Process an authentication request.
     *
     * Generates an auto-submit form to respond to the request.
     *
     * @return never
     */
    private function handleAuthenticationRequest(): never
    {
        $this->messageParameters = [];
        $parameters = Util::getRequestParameters();
        $this->ok = isset($parameters['scope']) && isset($parameters['response_type']) &&
            isset($parameters['client_id']) && isset($parameters['redirect_uri']) &&
            isset($parameters['login_hint']) && isset($parameters['nonce']);
        if (!$this->ok) {
            $this->messageParameters['error'] = 'invalid_request';
        }
        if ($this->ok) {
            $scopes = explode(' ', $parameters['scope']);
            $this->ok = in_array('openid', $scopes);
            if (!$this->ok) {
                $this->messageParameters['error'] = 'invalid_scope';
            }
        }
        if ($this->ok && ($parameters['response_type'] !== 'id_token')) {
            $this->ok = false;
            $this->messageParameters['error'] = 'unsupported_response_type';
        }
        if ($this->ok && ($parameters['client_id'] !== $this->clientId)) {
            $this->ok = false;
            $this->messageParameters['error'] = 'unauthorized_client';
        }
        if ($this->ok) {
            $this->ok = in_array($parameters['redirect_uri'], Tool::$defaultTool->redirectionUris);
            if (!$this->ok) {
                $this->messageParameters['error'] = 'invalid_request';
                $this->messageParameters['error_description'] = 'Unregistered redirect_uri';
            }
        }
        if ($this->ok) {
            if (isset($parameters['response_mode'])) {
                $this->ok = ($parameters['response_mode'] === 'form_post');
            } else {
                $this->ok = false;
            }
            if (!$this->ok) {
                $this->messageParameters['error'] = 'invalid_request';
                $this->messageParameters['error_description'] = 'Invalid response_mode';
            }
        }
        if ($this->ok && (!isset($parameters['prompt']) || ($parameters['prompt'] !== 'none'))) {
            $this->ok = false;
            $this->messageParameters['error'] = 'invalid_request';
            $this->messageParameters['error_description'] = 'Invalid prompt';
        }

        if ($this->ok) {
            $this->onAuthenticate();
        }
        if ($this->ok) {
            $this->messageParameters = $this->addSignature(Tool::$defaultTool->messageUrl, $this->messageParameters, 'POST', null,
                $parameters['nonce']);
        }
        if (isset($parameters['state'])) {
            $this->messageParameters['state'] = $parameters['state'];
        }
        if (!empty(static::$browserStorageFrame)) {
            if (strpos($parameters['redirect_uri'], '?') === false) {
                $sep = '?';
            } else {
                $sep = '&';
            }
            $parameters['redirect_uri'] .= "{$sep}lti_storage_target=" . static::$browserStorageFrame;
        }
        $html = Util::sendForm($parameters['redirect_uri'], $this->messageParameters);
        echo $html;
        exit;
    }

}
