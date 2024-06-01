<?php
declare(strict_types=1);

namespace ceLTIc\LTI;

use ceLTIc\LTI\DataConnector\DataConnector;
use ceLTIc\LTI\MediaType;
use ceLTIc\LTI\Profile;
use ceLTIc\LTI\Profile\ServiceDefinition;
use ceLTIc\LTI\Content\Item;
use ceLTIc\LTI\Jwt\Jwt;
use ceLTIc\LTI\Http\HttpMessage;
use ceLTIc\LTI\OAuth;
use ceLTIc\LTI\ApiHook\ApiHook;
use ceLTIc\LTI\User;
use ceLTIc\LTI\Util;
use ceLTIc\LTI\Enum\LtiVersion;
use ceLTIc\LTI\Enum\LogLevel;

/**
 * Class to represent an LTI Tool
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class Tool
{
    use System;
    use ApiHook;

    /**
     * Default connection error message.
     */
    public const CONNECTION_ERROR_MESSAGE = 'Sorry, there was an error connecting you to the application.';

    /**
     * List of supported incoming message types.
     */
    public static array $MESSAGE_TYPES = [
        'basic-lti-launch-request',
        'ConfigureLaunchRequest',
        'DashboardRequest',
        'ContentItemSelectionRequest',
        'ContentItemUpdateRequest',
        'LtiSubmissionReviewRequest',
        'ToolProxyRegistrationRequest',
        'LtiStartProctoring',
        'LtiEndAssessment'
    ];

    /**
     * Names of LTI parameters to be retained in the consumer settings property.
     *
     * @var array $LTI_CONSUMER_SETTING_NAMES
     */
    private static array $LTI_CONSUMER_SETTING_NAMES = ['custom_tc_profile_url', 'custom_system_setting_url', 'custom_oauth2_access_token_url'];

    /**
     * Names of LTI parameters to be retained in the context settings property.
     *
     * @var array $LTI_CONTEXT_SETTING_NAMES
     */
    private static array $LTI_CONTEXT_SETTING_NAMES = ['custom_context_setting_url',
        'ext_ims_lis_memberships_id', 'ext_ims_lis_memberships_url',
        'custom_context_memberships_url', 'custom_context_memberships_v2_url',
        'custom_context_group_sets_url', 'custom_context_groups_url',
        'custom_lineitems_url', 'custom_ags_scopes'
    ];

    /**
     * Names of LTI parameters to be retained in the resource link settings property.
     *
     * @var array $LTI_RESOURCE_LINK_SETTING_NAMES
     */
    private static array $LTI_RESOURCE_LINK_SETTING_NAMES = ['lis_result_sourcedid', 'lis_outcome_service_url',
        'ext_ims_lis_basic_outcome_url', 'ext_ims_lis_resultvalue_sourcedids', 'ext_outcome_data_values_accepted',
        'ext_ims_lis_memberships_id', 'ext_ims_lis_memberships_url',
        'ext_ims_lti_tool_setting', 'ext_ims_lti_tool_setting_id', 'ext_ims_lti_tool_setting_url',
        'custom_link_setting_url', 'custom_link_memberships_url',
        'custom_lineitems_url', 'custom_lineitem_url', 'custom_ags_scopes',
        'custom_ap_acs_url'
    ];

    /**
     * Names of LTI parameters to be retained even when not passed.
     *
     * @var array $LTI_RETAIN_SETTING_NAMES
     */
    private static array $LTI_RETAIN_SETTING_NAMES = ['custom_lineitem_url'];

    /**
     * Names of LTI custom parameter substitution variables (or capabilities) and their associated default message parameter names.
     *
     * @var array $CUSTOM_SUBSTITUTION_VARIABLES
     */
    private static array $CUSTOM_SUBSTITUTION_VARIABLES = [
        'User.id' => 'user_id',
        'User.image' => 'user_image',
        'User.username' => 'username',
        'User.scope.mentor' => 'role_scope_mentor',
        'Membership.role' => 'roles',
        'Person.sourcedId' => 'lis_person_sourcedid',
        'Person.name.full' => 'lis_person_name_full',
        'Person.name.family' => 'lis_person_name_family',
        'Person.name.given' => 'lis_person_name_given',
        'Person.name.middle' => 'lis_person_name_middle',
        'Person.email.primary' => 'lis_person_contact_email_primary',
        'Context.id' => 'context_id',
        'Context.type' => 'context_type',
        'Context.title' => 'context_title',
        'Context.label' => 'context_label',
        'CourseOffering.sourcedId' => 'lis_course_offering_sourcedid',
        'CourseSection.sourcedId' => 'lis_course_section_sourcedid',
        'CourseSection.label' => 'context_label',
        'CourseSection.title' => 'context_title',
        'ResourceLink.id' => 'resource_link_id',
        'ResourceLink.title' => 'resource_link_title',
        'ResourceLink.description' => 'resource_link_description',
        'Result.sourcedId' => 'lis_result_sourcedid',
        'BasicOutcome.url' => 'lis_outcome_service_url',
        'ToolConsumerProfile.url' => 'custom_tc_profile_url',
        'ToolProxy.url' => 'tool_proxy_url',
        'ToolProxy.custom.url' => 'custom_system_setting_url',
        'ToolProxyBinding.custom.url' => 'custom_context_setting_url',
        'LtiLink.custom.url' => 'custom_link_setting_url',
        'LineItems.url' => 'custom_lineitems_url',
        'LineItem.url' => 'custom_lineitem_url',
        'ToolProxyBinding.memberships.url' => 'custom_context_memberships_url',
        'ToolProxyBinding.nrps.url' => 'custom_context_memberships_v2_url',
        'LtiLink.memberships.url' => 'custom_link_memberships_url',
        'LtiLink.acs.url' => 'custom_ap_acs_url'
    ];

    /**
     * Platform object.
     *
     * @var Platform|null $platform
     */
    public ?Platform $platform = null;

    /**
     * Return URL provided by platform.
     *
     * @var string|null $returnUrl
     */
    public ?string $returnUrl = null;

    /**
     * UserResult object.
     *
     * @var UserResult|null $userResult
     */
    public ?UserResult $userResult = null;

    /**
     * Resource link object.
     *
     * @var ResourceLink|null $resourceLink
     */
    public ?ResourceLink $resourceLink = null;

    /**
     * Context object.
     *
     * @var Context|null $context
     */
    public ?Context $context = null;

    /**
     * Default email domain.
     *
     * @var string $defaultEmail
     */
    public string $defaultEmail = '';

    /**
     * Whether shared resource link arrangements are permitted.
     *
     * @var bool $allowSharing
     */
    public bool $allowSharing = false;

    /**
     * Message for last request processed
     *
     * @var string|null $message
     */
    public ?string $message = null;

    /**
     * Base URL for tool service
     *
     * @var string|null $baseUrl
     */
    public ?string $baseUrl = null;

    /**
     * Vendor details
     *
     * @var Profile\Item|null $vendor
     */
    public ?Profile\Item $vendor = null;

    /**
     * Product details
     *
     * @var Profile\Item|null $product
     */
    public ?Profile\Item $product = null;

    /**
     * Services required by Tool
     *
     * @var array|null $requiredServices
     */
    public ?array $requiredServices = null;

    /**
     * Optional services used by Tool
     *
     * @var array|null $optionalServices
     */
    public ?array $optionalServices = null;

    /**
     * Resource handlers for Tool
     *
     * @var array|null $resourceHandlers
     */
    public ?array $resourceHandlers = null;

    /**
     * Message URL for Tool
     *
     * @var string|null $messageUrl
     */
    public ?string $messageUrl = null;

    /**
     * Initiate Login request URL for Tool
     *
     * @var string|null $initiateLoginUrl
     */
    public ?string $initiateLoginUrl = null;

    /**
     * Redirection URIs for Tool
     *
     * @var array|null $redirectionUris
     */
    public ?array $redirectionUris = null;

    /**
     * Default tool for use with service requests
     *
     * @var Tool|null $defaultTool
     */
    public static ?Tool $defaultTool = null;

    /**
     * Use GET method for authentication request messages when true
     *
     * @var bool $authenticateUsingGet
     */
    public static bool $authenticateUsingGet = false;

    /**
     * Life in seconds for the state value issued during the OIDC login process
     *
     * @var int $stateLife
     */
    public static int $stateLife = 10;

    /**
     * Period in milliseconds to wait for a response to a postMessage
     *
     * @var int $postMessageTimeoutDelay
     */
    public static int $postMessageTimeoutDelay = 20;

    /**
     * URL to redirect user to on successful completion of the request.
     *
     * @var string|null $redirectUrl
     */
    protected ?string $redirectUrl = null;

    /**
     * Media types accepted by the platform.
     *
     * @var array|null $mediaTypes
     */
    protected ?array $mediaTypes = null;

    /**
     * Content item types accepted by the platform.
     *
     * @var array|null $contentTypes
     */
    protected ?array $contentTypes = null;

    /**
     * File types accepted by the platform.
     *
     * @var array|null $fileTypes
     */
    protected ?array $fileTypes = null;

    /**
     * Document targets accepted by the platform.
     *
     * @var array|null $documentTargets
     */
    protected ?array $documentTargets = null;

    /**
     * Default HTML to be displayed on a successful completion of the request.
     *
     * @var string|null $output
     */
    protected ?string $output = null;

    /**
     * HTML to be displayed on an unsuccessful completion of the request and no return URL is available.
     *
     * @var string|null $errorOutput
     */
    protected ?string $errorOutput = null;

    /**
     * LTI parameter constraints for auto validation checks.
     *
     * @var array|null $constraints
     */
    private ?array $constraints = null;

    /**
     * Class constructor
     *
     * @param DataConnector $dataConnector  Object containing a database connection object
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
     * Initialise the tool.
     *
     * @return void
     */
    public function initialize(): void
    {
        $this->id = null;
        $this->key = null;
        $this->name = null;
        $this->secret = null;
        $this->messageUrl = null;
        $this->initiateLoginUrl = null;
        $this->redirectionUris = null;
        $this->rsaKey = null;
        $this->signatureMethod = 'HMAC-SHA1';
        $this->encryptionMethod = null;
        $this->ltiVersion = null;
        $this->settings = [];
        $this->enabled = false;
        $this->enableFrom = null;
        $this->enableUntil = null;
        $this->lastAccess = null;
        $this->created = null;
        $this->updated = null;
        $this->constraints = [];
        $this->vendor = new Profile\Item();
        $this->product = new Profile\Item();
        $this->requiredServices = [];
        $this->optionalServices = [];
        $this->resourceHandlers = [];
    }

    /**
     * Save the tool to the database.
     *
     * @return bool  True if the object was successfully saved
     */
    public function save(): bool
    {
        return $this->dataConnector->saveTool($this);
    }

    /**
     * Delete the tool from the database.
     *
     * @return bool  True if the object was successfully deleted
     */
    public function delete(): bool
    {
        return $this->dataConnector->deleteTool($this);
    }

    /**
     * Get the message parameters.
     *
     * @param bool $strictMode          True if full compliance with the LTI specification is required (optional, default is false)
     * @param bool $disableCookieCheck  True if no cookie check should be made (optional, default is false)
     * @param bool $generateWarnings    True if warning messages should be generated (optional, default is false)
     *
     * @return array|null  The message parameter array
     */
    public function getMessageParameters(bool $strictMode = false, bool $disableCookieCheck = false, bool $generateWarnings = false): ?array
    {
        if (is_null($this->messageParameters)) {
            $this->parseMessage($strictMode, $disableCookieCheck, $generateWarnings);
// Set debug mode
            if (!Util::$logLevel->logDebug()) {
                $this->debugMode = (isset($this->messageParameters['custom_debug']) &&
                    (strtolower($this->messageParameters['custom_debug']) === 'true'));
                if ($this->debugMode) {
                    Util::$logLevel = LogLevel::Debug;
                }
            }
// Set return URL if available
            if (!empty($this->messageParameters['lti_message_type']) &&
                (($this->messageParameters['lti_message_type'] === 'ContentItemSelectionRequest') || ($this->messageParameters['lti_message_type'] === 'ContentItemUpdateRequest')) &&
                !empty($this->messageParameters['content_item_return_url'])) {
                $this->returnUrl = $this->messageParameters['content_item_return_url'];
            }
            if (empty($this->returnUrl) && !empty($this->messageParameters['launch_presentation_return_url'])) {
                $this->returnUrl = $this->messageParameters['launch_presentation_return_url'];
            }
        }

        return $this->messageParameters;
    }

    /**
     * Process an incoming request
     *
     * @param bool $strictMode          True if full compliance with the LTI specification is required (optional, default is false)
     * @param bool $disableCookieCheck  True if no cookie check should be made (optional, default is false)
     * @param bool $generateWarnings    True if warning messages should be generated (optional, default is false)
     */
    public function handleRequest(bool $strictMode = false, bool $disableCookieCheck = false, bool $generateWarnings = false): void
    {
        $parameters = Util::getRequestParameters();
        if ($this->debugMode) {
            Util::$logLevel = LogLevel::Debug;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {  // Ignore HEAD requests
            Util::logRequest(true);
        } elseif (isset($parameters['iss']) && (strlen($parameters['iss']) > 0)) {  // Initiate login request
            Util::logRequest();
            if (!isset($parameters['login_hint']) || (strlen($parameters['login_hint']) <= 0)) {
                $this->ok = false;
                $this->reason = 'Missing login_hint parameter.';
            } elseif (!isset($parameters['target_link_uri']) || (strlen($parameters['target_link_uri']) <= 0)) {
                $this->ok = false;
                $this->reason = 'Missing target_link_uri parameter.';
            } else {
                $this->ok = $this->sendAuthenticationRequest($parameters, $disableCookieCheck);
            }
        } elseif (isset($parameters['openid_configuration']) && (strlen($parameters['openid_configuration']) > 0)) {  // Dynamic registration request
            Util::logRequest();
            $this->onRegistration();
        } else {  // LTI message
            $this->getMessageParameters($strictMode, $disableCookieCheck, $generateWarnings);
            Util::logRequest();
            if ($this->ok && $this->authenticate($strictMode, $disableCookieCheck, $generateWarnings)) {
                if (empty($this->output)) {
                    $this->doCallback();
                    if ($this->ok && ($this->messageParameters['lti_message_type'] === 'ToolProxyRegistrationRequest')) {
                        $this->platform->save();
                    }
                }
            }
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
        $this->result();
    }

    /**
     * Add a parameter constraint to be checked on launch
     *
     * @param string $name              Name of parameter to be checked
     * @param bool $required            True if parameter is required (optional, default is true)
     * @param int|null $maxLength       Maximum permitted length of parameter value (optional, default is null)
     * @param array|null $messageTypes  Array of message types to which the constraint applies (optional, default is all)
     */
    public function setParameterConstraint(string $name, bool $required = true, ?int $maxLength = null, ?array $messageTypes = null): void
    {
        $name = trim($name);
        if (!empty($name)) {
            $this->constraints[$name] = [
                'required' => $required,
                'max_length' => $maxLength,
                'messages' => $messageTypes
            ];
        }
    }

    /**
     * Get an array of defined platforms
     *
     * @return array  Array of Platform objects
     */
    public function getPlatforms(): array
    {
        return $this->dataConnector->getPlatforms();
    }

    /**
     * Find an offered service based on a media type and HTTP action(s)
     *
     * @param string $format  Media type required
     * @param array $methods  Array of HTTP actions required
     *
     * @return ServiceDefinition|bool  The service object if found, otherwise false
     */
    public function findService(string $format, array $methods): ServiceDefinition|bool
    {
        $found = false;
        $services = $this->platform->profile->service_offered;
        if (is_array($services)) {
            foreach ($services as $service) {
                if (!is_array($service->format) || !in_array($format, $service->format)) {
                    continue;
                }
                $missing = [];
                foreach ($methods as $method) {
                    if (!is_array($service->action) || !in_array($method, $service->action)) {
                        $missing[] = $method;
                    }
                }
                if (count($missing) <= 0) {
                    $found = new ServiceDefinition($service->format, $service->action);
                    if (!empty($service->{'@id'})) {
                        $found->id = $service->{'@id'};
                    }
                    if (!empty($service->endpoint)) {
                        $found->endpoint = $service->endpoint;
                    }
                    break;
                }
            }
        }

        return $found;
    }

    /**
     * Send the tool proxy to the platform
     *
     * @return bool  True if the tool proxy was accepted
     */
    public function doToolProxyService(): bool
    {
// Create tool proxy
        $toolProxyService = $this->findService('application/vnd.ims.lti.v2.toolproxy+json', ['POST']);
        $secret = Util::getRandomString(12);
        $toolProxy = new MediaType\ToolProxy($this, $toolProxyService, $secret);
        $http = $this->platform->doServiceRequest($toolProxyService, 'POST', 'application/vnd.ims.lti.v2.toolproxy+json',
            json_encode($toolProxy));
        $ok = $http->ok && ($http->status === 201) && !empty($http->responseJson->tool_proxy_guid);
        if ($ok) {
            $this->platform->setKey($http->responseJson->tool_proxy_guid);
            $this->platform->secret = $toolProxy->security_contract->shared_secret;
            $this->platform->toolProxy = json_encode($toolProxy);
            $this->platform->save();
        }

        return $ok;
    }

###
###    PROTECTED METHODS
###

    /**
     * Process a valid launch request
     *
     * @return void
     */
    protected function onLaunch(): void
    {
        $this->reason = 'No onLaunch method found for tool.';
        $this->onError();
    }

    /**
     * Process a valid configure request
     *
     * @return void
     */
    protected function onConfigure(): void
    {
        $this->reason = 'No onConfigure method found for tool.';
        $this->onError();
    }

    /**
     * Process a valid dashboard request
     *
     * @return void
     */
    protected function onDashboard(): void
    {
        $this->reason = 'No onDashboard method found for tool.';
        $this->onError();
    }

    /**
     * Process a valid content-item request
     *
     * @return void
     */
    protected function onContentItem(): void
    {
        $this->reason = 'No onContentItem method found for tool.';
        $this->onError();
    }

    /**
     * Process a valid content-item update request
     *
     * @return void
     */
    protected function onContentItemUpdate(): void
    {
        $this->reason = 'No onContentItemUpdate method found for tool.';
        $this->onError();
    }

    /**
     * Process a valid submission review request
     *
     * @return void
     */
    protected function onSubmissionReview(): void
    {
        $this->reason = 'No onSubmissionReview method found for tool.';
        $this->onError();
    }

    /**
     * Process a dynamic registration request
     *
     * @return void
     */
    protected function onRegistration(): void
    {
        $platformConfig = $this->getPlatformConfiguration();
        if ($this->ok) {
            $toolConfig = $this->getConfiguration($platformConfig);
            $registrationConfig = $this->sendRegistration($platformConfig, $toolConfig);
            if ($this->ok) {
                $this->getPlatformToRegister($platformConfig, $registrationConfig);
            }
        }
        $this->getRegistrationResponsePage($toolConfig);
        $this->ok = true;
    }

    /**
     * Process a valid start proctoring request
     */
    protected function onLtiStartProctoring(): void
    {
        $this->reason = 'No onLtiStartProctoring method found for tool.';
        $this->onError();
    }

    /**
     * Process a valid end assessment request
     *
     * @return void
     */
    protected function onLtiEndAssessment(): void
    {
        $this->reason = 'No onLtiEndAssessment method found for tool.';
        $this->onError();
    }

    /**
     * Process a login initiation request
     *
     * @param array $requestParameters  Request parameters
     * @param array $authParameters     Authentication request parameters
     *
     * @return void
     */
    protected function onInitiateLogin(array $requestParameters, array &$authParameters): void
    {
        $hasSession = !empty(session_id());
        if (!$hasSession) {
            session_start();
        }
        $_SESSION['ceLTIc_lti_authentication_request'] = [
            'state' => $authParameters['state'],
            'nonce' => $authParameters['nonce']
        ];
        if (!$hasSession) {
            session_write_close();
        }
    }

    /**
     * Process response to an authentication request
     *
     * @param string $state             State value
     * @param string $nonce             Nonce value
     * @param bool $usePlatformStorage  True if platform storage is being used
     *
     * @return void
     */
    protected function onAuthenticate(string $state, string $nonce, bool $usePlatformStorage): void
    {
        $hasSession = !empty(session_id());
        if (!$hasSession) {
            session_start();
        }
        $parts = explode('.', $state);
        if (!isset($this->rawParameters['_storage_check']) && $usePlatformStorage) {  // Check browser storage
            $this->rawParameters['_storage_check'] = '';
            $javascript = $this->getStorageJS('lti.get_data', $state, '');
            echo Util::sendForm($_SERVER['REQUEST_URI'], $this->rawParameters, '', $javascript);
            exit;
        } elseif (isset($this->rawParameters['_storage_check'])) {
            if (!empty(($this->rawParameters['_storage_check']))) {
                $state = $parts[0];
                $parts = explode('.', $this->rawParameters['_storage_check']);
                if ((count($parts) !== 2) || ($parts[0] !== $state) || ($parts[1] !== $nonce)) {
                    $this->ok = false;
                    $this->reason = 'Invalid state and/or nonce values';
                }
            } else {
                $this->ok = false;
                $this->reason = 'Error accessing platform storage';
            }
        } elseif (isset($_SESSION['ceLTIc_lti_authentication_request'])) {
            $auth = $_SESSION['ceLTIc_lti_authentication_request'];
            if (str_ends_with($state, '.platformStorage')) {
                $state = substr($state, 0, -16);
            }
            if (($state !== $auth['state']) || ($nonce !== $auth['nonce'])) {
                $this->ok = false;
                $this->reason = 'Invalid state parameter value and/or nonce claim value';
            }
            unset($_SESSION['ceLTIc_lti_authentication_request']);
        }
        if (!$hasSession) {
            session_write_close();
        }
    }

    /**
     * Process a change in the session ID
     *
     * @return void
     */
    protected function onResetSessionId(): void
    {

    }

    /**
     * Process a response to an invalid request
     *
     * @return void
     */
    protected function onError(): void
    {
        $this->ok = false;
    }

    /**
     * Fetch a platform's configuration data
     *
     * @return array|null  Platform configuration data
     */
    protected function getPlatformConfiguration(): ?array
    {
        if ($this->ok) {
            $parameters = Util::getRequestParameters();
            $this->ok = !empty($parameters['openid_configuration']);
            if ($this->ok) {
                $http = new HttpMessage($parameters['openid_configuration']);
                $this->ok = $http->send();
                if ($this->ok) {
                    $platformConfig = Util::jsonDecode($http->response, true);
                    $this->ok = !empty($platformConfig);
                }
                if (!$this->ok) {
                    $this->reason = 'Unable to access platform configuration details.';
                }
            } else {
                $this->reason = 'Invalid registration request: missing openid_configuration parameter.';
            }
            if ($this->ok) {
                $this->ok = !empty($platformConfig['registration_endpoint']) && !empty($platformConfig['jwks_uri']) && !empty($platformConfig['authorization_endpoint']) &&
                    !empty($platformConfig['token_endpoint']) && !empty($platformConfig['https://purl.imsglobal.org/spec/lti-platform-configuration']) &&
                    !empty($platformConfig['claims_supported']) && !empty($platformConfig['scopes_supported']) &&
                    !empty($platformConfig['id_token_signing_alg_values_supported']) &&
                    !empty($platformConfig['https://purl.imsglobal.org/spec/lti-platform-configuration']['product_family_code']) &&
                    !empty($platformConfig['https://purl.imsglobal.org/spec/lti-platform-configuration']['version']) &&
                    !empty($platformConfig['https://purl.imsglobal.org/spec/lti-platform-configuration']['messages_supported']);
                if (!$this->ok) {
                    $this->reason = 'Invalid platform configuration details.';
                }
            }
            if ($this->ok) {
                $jwtClient = Jwt::getJwtClient();
                $algorithms = \array_intersect($jwtClient::getSupportedAlgorithms(),
                    $platformConfig['id_token_signing_alg_values_supported']);
                $this->ok = !empty($algorithms);
                if ($this->ok) {
                    rsort($platformConfig['id_token_signing_alg_values_supported']);
                } else {
                    $this->reason = 'None of the signature algorithms offered by the platform is supported.';
                }
            }
        }
        if (!$this->ok) {
            $platformConfig = null;
        }

        return $platformConfig;
    }

    /**
     * Prepare the tool's configuration data
     *
     * @param array $platformConfig  Platform configuration data
     *
     * @return array  Tool configuration data
     */
    protected function getConfiguration(array $platformConfig): array
    {
        $claimsMapping = [
            'User.id' => 'sub',
            'Person.name.full' => 'name',
            'Person.name.given' => 'given_name',
            'Person.name.middle' => 'middle_name',
            'Person.name.family' => 'family_name',
            'Person.email.primary' => 'email'
        ];
        $toolName = (!empty($this->product->name)) ? $this->product->name : 'Unnamed tool';
        $toolDescription = (!empty($this->product->description)) ? $this->product->description : '';
        $oauthRequest = OAuth\OAuthRequest::from_request();
        $toolUrl = $oauthRequest->get_normalized_http_url();
        $pos = strpos($toolUrl, '//');
        $domain = substr($toolUrl, $pos + 2);
        $domain = substr($domain, 0, strpos($domain, '/'));
        $claimsSupported = $platformConfig['claims_supported'];
        $messagesSupported = [];
        foreach ($platformConfig['https://purl.imsglobal.org/spec/lti-platform-configuration']['messages_supported'] as $message) {
            $messagesSupported[] = $message['type'];
        }
        $scopesSupported = $platformConfig['scopes_supported'];
        $iconUrl = null;
        $messages = [];
        $claims = ['iss'];
        $variables = [];
        $constants = [];
        $redirectUris = [];
        foreach ($this->resourceHandlers as $resourceHandler) {
            if (empty($iconUrl)) {
                $iconUrl = $resourceHandler->icon;
            }
            foreach (array_merge($resourceHandler->optionalMessages, $resourceHandler->requiredMessages) as $message) {
                $type = $message->type;
                if (array_key_exists($type, Util::MESSAGE_TYPE_MAPPING)) {
                    $type = Util::MESSAGE_TYPE_MAPPING[$type];
                }
                $capabilities = [];
                if ($type === 'LtiResourceLinkRequest') {
                    $toolUrl = "{$this->baseUrl}{$message->path}";
                    $redirectUris[] = $toolUrl;
                    $capabilities = $message->capabilities;
                    $variables = array_merge($variables, $message->variables);
                    $constants = array_merge($constants, $message->constants);
                } elseif (in_array($type, $messagesSupported)) {
                    $redirectUris[] = "{$this->baseUrl}{$message->path}";
                    $capabilities = $message->capabilities;
                    $variables = array_merge($message->variables, $variables);
                    $constants = array_merge($message->constants, $constants);
                    $messages[] = [
                        'type' => $type,
                        'target_link_uri' => "{$this->baseUrl}{$message->path}",
                        'label' => $toolName
                    ];
                }
                foreach ($capabilities as $capability) {
                    if (array_key_exists($capability, $claimsMapping) && in_array($claimsMapping[$capability], $claimsSupported)) {
                        $claims[] = $claimsMapping[$capability];
                    }
                }
            }
        }
        if (empty($redirectUris)) {
            $redirectUris = [$toolUrl];
        } else {
            $redirectUris = array_unique($redirectUris);
        }
        if (!empty($claims)) {
            $claims = array_unique($claims);
        }
        $custom = new \stdClass();
        foreach ($constants as $name => $value) {
            $custom->{$name} = $value;
        }
        foreach ($variables as $name => $value) {
            $custom->{$name} = '$' . $value;
        }
        $toolConfig = [];
        $toolConfig['application_type'] = 'web';
        $toolConfig['client_name'] = $toolName;
        $toolConfig['response_types'] = ['id_token'];
        $toolConfig['grant_types'] = ['implicit', 'client_credentials'];
        $toolConfig['initiate_login_uri'] = $toolUrl;
        $toolConfig['redirect_uris'] = $redirectUris;
        $toolConfig['jwks_uri'] = $this->jku;
        $toolConfig['token_endpoint_auth_method'] = 'private_key_jwt';
        $toolConfig['https://purl.imsglobal.org/spec/lti-tool-configuration'] = [
            'domain' => $domain,
            'target_link_uri' => $toolUrl,
            'custom_parameters' => $custom,
            'claims' => $claims,
            'messages' => $messages,
            'description' => $toolDescription
        ];
        $toolConfig['scope'] = implode(' ', array_intersect($this->requiredScopes, $scopesSupported));
        if (!empty($iconUrl)) {
            if ((strpos($iconUrl, '://') === false) && !empty($this->baseUrl)) {
                $iconUrl = "{$this->baseUrl}{$iconUrl}";
            }
            $toolConfig['logo_uri'] = $iconUrl;
        }

        return $toolConfig;
    }

    /**
     * Send the tool registration to the platform
     *
     * @param array $platformConfig  Platform configuration data
     * @param array $toolConfig      Tool configuration data
     *
     * @return array|null  Registration data
     */
    protected function sendRegistration(array $platformConfig, array $toolConfig): ?array
    {
        if ($this->ok) {
            $parameters = Util::getRequestParameters();
            $body = json_encode($toolConfig);
            $headers = "Content-type: application/json; charset=UTF-8";
            if (!empty($parameters['registration_token'])) {
                $headers .= "\nAuthorization: Bearer {$parameters['registration_token']}";
            }
            $http = new HttpMessage($platformConfig['registration_endpoint'], 'POST', $body, $headers);
            $this->ok = $http->send();
            if ($this->ok) {
                $registrationConfig = Util::jsonDecode($http->response, true);
                $this->ok = !empty($registrationConfig);
            }
            if (!$this->ok) {
                $this->reason = 'Unable to register with platform.';
            }
        }
        if (!$this->ok) {
            $registrationConfig = null;
        }

        return $registrationConfig;
    }

    /**
     * Initialise the platform to be registered
     *
     * @param array $platformConfig      Platform configuration data
     * @param array $registrationConfig  Registration data
     * @param bool $doSave               True if the platform should be saved (optional, default is true)
     *
     * @return Platform  Platform object
     */
    protected function getPlatformToRegister(array $platformConfig, array $registrationConfig, bool $doSave = true): Platform
    {
        $domain = $platformConfig['issuer'];
        $pos = strpos($domain, '//');
        if ($pos !== false) {
            $domain = substr($domain, $pos + 2);
            $pos = strpos($domain, '/');
            if ($pos !== false) {
                $domain = substr($domain, 0, $pos);
            }
        }
        $this->platform = new Platform($this->dataConnector);
        $this->platform->name = $domain;
        $this->platform->ltiVersion = LtiVersion::V1P3;
        $this->platform->signatureMethod = reset($platformConfig['id_token_signing_alg_values_supported']);
        $this->platform->platformId = $platformConfig['issuer'];
        $this->platform->clientId = $registrationConfig['client_id'];
        $this->platform->deploymentId = $registrationConfig['https://purl.imsglobal.org/spec/lti-tool-configuration']['deployment_id'] ?? '';
        $this->platform->authenticationUrl = $platformConfig['authorization_endpoint'];
        $this->platform->accessTokenUrl = $platformConfig['token_endpoint'];
        $this->platform->jku = $platformConfig['jwks_uri'];
        if ($doSave) {
            $this->ok = $this->platform->save();
            if (!$this->ok) {
                $this->reason = 'Sorry, an error occurred when saving the platform details.';
            }
        }

        return $this->platform;
    }

    /**
     * Prepare the page to complete a registration request
     *
     * @param array $toolConfig  Tool configuration data
     *
     * @return void
     */
    protected function getRegistrationResponsePage(array $toolConfig): void
    {
        $enabled = '';
        if (!empty($this->platform)) {
            $now = time();
            if (!$this->platform->enabled) {
                $enabled = ', but it will need to be enabled by the tool provider before it can be used';
            } elseif (!empty($this->platform->enableFrom) && ($this->platform->enableFrom > $now)) {
                $enabled = ', but you will only have access from ' . date('j F Y H:i T', $this->platform->enableFrom);
                if (!empty($this->platform->enableUntil)) {
                    $enabled .= ' until ' . date('j F Y H:i T', $this->platform->enableUntil);
                }
            } elseif (!empty($this->platform->enableUntil)) {
                if ($this->platform->enableUntil > $now) {
                    $enabled = ', but you will only have access until ' . date('j F Y H:i T', $this->platform->enableUntil);
                } else {
                    $enabled = ', but your access was set to end at ' . date('j F Y H:i T', $this->platform->enableUntil);
                }
            }
        }
        $html = <<< EOD
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title>LTI Tool registration</title>
    <style>
      h1 {
        font-soze: 110%;
        font-weight: bold;
      }
      .success {
        color: #155724;
        background-color: #d4edda;
        border-color: #c3e6cb;
        border: 1px solid;
        padding: .75rem 1.25rem;
        margin-bottom: 1rem;
      }
      .error {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
        border: 1px solid;
        padding: .75rem 1.25rem;
        margin-bottom: 1rem;
      }
      .centre {
        text-align: center;
      }
      button {
        border: 1px solid transparent;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
        border-radius: 0.25rem;
        color: #fff;
        background-color: #007bff;
        border-color: #007bff;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        cursor: pointer;
      }
    </style>
    <script language="javascript" type="text/javascript">
      function doClose(el) {
        (window.opener || window.parent).postMessage({subject:'org.imsglobal.lti.close'}, '*');
        return true;
      }
    </script>
  </head>
  <body>
    <h1>{$toolConfig['client_name']} registration</h1>

EOD;
        if ($this->ok) {
            $html .= <<< EOD
    <p class="success">
      The tool registration was successful{$enabled}.
    </p>
    <p class="centre">
      <button type="button" onclick="return doClose();">Close</button>
    </p>

EOD;
        } else {
            $html .= <<< EOD
    <p class="error">
      Sorry, the registration was not successful: {$this->reason}
    </p>

EOD;
        }
        $html .= <<< EOD
  </body>
</html>
EOD;
        $this->output = $html;
    }

    /**
     * Load the tool from the database by its consumer key.
     *
     * @param string|null $key              Consumer key
     * @param DataConnector $dataConnector  A data connector object
     * @param bool $autoEnable              True if the tool is to be enabled automatically (optional, default is false)
     *
     * @return Tool  The tool object
     */
    public static function fromConsumerKey(?string $key = null, DataConnector $dataConnector = null, bool $autoEnable = false): Tool
    {
        $tool = new static($dataConnector);
        $tool->key = $key;
        if (!empty($dataConnector)) {
            $ok = $dataConnector->loadTool($tool);
            if ($ok && $autoEnable) {
                $tool->enabled = true;
            }
        }

        return $tool;
    }

    /**
     * Load the tool from the database by its initiate login URL.
     *
     * @param string $initiateLoginUrl      The initiate login URL
     * @param DataConnector $dataConnector  A data connector object
     * @param bool $autoEnable              True if the tool is to be enabled automatically (optional, default is false)
     *
     * @return Tool  The tool object
     */
    public static function fromInitiateLoginUrl(string $initiateLoginUrl, DataConnector $dataConnector = null,
        bool $autoEnable = false): Tool
    {
        $tool = new static($dataConnector);
        $tool->initiateLoginUrl = $initiateLoginUrl;
        if ($dataConnector->loadTool($tool)) {
            if ($autoEnable) {
                $tool->enabled = true;
            }
        }

        return $tool;
    }

    /**
     * Load the tool from the database by its record ID.
     *
     * @param int $id                       The tool record ID
     * @param DataConnector $dataConnector  A data connector object
     *
     * @return Tool  The tool object
     */
    public static function fromRecordId(int $id, DataConnector $dataConnector): Tool
    {
        $tool = new static($dataConnector);
        $tool->setRecordId($id);
        $dataConnector->loadTool($tool);

        return $tool;
    }

###
###    PRIVATE METHODS
###

    /**
     * Perform the result of an action.
     *
     * @return void
     */
    private function result(): void
    {
        if (!$this->ok) {
            $this->message = self::CONNECTION_ERROR_MESSAGE;
            $this->onError();
        }
        if (!$this->ok) {
// If not valid, return an error message to the platform if a return URL is provided
            if (!empty($this->returnUrl)) {
                $errorUrl = $this->returnUrl;
                if (!is_null($this->platform) && isset($this->messageParameters['lti_message_type']) &&
                    (($this->messageParameters['lti_message_type'] === 'ContentItemSelectionRequest') ||
                    ($this->messageParameters['lti_message_type'] === 'ContentItemUpdateRequest'))) {
                    $formParams = [];
                    if ($this->debugMode && !is_null($this->reason)) {
                        $formParams['lti_errormsg'] = "Debug error: {$this->reason}";
                    } else {
                        $formParams['lti_errormsg'] = $this->message;
                        if (!is_null($this->reason)) {
                            $formParams['lti_errorlog'] = "Debug error: {$this->reason}";
                        }
                    }
                    if (isset($this->messageParameters['data'])) {
                        $formParams['data'] = $this->messageParameters['data'];
                    }
                    if (empty($this->ltiVersion)) {
                        $this->ltiVersion = LtiVersion::V1;
                    }
                    $page = $this->sendMessage($errorUrl, 'ContentItemSelection', $formParams);
                    echo $page;
                    exit;
                } else {
                    if (strpos($errorUrl, '?') === false) {
                        $errorUrl .= '?';
                    } else {
                        $errorUrl .= '&';
                    }
                    if ($this->debugMode && !is_null($this->reason)) {
                        $errorUrl .= 'lti_errormsg=' . Util::urlEncode("Debug error: {$this->reason}");
                    } else {
                        $errorUrl .= 'lti_errormsg=' . Util::urlEncode($this->message);
                        if (!is_null($this->reason)) {
                            $errorUrl .= '&lti_errorlog=' . Util::urlEncode("Debug error: {$this->reason}");
                        }
                    }
                    header("Location: {$errorUrl}");
                }
                exit;
            } else {
                if (!is_null($this->errorOutput)) {
                    echo $this->errorOutput;
                } elseif ($this->debugMode && !empty($this->reason)) {
                    echo "Debug error: {$this->reason}";
                } else {
                    echo "Error: {$this->message}";
                }
                exit;
            }
        } elseif (!is_null($this->redirectUrl)) {
            header("Location: {$this->redirectUrl}");
            exit;
        } elseif (!is_null($this->output)) {
            echo $this->output;
            exit;
        }
    }

    /**
     * Check the authenticity of the LTI message.
     *
     * The platform, resource link and user objects will be initialised if the request is valid.
     *
     * @param bool $strictMode          True if full compliance with the LTI specification is required
     * @param bool $disableCookieCheck  True if no cookie check should be made
     * @param bool $generateWarnings    True if warning messages should be generated
     *
     * @return bool  True if the request has been successfully validated.
     */
    private function authenticate(bool $strictMode, bool $disableCookieCheck, bool $generateWarnings): bool
    {
        $doSavePlatform = false;
        $this->ok = $this->checkMessage();
        if (($this->ok || $generateWarnings) && !empty($this->jwt) && !empty($this->jwt->hasJwt())) {
            if ($this->jwt->hasClaim('sub') && (strlen($this->jwt->getClaim('sub')) <= 0)) {
                $this->setError('Empty sub claim', $strictMode, $generateWarnings);
            }
            if (!empty($this->jwt->getClaim('https://purl.imsglobal.org/spec/lti/claim/context', '')) &&
                empty($this->messageParameters['context_id'])) {
                $this->setError('Missing id property in https://purl.imsglobal.org/spec/lti/claim/context claim', $strictMode,
                    $generateWarnings);
            } elseif (!empty($this->jwt->getClaim('https://purl.imsglobal.org/spec/lti/claim/tool_platform', '')) &&
                empty($this->messageParameters['tool_consumer_instance_guid'])) {
                $this->setError('Missing guid property in https://purl.imsglobal.org/spec/lti/claim/tool_platform claim',
                    $strictMode, $generateWarnings);
            }
        }
        if (($this->ok || $generateWarnings) && !empty($this->messageParameters['lti_message_type'])) {
            if ($this->messageParameters['lti_message_type'] === 'basic-lti-launch-request') {
                if ($this->ok && (!isset($this->messageParameters['resource_link_id']) || (strlen(trim($this->messageParameters['resource_link_id'])) <= 0))) {
                    $this->ok = false;
                    $this->reason = 'Missing resource link ID.';
                }
                if ($this->ltiVersion === LtiVersion::V1P3) {
                    if (!isset($this->messageParameters['roles'])) {
                        $this->setError('Missing roles parameter.', $strictMode, $generateWarnings);
                    } elseif (!empty($this->messageParameters['roles']) && empty(array_intersect(self::parseRoles($this->messageParameters['roles'],
                                    LtiVersion::V1P3), User::PRINCIPAL_ROLES))) {
                        $this->setError('No principal role found in roles parameter.', $strictMode, $generateWarnings);
                    }
                }
            } elseif (($this->messageParameters['lti_message_type'] === 'ContentItemSelectionRequest') ||
                ($this->messageParameters['lti_message_type'] === 'ContentItemUpdateRequest')) {
                $isUpdate = ($this->messageParameters['lti_message_type'] === 'ContentItemUpdateRequest');
                $mediaTypes = [];
                $contentTypes = [];
                $fileTypes = [];
                $documentTargets = [];
                if (isset($this->messageParameters['accept_media_types']) && (strlen(trim($this->messageParameters['accept_media_types'])) > 0)) {
                    $mediaTypes = array_map('trim', explode(',', $this->messageParameters['accept_media_types']));
                    $mediaTypes = array_filter($mediaTypes);
                    $mediaTypes = array_unique($mediaTypes);
                }
                if ((count($mediaTypes) <= 0) && ($this->ltiVersion === LtiVersion::V1P3)) {
                    $this->setError('Missing or empty accept_media_types parameter.', $strictMode, $generateWarnings);
                }
                if ($isUpdate) {
                    if ($this->ltiVersion === LtiVersion::V1P3) {
                        if (!$this->checkValue($this->messageParameters['accept_media_types'],
                                [Item::LTI_LINK_MEDIA_TYPE, Item::LTI_ASSIGNMENT_MEDIA_TYPE],
                                'Invalid value in accept_media_types parameter: \'%s\'.', $strictMode, $generateWarnings, true)) {
                            $this->ok = false;
                        }
                    } elseif (!$this->checkValue($this->messageParameters['accept_types'],
                            [Item::TYPE_LTI_LINK, Item::TYPE_LTI_ASSIGNMENT], 'Invalid value in accept_types parameter: \'%s\'.',
                            $strictMode, $generateWarnings, true)) {
                        $this->ok = false;
                    }
                }
                if ($this->ok) {
                    foreach ($mediaTypes as $mediaType) {
                        if (!str_starts_with($mediaType, 'application/vnd.ims.lti.')) {
                            $fileTypes[] = $mediaType;
                        }
                        if (($mediaType === 'text/html') || ($mediaType === '*/*')) {
                            $contentTypes[] = Item::TYPE_LINK;
                            $contentTypes[] = Item::TYPE_HTML;
                        } elseif (str_starts_with($mediaType, 'image/') || ($mediaType === '*/*')) {
                            $contentTypes[] = Item::TYPE_IMAGE;
                        } elseif ($mediaType === Item::LTI_LINK_MEDIA_TYPE) {
                            $contentTypes[] = Item::TYPE_LTI_LINK;
                        } elseif ($mediaType === Item::LTI_ASSIGNMENT_MEDIA_TYPE) {
                            $contentTypes[] = Item::TYPE_LTI_ASSIGNMENT;
                        }
                    }
                    if (!empty($fileTypes)) {
                        $contentTypes[] = Item::TYPE_FILE;
                    }
                    $contentTypes = array_unique($contentTypes);
                }
                if (isset($this->messageParameters['accept_presentation_document_targets']) &&
                    (strlen(trim($this->messageParameters['accept_presentation_document_targets'])) > 0)) {
                    $documentTargets = array_map('trim',
                        explode(',', $this->messageParameters['accept_presentation_document_targets']));
                    $documentTargets = array_filter($documentTargets);
                    $documentTargets = array_unique($documentTargets);
                    if (count($documentTargets) <= 0) {
                        $this->setError('Missing or empty accept_presentation_document_targets parameter.', $strictMode,
                            $generateWarnings);
                    } elseif (!empty($documentTargets)) {
                        if (empty($this->jwt) || !$this->jwt->hasJwt()) {
                            $permittedTargets = ['embed', 'frame', 'iframe', 'window', 'popup', 'overlay', 'none'];
                        } else {  // JWT
                            $permittedTargets = ['embed', 'iframe', 'window'];
                        }
                        foreach ($documentTargets as $documentTarget) {
                            if (!$this->checkValue($documentTarget, $permittedTargets,
                                    'Invalid value in accept_presentation_document_targets parameter: \'%s\'.', $strictMode,
                                    $generateWarnings, true)) {
                                $this->ok = false;
                            }
                        }
                    }
                } else {
                    $this->setError('No accept_presentation_document_targets parameter found.', $strictMode, $generateWarnings);
                }
                if ($this->ok || $generateWarnings) {
                    if (empty($this->messageParameters['content_item_return_url'])) {
                        $this->setError('Missing content_item_return_url parameter.', true, $generateWarnings);
                    }
                }
                if ($this->ok) {
                    $this->mediaTypes = $mediaTypes;
                    $this->contentTypes = $contentTypes;
                    $this->fileTypes = $fileTypes;
                    $this->documentTargets = $documentTargets;
                }
            } elseif ($this->messageParameters['lti_message_type'] === 'LtiSubmissionReviewRequest') {
                if (!isset($this->messageParameters['custom_lineitem_url']) || (strlen(trim($this->messageParameters['custom_lineitem_url'])) <= 0)) {
                    $this->setError('Missing LineItem service URL.', true, $generateWarnings);
                }
                if (!isset($this->messageParameters['for_user_id']) || (strlen(trim($this->messageParameters['for_user_id'])) <= 0)) {
                    $this->setError('Missing ID of \'for user\'', true, $generateWarnings);
                }
                if (($this->ok || $generateWarnings) && ($this->ltiVersion === LtiVersion::V1P3)) {
                    if (!isset($this->messageParameters['roles'])) {
                        $this->setError('Missing roles parameter.', $strictMode, $generateWarnings);
                    }
                }
            } elseif ($this->messageParameters['lti_message_type'] === 'ToolProxyRegistrationRequest') {
                if (!method_exists($this, 'onRegister')) {
                    $this->setError('No onRegister method found for tool.', true, $generateWarnings);
                } elseif ((!isset($this->messageParameters['reg_key']) ||
                    (strlen(trim($this->messageParameters['reg_key'])) <= 0)) ||
                    (!isset($this->messageParameters['reg_password']) ||
                    (strlen(trim($this->messageParameters['reg_password'])) <= 0)) ||
                    (!isset($this->messageParameters['tc_profile_url']) ||
                    (strlen(trim($this->messageParameters['tc_profile_url'])) <= 0)) ||
                    (!isset($this->messageParameters['launch_presentation_return_url']) ||
                    (strlen(trim($this->messageParameters['launch_presentation_return_url'])) <= 0))) {
                    $this->setError('Missing message parameters.', true, $generateWarnings);
                }
            } elseif ($this->messageParameters['lti_message_type'] === 'LtiStartProctoring') {
                if (!isset($this->messageParameters['resource_link_id']) || (strlen(trim($this->messageParameters['resource_link_id'])) <= 0)) {
                    $this->setError('Missing resource link ID.', true, $generateWarnings);
                }
                if (!isset($this->messageParameters['custom_ap_attempt_number']) || (strlen(trim($this->messageParameters['custom_ap_attempt_number'])) <= 0) ||
                    !is_numeric($this->messageParameters['custom_ap_attempt_number'])) {
                    $this->setError('Missing or invalid value for attempt number.', true, $generateWarnings);
                }
                if (!isset($this->messageParameters['user_id']) || (strlen(trim($this->messageParameters['user_id'])) <= 0)) {
                    $this->setError('Empty user ID.', true, $generateWarnings);
                }
            }
        }
        if ($this->ok || $generateWarnings) {
            if (isset($this->messageParameters['role_scope_mentor'])) {
                if (!isset($this->messageParameters['roles']) ||
                    !in_array('urn:lti:role:ims/lis/Mentor', self::parseRoles($this->messageParameters['roles']))) {
                    $this->setError('Found role_scope_mentor parameter without a Mentor role.', $strictMode, $generateWarnings);
                }
            }
        }
// Check consumer key
        if (($this->ok || $generateWarnings) && !empty($this->messageParameters['lti_message_type']) &&
            ($this->messageParameters['lti_message_type'] !== 'ToolProxyRegistrationRequest')) {
            $now = time();
            if (!isset($this->messageParameters['oauth_consumer_key'])) {
                $this->setError('Missing consumer key.', true, $generateWarnings);
            }
            if (is_null($this->platform->created)) {
                if (empty($this->jwt) || !$this->jwt->hasJwt()) {
                    $reason = "Consumer key not recognised: '{$this->messageParameters['oauth_consumer_key']}'.";
                } else {
                    $reason = "Platform not recognised (Platform ID | Client ID | Deployment ID): '{$this->messageParameters['platform_id']}' | '{$this->messageParameters['oauth_consumer_key']}' | '{$this->messageParameters['deployment_id']}'.";
                }
                $this->setError($reason, true, $generateWarnings);
            }
            if ($this->ok) {
                if ($this->messageParameters['oauth_signature_method'] !== $this->platform->signatureMethod) {
                    $this->platform->signatureMethod = $this->messageParameters['oauth_signature_method'];
                    $doSavePlatform = true;
                }
                $today = date('Y-m-d', $now);
                if (is_null($this->platform->lastAccess)) {
                    $doSavePlatform = true;
                } else {
                    $last = date('Y-m-d', $this->platform->lastAccess);
                    $doSavePlatform = $doSavePlatform || ($last !== $today);
                }
                $this->platform->lastAccess = $now;
                $this->ok = $this->verifySignature();
            }
            if ($this->ok) {
                if ($this->platform->protected) {
                    if (!is_null($this->platform->consumerGuid)) {
                        $this->ok = empty($this->messageParameters['tool_consumer_instance_guid']) || ($this->platform->consumerGuid === $this->messageParameters['tool_consumer_instance_guid']);
                        if (!$this->ok) {
                            $this->reason = 'Request is from an invalid platform.';
                        }
                    } else {
                        $this->ok = isset($this->messageParameters['tool_consumer_instance_guid']);
                        if (!$this->ok) {
                            $this->reason = 'A platform GUID must be included in the launch request as this configuration is protected.';
                        }
                    }
                }
                if ($this->ok) {
                    $this->ok = $this->platform->enabled;
                    if (!$this->ok) {
                        $this->reason = 'Platform has not been enabled by the tool.';
                    }
                }
                if ($this->ok) {
                    $this->ok = is_null($this->platform->enableFrom) || ($this->platform->enableFrom <= $now);
                    if ($this->ok) {
                        $this->ok = is_null($this->platform->enableUntil) || ($this->platform->enableUntil > $now);
                        if (!$this->ok) {
                            $this->reason = 'Platform access has expired.';
                        }
                    } else {
                        $this->reason = 'Platform access is not yet available.';
                    }
                }
            }
// Validate other message parameter values
            if ($this->ok || $generateWarnings) {
                if (isset($this->messageParameters['context_type'])) {
                    $context_types = explode(',', $this->messageParameters['context_type']);
                    $permitted_types = ['CourseTemplate', 'CourseOffering', 'CourseSection', 'Group',
                        'urn:lti:context-type:ims/lis/CourseTemplate', 'urn:lti:context-type:ims/lis/CourseOffering', 'urn:lti:context-type:ims/lis/CourseSection', 'urn:lti:context-type:ims/lis/Group'];
                    if ($this->ltiVersion !== LtiVersion::V1) {
                        $permitted_types = array_merge($permitted_types,
                            ['http://purl.imsglobal.org/vocab/lis/v2/course#CourseTemplate', 'http://purl.imsglobal.org/vocab/lis/v2/course#CourseOffering', 'http://purl.imsglobal.org/vocab/lis/v2/course#CourseSection', 'http://purl.imsglobal.org/vocab/lis/v2/course#Group']);
                    }
                    $found = false;
                    foreach ($context_types as $context_type) {
                        $found = in_array(trim($context_type), $permitted_types);
                        if ($found) {
                            break;
                        }
                    }
                    if (!$found) {
                        $this->setError(sprintf('No valid value found in context_type parameter: \'%s\'.',
                                $this->messageParameters['context_type']), $strictMode, $generateWarnings);
                    }
                }
                if (($this->ok || $generateWarnings) && !empty($this->messageParameters['lti_message_type']) &&
                    (($this->messageParameters['lti_message_type'] === 'ContentItemSelectionRequest') || ($this->messageParameters['lti_message_type'] === 'ContentItemUpdateRequest'))) {
                    $isUpdate = ($this->messageParameters['lti_message_type'] === 'ContentItemUpdateRequest');
                    if (isset($this->messageParameters['accept_unsigned']) &&
                        !$this->checkValue($this->messageParameters['accept_unsigned'], ['true', 'false'],
                            'Invalid value for accept_unsigned parameter: \'%s\'.', $strictMode, $generateWarnings)) {
                        $this->ok = false;
                    }
                    if (isset($this->messageParameters['accept_multiple'])) {
                        if (!$isUpdate) {
                            if (!$this->checkValue($this->messageParameters['accept_multiple'], ['true', 'false'],
                                    'Invalid value for accept_multiple parameter: \'%s\'.', $strictMode, $generateWarnings)) {
                                $this->ok = false;
                            }
                        } elseif (!$this->checkValue($this->messageParameters['accept_multiple'], ['false'],
                                'Invalid value for accept_multiple parameter: \'%s\'.', $strictMode, $generateWarnings)) {
                            $this->ok = false;
                        }
                    }
                    if (isset($this->messageParameters['accept_copy_advice'])) {
                        if (!$isUpdate) {
                            if (!$this->checkValue($this->messageParameters['accept_copy_advice'], ['true', 'false'],
                                    'Invalid value for accept_copy_advice parameter: \'%s\'.', $strictMode, $generateWarnings)) {
                                $this->ok = false;
                            }
                        } elseif (!$this->checkValue($this->messageParameters['accept_copy_advice'], ['false'],
                                'Invalid value for accept_copy_advice parameter: \'%s\'.', $strictMode, $generateWarnings)) {
                            $this->ok = false;
                        }
                    }
                    if (isset($this->messageParameters['auto_create']) &&
                        !$this->checkValue($this->messageParameters['auto_create'], ['true', 'false'],
                            'Invalid value for auto_create parameter: \'%s\'.', $strictMode, $generateWarnings)) {
                        $this->ok = false;
                    }
                    if (isset($this->messageParameters['can_confirm']) &&
                        !$this->checkValue($this->messageParameters['can_confirm'], ['true', 'false'],
                            'Invalid value for can_confirm parameter: \'%s\'.', $strictMode, $generateWarnings)) {
                        $this->ok = false;
                    }
                }
                if (isset($this->messageParameters['launch_presentation_document_target'])) {
                    if (!$this->checkValue($this->messageParameters['launch_presentation_document_target'],
                            ['embed', 'frame', 'iframe', 'window', 'popup', 'overlay'],
                            'Invalid value for launch_presentation_document_target parameter: \'%s\'.', $strictMode,
                            $generateWarnings, true)) {
                        $this->ok = false;
                    }
                    if (($this->messageParameters['lti_message_type'] === 'LtiStartProctoring') &&
                        ($this->messageParameters['launch_presentation_document_target'] !== 'window')) {
                        if (isset($this->messageParameters['launch_presentation_height']) ||
                            isset($this->messageParameters['launch_presentation_width'])) {
                            $this->setError('Height and width parameters must only be included for the window document target.',
                                $strictMode, $generateWarnings);
                        }
                    }
                }
                $errors = [];
                foreach ($this->messageParameters as $name => $value) {
                    if (str_starts_with(strval($name), 'custom_') && !is_string($value)) {
                        $errors[] = substr($name, 7);
                    }
                }
                if (!empty($errors)) {
                    $this->setError(sprintf('Custom parameters must have string values: %s', implode(', ', $errors)), $strictMode,
                        $generateWarnings);
                }
            }
        }

        if ($this->ok && ($this->messageParameters['lti_message_type'] === 'ToolProxyRegistrationRequest')) {
            $this->ok = $this->ltiVersion === LtiVersion::V2;
            if (!$this->ok) {
                $this->reason = 'Invalid lti_version parameter.';
            }
            if ($this->ok) {
                $url = $this->messageParameters['tc_profile_url'];
                if (strpos($url, '?') === false) {
                    $url .= '?';
                } else {
                    $url .= '&';
                }
                $url .= 'lti_version=' . LtiVersion::V2->value;
                $http = new HttpMessage($url, 'GET', null, 'Accept: application/vnd.ims.lti.v2.toolconsumerprofile+json');
                $this->ok = $http->send();
                if (!$this->ok) {
                    $this->reason = 'Platform profile not accessible.';
                } else {
                    $tcProfile = Util::jsonDecode($http->response);
                    $this->ok = !is_null($tcProfile);
                    if (!$this->ok) {
                        $this->reason = 'Invalid JSON in platform profile.';
                    }
                }
            }
// Check for required capabilities
            if ($this->ok) {
                $this->platform = Platform::fromConsumerKey($this->messageParameters['reg_key'], $this->dataConnector);
                $this->platform->profile = $tcProfile;
                $capabilities = $this->platform->profile->capability_offered;
                $missing = [];
                foreach ($this->resourceHandlers as $resourceHandler) {
                    foreach ($resourceHandler->requiredMessages as $message) {
                        if (!in_array($message->type, $capabilities)) {
                            $missing[$message->type] = true;
                        }
                    }
                }
                foreach ($this->constraints as $name => $constraint) {
                    if ($constraint['required']) {
                        if (empty(array_intersect($capabilities,
                                    array_keys(array_intersect(self::$CUSTOM_SUBSTITUTION_VARIABLES, [$name]))))) {
                            $missing[$name] = true;
                        }
                    }
                }
                if (!empty($missing)) {
                    ksort($missing);
                    $this->reason = 'Required capability not offered - \'' . implode('\', \'', array_keys($missing)) . '\'.';
                    $this->ok = false;
                }
            }
// Check for required services
            if ($this->ok) {
                foreach ($this->requiredServices as $service) {
                    foreach ($service->formats as $format) {
                        if (!$this->findService($format, $service->actions)) {
                            if ($this->ok) {
                                $this->reason = 'Required service(s) not offered - ';
                                $this->ok = false;
                            } else {
                                $this->reason .= ', ';
                            }
                            $this->reason .= "'{$format}' [" . implode(', ', $service->actions) . '].';
                        }
                    }
                }
            }
            if ($this->ok) {
                if ($this->messageParameters['lti_message_type'] === 'ToolProxyRegistrationRequest') {
                    $this->platform->profile = $tcProfile;
                    $this->platform->secret = $this->messageParameters['reg_password'];
                    $this->platform->ltiVersion = $this->ltiVersion;
                    $this->platform->name = $tcProfile->product_instance->service_owner->service_owner_name->default_value;
                    $this->platform->consumerName = $this->platform->name;
                    $this->platform->consumerVersion = "{$tcProfile->product_instance->product_info->product_family->code}-{$tcProfile->product_instance->product_info->product_version}";
                    $this->platform->consumerGuid = $tcProfile->product_instance->guid;
                    $this->platform->protected = true;
                    $doSavePlatform = true;
                }
            }
        } elseif ($this->ok && !empty($this->messageParameters['custom_tc_profile_url']) && empty($this->platform->profile)) {
            $url = $this->messageParameters['custom_tc_profile_url'];
            if (strpos($url, '?') === false) {
                $url .= '?';
            } else {
                $url .= '&';
            }
            $url .= 'lti_version=' . $this->ltiVersion->value;
            $http = new HttpMessage($url, 'GET', null, 'Accept: application/vnd.ims.lti.v2.toolconsumerprofile+json');
            if ($http->send()) {
                $tcProfile = Util::jsonDecode($http->response);
                if (!is_null($tcProfile)) {
                    $this->platform->profile = $tcProfile;
                    $doSavePlatform = true;
                }
            }
        }

        if ($this->ok) {

// Check if a relaunch is being requested
            if (isset($this->messageParameters['relaunch_url'])) {
                Util::logRequest();
                if (empty($this->messageParameters['platform_state'])) {
                    $this->ok = false;
                    $this->reason = 'Missing or empty platform_state parameter.';
                } else {
                    $this->ok = $this->sendRelaunchRequest($disableCookieCheck);
                }
            } else {
// Validate message parameter constraints
                $invalidParameters = [];
                foreach ($this->constraints as $name => $constraint) {
                    if (empty($constraint['messages']) || in_array($this->messageParameters['lti_message_type'],
                            $constraint['messages'])) {
                        $ok = true;
                        if ($constraint['required']) {
                            if (!isset($this->messageParameters[$name]) || (strlen(trim($this->messageParameters[$name])) <= 0)) {
                                $invalidParameters[] = "{$name} (missing)";
                                $ok = false;
                            }
                        }
                        if ($ok && !is_null($constraint['max_length']) && isset($this->messageParameters[$name])) {
                            if (strlen(trim($this->messageParameters[$name])) > $constraint['max_length']) {
                                $invalidParameters[] = "{$name} (too long)";
                            }
                        }
                    }
                }
                if (count($invalidParameters) > 0) {
                    $this->ok = false;
                    if (empty($this->reason)) {
                        $this->reason = 'Invalid parameter(s): ' . implode(', ', $invalidParameters) . '.';
                    }
                }

                if ($this->ok) {

// Set the request context
                    $contextId = '';
                    if ($this->hasConfiguredApiHook(self::$CONTEXT_ID_HOOK, $this->platform->getFamilyCode(), $this)) {
                        $className = $this->getApiHook(self::$CONTEXT_ID_HOOK, $this->platform->getFamilyCode());
                        $tpHook = new $className($this);
                        $contextId = $tpHook->getContextId();
                    }
                    if (empty($contextId) && isset($this->messageParameters['context_id'])) {
                        $contextId = trim($this->messageParameters['context_id']);
                    }
                    if (!empty($contextId)) {
                        $this->context = Context::fromPlatform($this->platform, $contextId);
                        $title = '';
                        if (isset($this->messageParameters['context_title'])) {
                            $title = trim($this->messageParameters['context_title']);
                        }
                        if (empty($title)) {
                            $title = "Course {$this->context->getId()}";
                        }
                        $this->context->title = $title;
                        if (isset($this->messageParameters['context_type'])) {
                            $this->context->type = trim($this->messageParameters['context_type']);
                            if (str_starts_with($this->context->type, 'http://purl.imsglobal.org/vocab/lis/v2/course#')) {
                                $this->context->type = substr($this->context->type, 46);
                            }
                        }
                    }

// Set the request resource link
                    if (isset($this->messageParameters['resource_link_id'])) {
                        $contentItemId = '';
                        if (isset($this->messageParameters['custom_content_item_id'])) {
                            $contentItemId = $this->messageParameters['custom_content_item_id'];
                        }
                        if (empty($this->context)) {
                            $this->resourceLink = ResourceLink::fromPlatform($this->platform,
                                    trim($this->messageParameters['resource_link_id']), $contentItemId);
                        } else {
                            $this->resourceLink = ResourceLink::fromContext($this->context,
                                    trim($this->messageParameters['resource_link_id']), $contentItemId);
                        }
                        $title = '';
                        if (isset($this->messageParameters['resource_link_title'])) {
                            $title = trim($this->messageParameters['resource_link_title']);
                        }
                        if (empty($title)) {
                            $title = "Resource {$this->resourceLink->getId()}";
                        }
                        $this->resourceLink->title = $title;
                    }
// Delete any existing custom parameters
                    foreach ($this->platform->getSettings() as $name => $value) {
                        if (str_starts_with($name, 'custom_') && (!in_array($name, self::$LTI_RETAIN_SETTING_NAMES))) {
                            $this->platform->setSetting($name);
                            $doSavePlatform = true;
                        }
                    }
                    if (!empty($this->context)) {
                        foreach ($this->context->getSettings() as $name => $value) {
                            if (str_starts_with($name, 'custom_') && (!in_array($name, self::$LTI_RETAIN_SETTING_NAMES))) {
                                $this->context->setSetting($name);
                            }
                        }
                    }
                    if (!empty($this->resourceLink)) {
                        foreach ($this->resourceLink->getSettings() as $name => $value) {
                            if (str_starts_with($name, 'custom_') && (!in_array($name, self::$LTI_RETAIN_SETTING_NAMES))) {
                                $this->resourceLink->setSetting($name);
                            }
                        }
                    }
// Save LTI parameters
                    foreach (self::$LTI_CONSUMER_SETTING_NAMES as $name) {
                        if (isset($this->messageParameters[$name])) {
                            $this->platform->setSetting($name, $this->messageParameters[$name]);
                        } elseif (!in_array($name, self::$LTI_RETAIN_SETTING_NAMES)) {
                            $this->platform->setSetting($name);
                        }
                    }
                    if (!empty($this->context)) {
                        foreach (self::$LTI_CONTEXT_SETTING_NAMES as $name) {
                            if (isset($this->messageParameters[$name])) {
                                $this->context->setSetting($name, $this->messageParameters[$name]);
                            } elseif (!in_array($name, self::$LTI_RETAIN_SETTING_NAMES)) {
                                $this->context->setSetting($name);
                            }
                        }
                    }
                    if (!empty($this->resourceLink)) {
                        foreach (self::$LTI_RESOURCE_LINK_SETTING_NAMES as $name) {
                            if (isset($this->messageParameters[$name])) {
                                $this->resourceLink->setSetting($name, $this->messageParameters[$name]);
                            } elseif (!in_array($name, self::$LTI_RETAIN_SETTING_NAMES)) {
                                $this->resourceLink->setSetting($name);
                            }
                        }
                    }
// Save other custom parameters at all levels
                    foreach ($this->messageParameters as $name => $value) {
                        if (str_starts_with(strval($name), 'custom_') && is_string($value) && !in_array($name,
                                array_merge(self::$LTI_CONSUMER_SETTING_NAMES, self::$LTI_CONTEXT_SETTING_NAMES,
                                    self::$LTI_RESOURCE_LINK_SETTING_NAMES))) {
                            $this->platform->setSetting($name, $value);
                            if (!empty($this->context)) {
                                $this->context->setSetting($name, $value);
                            }
                            if (!empty($this->resourceLink)) {
                                $this->resourceLink->setSetting($name, $value);
                            }
                        }
                    }

// Set the user instance
                    $userId = '';
                    if ($this->hasConfiguredApiHook(self::$USER_ID_HOOK, $this->platform->getFamilyCode(), $this)) {
                        $className = $this->getApiHook(self::$USER_ID_HOOK, $this->platform->getFamilyCode());
                        $tpHook = new $className($this);
                        $userId = $tpHook->getUserId();
                    }
                    if (empty($userId) && isset($this->messageParameters['user_id'])) {
                        $userId = trim($this->messageParameters['user_id']);
                    }

                    $this->userResult = UserResult::fromResourceLink($this->resourceLink, $userId);

// Set the user name
                    $firstname = $this->messageParameters['lis_person_name_given'] ?? '';
                    $middlename = $this->messageParameters['lis_person_name_middle'] ?? '';
                    $lastname = $this->messageParameters['lis_person_name_family'] ?? '';
                    $fullname = $this->messageParameters['lis_person_name_full'] ?? '';
                    $this->userResult->setNames($firstname, $lastname, $fullname, $middlename);

// Set the sourcedId
                    if (isset($this->messageParameters['lis_person_sourcedid'])) {
                        $this->userResult->sourcedId = $this->messageParameters['lis_person_sourcedid'];
                    }

// Set the username
                    if (isset($this->messageParameters['ext_username'])) {
                        $this->userResult->username = $this->messageParameters['ext_username'];
                    } elseif (isset($this->messageParameters['ext_user_username'])) {
                        $this->userResult->username = $this->messageParameters['ext_user_username'];
                    } elseif (isset($this->messageParameters['ext_d2l_username'])) {
                        $this->userResult->username = $this->messageParameters['ext_d2l_username'];
                    } elseif (isset($this->messageParameters['custom_username'])) {
                        $this->userResult->username = $this->messageParameters['custom_username'];
                    } elseif (isset($this->messageParameters['custom_user_username'])) {
                        $this->userResult->username = $this->messageParameters['custom_user_username'];
                    }

// Set the user email
                    $email = $this->messageParameters['lis_person_contact_email_primary'] ?? '';
                    $this->userResult->setEmail($email, $this->defaultEmail);

// Set the user image URI
                    if (isset($this->messageParameters['user_image'])) {
                        $this->userResult->image = $this->messageParameters['user_image'];
                    }

// Set the user roles
                    if (isset($this->messageParameters['roles'])) {
                        $this->userResult->roles = self::parseRoles($this->messageParameters['roles'], $this->ltiVersion);
                    }

// Initialise the platform and check for changes
                    $this->platform->defaultEmail = $this->defaultEmail;
                    if ($this->platform->ltiVersion !== $this->ltiVersion) {
                        $this->platform->ltiVersion = $this->ltiVersion;
                        $doSavePlatform = true;
                    }
                    if (isset($this->messageParameters['deployment_id'])) {
                        $this->platform->deploymentId = $this->messageParameters['deployment_id'];
                    }
                    if (isset($this->messageParameters['tool_consumer_instance_name'])) {
                        if ($this->platform->consumerName !== $this->messageParameters['tool_consumer_instance_name']) {
                            $this->platform->consumerName = $this->messageParameters['tool_consumer_instance_name'];
                            $doSavePlatform = true;
                        }
                    }
                    if (isset($this->messageParameters['tool_consumer_info_product_family_code'])) {
                        $version = $this->messageParameters['tool_consumer_info_product_family_code'];
                        if (isset($this->messageParameters['tool_consumer_info_version'])) {
                            $version .= "-{$this->messageParameters['tool_consumer_info_version']}";
                        }
// do not delete any existing consumer version if none is passed
                        if ($this->platform->consumerVersion !== $version) {
                            $this->platform->consumerVersion = $version;
                            $doSavePlatform = true;
                        }
                    } elseif (isset($this->messageParameters['ext_lms']) && ($this->platform->consumerName !== $this->messageParameters['ext_lms'])) {
                        $this->platform->consumerVersion = $this->messageParameters['ext_lms'];
                        $doSavePlatform = true;
                    }
                    if (isset($this->messageParameters['tool_consumer_instance_guid'])) {
                        if (is_null($this->platform->consumerGuid)) {
                            $this->platform->consumerGuid = $this->messageParameters['tool_consumer_instance_guid'];
                            $doSavePlatform = true;
                        } elseif (!$this->platform->protected && ($this->platform->consumerGuid !== $this->messageParameters['tool_consumer_instance_guid'])) {
                            $this->platform->consumerGuid = $this->messageParameters['tool_consumer_instance_guid'];
                            $doSavePlatform = true;
                        }
                    }
                    if (isset($this->messageParameters['launch_presentation_css_url'])) {
                        if ($this->platform->cssPath !== $this->messageParameters['launch_presentation_css_url']) {
                            $this->platform->cssPath = $this->messageParameters['launch_presentation_css_url'];
                            $doSavePlatform = true;
                        }
                    } elseif (isset($this->messageParameters['ext_launch_presentation_css_url']) && ($this->platform->cssPath !== $this->messageParameters['ext_launch_presentation_css_url'])) {
                        $this->platform->cssPath = $this->messageParameters['ext_launch_presentation_css_url'];
                        $doSavePlatform = true;
                    } elseif (!empty($this->platform->cssPath)) {
                        $this->platform->cssPath = null;
                        $doSavePlatform = true;
                    }
                }

// Persist changes to platform
                if ($doSavePlatform) {
                    $this->platform->save();
                }

                if ($this->ok) {

// Persist changes to context
                    if (isset($this->context)) {
                        $this->context->save();
                    }

                    if (isset($this->resourceLink)) {
// Persist changes to resource link
                        $this->resourceLink->save();

// Persist changes to user instnce
                        $this->userResult->setResourceLinkId($this->resourceLink->getRecordId());
                        if (isset($this->messageParameters['lis_result_sourcedid'])) {
                            if ($this->userResult->ltiResultSourcedId !== $this->messageParameters['lis_result_sourcedid']) {
                                $this->userResult->ltiResultSourcedId = $this->messageParameters['lis_result_sourcedid'];
                                $this->userResult->save();
                            }
                        } elseif ($this->userResult->isLearner()) {  // Ensure all learners are recorded in case Assignment and Grade services are used
                            $this->userResult->ltiResultSourcedId = '';
                            $this->userResult->save();
                        }

// Check if a share arrangement is in place for this resource link
                        $this->ok = $this->checkForShare();
                    }
                }
            }
        }

        return $this->ok;
    }

    /**
     * Check if a share arrangement is in place.
     *
     * @return bool  True if no error is reported
     */
    private function checkForShare(): bool
    {
        $ok = true;
        $doSaveResourceLink = true;

        $id = $this->resourceLink->primaryResourceLinkId;

        $shareRequest = isset($this->messageParameters['custom_share_key']) && !empty($this->messageParameters['custom_share_key']);
        if ($shareRequest) {
            if (!$this->allowSharing) {
                $ok = false;
                $this->reason = 'Your sharing request has been refused because sharing is not being permitted.';
            } else {
// Check if this is a new share key
                $shareKey = new ResourceLinkShareKey($this->resourceLink, $this->messageParameters['custom_share_key']);
                if (!is_null($shareKey->resourceLinkId)) {
// Update resource link with sharing primary resource link details
                    $id = $shareKey->resourceLinkId;
                    $ok = ($id !== $this->resourceLink->getRecordId());
                    if ($ok) {
                        $this->resourceLink->primaryResourceLinkId = $id;
                        $this->resourceLink->shareApproved = $shareKey->autoApprove;
                        $ok = $this->resourceLink->save();
                        if ($ok) {
                            $doSaveResourceLink = false;
                            $this->userResult->getResourceLink()->primaryResourceLinkId = $id;
                            $this->userResult->getResourceLink()->shareApproved = $shareKey->autoApprove;
                            $this->userResult->getResourceLink()->updated = time();
// Remove share key
                            $shareKey->delete();
                        } else {
                            $this->reason = 'An error occurred initialising your share arrangement.';
                        }
                    } else {
                        $this->reason = 'It is not possible to share your resource link with yourself.';
                    }
                }
                if ($ok) {
                    $ok = !is_null($id);
                    if (!$ok) {
                        $this->reason = 'You have requested to share a resource link but none is available.';
                    } else {
                        $ok = (!is_null($this->userResult->getResourceLink()->shareApproved) && $this->userResult->getResourceLink()->shareApproved);
                        if (!$ok) {
                            $this->reason = 'Your share request is waiting to be approved.';
                        }
                    }
                }
            }
        } else {
// Check no share is in place
            $ok = is_null($id);
            if (!$ok) {
                $this->reason = 'You have not requested to share a resource link but an arrangement is currently in place.';
            }
        }

// Look up primary resource link
        if ($ok && !is_null($id)) {
            $resourceLink = ResourceLink::fromRecordId($id, $this->dataConnector);
            $ok = !is_null($resourceLink->created);
            if ($ok) {
                if ($doSaveResourceLink) {
                    $this->resourceLink->save();
                }
                $this->resourceLink = $resourceLink;
            } else {
                $this->reason = 'Unable to load resource link being shared.';
            }
        }

        return $ok;
    }

    /**
     * Generate a form to perform an authentication request.
     *
     * @param array $parameters         Request parameters
     * @param bool $disableCookieCheck  True if no cookie check should be made
     *
     * @return bool  True if form was generated
     */
    private function sendAuthenticationRequest(array $parameters, bool $disableCookieCheck): bool
    {
        $clientId = null;
        if (isset($parameters['client_id'])) {
            $clientId = $parameters['client_id'];
        }
        $deploymentId = null;
        if (isset($parameters['lti_deployment_id'])) {
            $deploymentId = $parameters['lti_deployment_id'];
        }
        $currentLogLevel = Util::$logLevel;
        $this->platform = Platform::fromPlatformId($parameters['iss'], $clientId, $deploymentId, $this->dataConnector);
        if ($this->platform->debugMode && (!$currentLogLevel->logDebug())) {
            $this->debugMode = true;
            Util::$logLevel = LogLevel::Debug;
            Util::logRequest();
        }
        $ok = !is_null($this->platform) && !empty($this->platform->authenticationUrl);
        if (!$ok) {
            $this->reason = 'Platform not found or no platform authentication request URL.';
        } else {
            $oauthRequest = OAuth\OAuthRequest::from_request();
            $usePlatformStorage = !empty($oauthRequest->get_parameter('lti_storage_target'));
            $session_id = '';
            if ($usePlatformStorage) {
                $usePlatformStorage = empty($_COOKIE[session_name()]) || ($_COOKIE[session_name()] !== session_id());
            }
            if (!$disableCookieCheck) {
                if (empty(session_id())) {
                    if (empty($_COOKIE)) {
                        Util::setTestCookie();
                    }
                } elseif (empty($_COOKIE[session_name()]) || ($_COOKIE[session_name()] !== session_id())) {
                    $session_id = '.' . session_id();
                    if (empty($_COOKIE[session_name()])) {
                        Util::setTestCookie();
                    }
                }
            }
            do {
                $state = Util::getRandomString();
                $nonce = new PlatformNonce($this->platform, "{$state}{$session_id}");
                $ok = !$nonce->load();
            } while (!$ok);
            $nonce->expires = time() + Tool::$stateLife;
            $ok = $nonce->save();
            if ($ok) {
                $redirectUri = $oauthRequest->get_normalized_http_url();
                if (!empty($_SERVER['QUERY_STRING'])) {
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $ignoreParams = ['lti_storage_target'];
                    } else {  // Remove all parameters added by platform from query string
                        $ignoreParams = ['iss', 'target_link_uri', 'login_hint', 'lti_message_hint', 'client_id', 'lti_deployment_id', 'lti_storage_target'];
                    }
                    $queryString = '';
                    $params = explode('&', $_SERVER['QUERY_STRING']);
                    $ignore = false;  // Only include those query parameters which come before any of the standard OpenID Connect ones
                    foreach ($params as $param) {
                        $parts = explode('=', $param, 2);
                        if (in_array($parts[0], $ignoreParams)) {
                            $ignore = true;
                        } elseif (!$ignore) {
                            if ((count($parts) <= 1) || empty($parts[1])) {  // Drop equals sign for empty parameters to workaround Canvas bug
                                $queryString .= "&{$parts[0]}";
                            } else {
                                $queryString .= "&{$parts[0]}={$parts[1]}";
                            }
                        }
                    }
                    if (!empty($queryString)) {
                        $queryString = substr($queryString, 1);
                        $redirectUri .= "?{$queryString}";
                    }
                }
                $requestNonce = Util::getRandomString(32);
                $params = [
                    'client_id' => $this->platform->clientId,
                    'login_hint' => $parameters['login_hint'],
                    'nonce' => $requestNonce,
                    'prompt' => 'none',
                    'redirect_uri' => $redirectUri,
                    'response_mode' => 'form_post',
                    'response_type' => 'id_token',
                    'scope' => 'openid',
                    'state' => $nonce->getValue()
                ];
                if (isset($parameters['lti_message_hint'])) {
                    $params['lti_message_hint'] = $parameters['lti_message_hint'];
                }
                $this->onInitiateLogin($parameters, $params);
                $javascript = '';
                if ($usePlatformStorage) {
                    $javascript = $this->getStorageJS('lti.put_data', $nonce->getValue(), $requestNonce);
                }
                if (!Tool::$authenticateUsingGet) {
                    $this->output = Util::sendForm($this->platform->authenticationUrl, $params, '', $javascript);
                } else {
                    Util::redirect($this->platform->authenticationUrl, $params, '', $javascript);
                }
            } else {
                $this->reason = 'Unable to generate a state value.';
            }
        }

        return $ok;
    }

    /**
     * Generate a form to perform a relaunch request.
     *
     * @param bool $disableCookieCheck  True if no cookie check should be made
     *
     * @return bool  True if a relaunch request was sent
     */
    private function sendRelaunchRequest(bool $disableCookieCheck): bool
    {
        $session_id = '';
        if (!$disableCookieCheck) {
            if (empty(session_id())) {
                if (empty($_COOKIE)) {
                    Util::setTestCookie();
                }
            } elseif (empty($_COOKIE[session_name()]) || ($_COOKIE[session_name()] !== session_id())) {
                $session_id = '.' . session_id();
                if (empty($_COOKIE[session_name()])) {
                    Util::setTestCookie();
                }
            }
        }
        do {
            $state = Util::getRandomString();
            $nonce = new PlatformNonce($this->platform, "{$state}{$session_id}");
            $ok = !$nonce->load();
        } while (!$ok);
        $nonce->expires = time() + Tool::$stateLife;
        $this->ok = $nonce->save();
        if ($this->ok) {
            $params = [
                'tool_state' => $nonce->getValue(),
                'platform_state' => $this->messageParameters['platform_state']
            ];
            $params = $this->platform->addSignature($this->messageParameters['relaunch_url'], $params);
            $this->output = Util::sendForm($this->messageParameters['relaunch_url'], $params);
        } else {
            $this->reason = 'Unable to generate a state value.';
        }

        return $this->ok;
    }

    /**
     * Validate a parameter value from an array of permitted values.
     *
     * @param string $value           Value to be checked
     * @param array $values           Array of permitted values
     * @param string $reason          Reason to generate when the value is not permitted
     * @param bool $strictMode        True if full compliance with the LTI specification is required
     * @param bool $generateWarnings  True if warning messages should be generated
     * @param bool $ignoreInvalid     True if invalid values are to be ignored (optional default is false)
     *
     * @return bool  True if value is valid
     */
    private function checkValue(string &$value, array $values, string $reason, bool $strictMode, bool $generateWarnings,
        bool $ignoreInvalid = false): bool
    {
        $lookupValue = $value;
        if (!$strictMode) {
            $lookupValue = strtolower($value);
        }
        $ok = in_array($lookupValue, $values);
        if (!$ok) {
            if ($this->ok && $strictMode) {
                $this->reason = sprintf($reason, $value);
            } else {
                $ok = true;
                if ($generateWarnings) {
                    $this->warnings[] = sprintf($reason, $value);
                }
            }
        } elseif ($lookupValue !== $value) {
            if ($generateWarnings) {
                $this->warnings[] = sprintf($reason, $value) . " [Changed to '{$lookupValue}']";
            }
            $value = $lookupValue;
        }

        return $ok;
    }

    /**
     * Set error reason or add warning.
     *
     * @param string $reason          Reason to generate when the value is not permitted
     * @param bool $strictMode        True if full compliance with the LTI specification is required
     * @param bool $generateWarnings  True if warning messages should be generated
     *
     * @return void
     */
    private function setError(string $reason, bool $strictMode, bool $generateWarnings): void
    {
        if ($strictMode && $this->ok) {
            $this->ok = false;
            $this->reason = $reason;
        } elseif ($generateWarnings) {
            $this->warnings[] = $reason;
        }
    }

    /**
     * Get the JavaScript for handling storage postMessages from a tool.
     *
     * @param string $message  Type of postMessage
     * @param string $state    Value of state
     * @param string $nonce    Value of nonce
     *
     * @return string  The JavaScript to handle storage postMessages
     */
    private function getStorageJS(string $message, string $state, string $nonce): string
    {
        $javascript = '';
        $timeoutDelay = static::$postMessageTimeoutDelay;
        $formSubmissionTimeout = Util::$formSubmissionTimeout;
        if ($timeoutDelay > 0) {
            $parts = explode('.', $state);
            $state = $parts[0];
            $capabilitiesId = Util::getRandomString();
            $messageId = Util::getRandomString();
            $javascript = <<< EOD
let origin = new URL('{$this->platform->authenticationUrl}').origin;
let params = new URLSearchParams(window.location.search);
let target = params.get('lti_storage_target');
let state = '{$state}';
let nonce = '{$nonce}';
let capabilitiesid = '{$capabilitiesId}';
let messageid = '{$messageId}';
let supported = new Map();
let timeout;

window.addEventListener('message', function (event) {
  let ok = true;
  if (typeof event.data !== "object") {
    ok = false;
    console.log('Error \'response is not an object\': ' + event.data);
  }
  if (ok && event.data.error) {
    ok = false;
    if (event.data.error.code && event.data.error.message) {
      console.log('Error \'' + event.data.error.code + '\': ' + event.data.error.message);
    } else {
      console.log(event.data.error);
    }
  }
  if (ok && !event.data.subject) {
    ok = false;
    console.log('Error: There is no subject specified');
  }
  if (ok) {
    switch (event.data.subject) {
      case 'lti.capabilities.response':
      case 'org.imsglobal.lti.capabilities.response':
        clearTimeout(timeout);
        if (event.data.message_id !== capabilitiesid) {
          ok = false;
          console.log('Invalid message ID');
        } else {
          event.data.supported_messages.forEach(function(capability) {
            supported.set(capability.subject, (capability.frame) ? capability.frame : target);
          });
          if (supported.has('{$message}')) {
            sendMessage('{$message}');
          } else if (supported.has('org.imsglobal.{$message}')) {
            sendMessage('org.imsglobal.{$message}');
          } else {
            submitForm();
          }
        }
        break;
      case '{$message}.response':
      case 'org.imsglobal.{$message}.response':
        clearTimeout(timeout);
        if ((event.data.message_id !== messageid) || (event.origin !== origin)) {
          ok = false;
          console.log('Invalid message ID or origin');
        } else if (event.data.key !== state) {
          ok = false;
          console.log('Key not expected: ' + event.data.key);
        } else if (('{$message}' === 'lti.put_data') && (event.data.value !== nonce)) {
          ok = false;
          console.log('Invalid value for key ' + event.data.key + ': ' + event.data.value + ' (expected ' + nonce + ')');
        } else {
          if (document.getElementById('id__storage_check')) {
            document.getElementById('id__storage_check').value = state + '.' + event.data.value;
          } else if (document.getElementById('id_state')) {
            document.getElementById('id_state').value += '.platformStorage';
          }
          submitForm();
        }
        break;
      default:
        console.log('Subject \'' + event.data.subject + '\' not recognised');
        break;
    }
  } else {
    clearTimeout(timeout);
  }
  if (!ok) {
    submitForm();
  }
});

function getTarget(frame = '') {
  let wdw = window.opener || window.parent;
  let targetframe = wdw;
  if (frame && (frame !== '_parent')) {
    try {
      targetframe = wdw.frames[frame];
    } catch(err) {
      targetframe = null;
    }
    if (!targetframe) {
      try {
        targetframe = window.top.frames[frame];
      } catch(err) {
        console.log('Cannot access storage frame (' + frame + '): ' + err.message);
        targetframe = null;
      }
    }
  }
  if (targetframe === window) {
    targetframe = null;
  }
  if (!targetframe) {
    console.log('No target frame found');
  }

  return targetframe;
}


EOD;
            switch ($message) {
                case 'lti.put_data':
                    $javascript .= <<< EOD
function sendMessage(subject) {
  let usetarget = target;
  if (supported.has(subject)) {
    usetarget = supported.get(subject);
  }
  let targetframe = getTarget(usetarget);
  if (targetframe) {
    try {
      targetframe.postMessage({
        'subject': subject,
        'message_id': messageid,
        'key': state,
        'value': nonce
      }, origin);
    } catch(err) {
      console.log(err.name + ': ' + err.message);
    }
  } else {
    saveData();
  }
}

function doOnLoad() {
  timeout = setTimeout(function() {  // Allow time to check platform capabilities
    timeout = setTimeout(function() {  // Allow time to check platform capabilities
      timeout = setTimeout(function() {  // Allow time to send postMessage
        submitForm();
      }, {$timeoutDelay});
      sendMessage('lti.put_data');
    }, {$timeoutDelay});
    checkCapabilities('org.imsglobal.lti.capabilities', true);
  }, {$timeoutDelay});
  checkCapabilities('lti.capabilities', false);
}

EOD;
                    break;
                case 'lti.get_data':
                    $javascript .= <<< EOD
function sendMessage(subject) {
  let usetarget = target;
  if (supported.has(subject)) {
    usetarget = supported.get(subject);
  }
  let targetframe = getTarget(usetarget);
  if (targetframe) {
    try {
      targetframe.postMessage({
        'subject': subject,
        'message_id': messageid,
        'key': state
      }, origin);
    } catch(err) {
      console.log(err.name + ': ' + err.message);
    }
  }
}

function doOnLoad() {
  timeout = setTimeout(function() {  // Allow time to check platform capabilities
    timeout = setTimeout(function() {  // Allow time to check platform capabilities
      timeout = setTimeout(function() {  // Allow time to send postMessage
        submitForm();
      }, {$timeoutDelay});
      sendMessage('lti.get_data');
    }, {$timeoutDelay});
    checkCapabilities('org.imsglobal.lti.capabilities', true);
  }, {$timeoutDelay});
  checkCapabilities('lti.capabilities', false);
}

EOD;
                    break;
            }

            $javascript .= <<< EOD

function checkCapabilities(subject, checkparent) {
  let wdw = getTarget(target);
  if (wdw) {
    try {
      wdw.postMessage({
        'subject': subject,
        'message_id': capabilitiesid
      }, '*');
      if (checkparent && (wdw !== window.parent)) {
        window.parent.postMessage({
          'subject': subject,
          'message_id': capabilitiesid
        }, '*');
      }
    } catch(err) {
      console.log(err.name + ': ' + err.message);
    }
  }
}

function doUnblock() {
  var el = document.getElementById('id_blocked');
  el.style.display = 'block';
}

function submitForm() {
  if ((document.forms[0].target === '_blank') && (window.top === window.self)) {
    document.forms[0].target = '';
  }
  window.setTimeout(doUnblock, {$formSubmissionTimeout}000);
  document.forms[0].submit();
}

window.onload=doOnLoad;
EOD;
        }

        return $javascript;
    }

}
