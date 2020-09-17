<?php

namespace ceLTIc\LTI;

use ceLTIc\LTI\DataConnector\DataConnector;
use ceLTIc\LTI\MediaType;
use ceLTIc\LTI\Profile;
use ceLTIc\LTI\Content\Item;
use ceLTIc\LTI\Http\HttpMessage;
use ceLTIc\LTI\OAuth;
use ceLTIc\LTI\ApiHook\ApiHook;
use ceLTIc\LTI\Util;

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
    const CONNECTION_ERROR_MESSAGE = 'Sorry, there was an error connecting you to the application.';

    /**
     * Use ID value only.
     */
    const ID_SCOPE_ID_ONLY = 0;

    /**
     * Prefix an ID with the consumer key.
     */
    const ID_SCOPE_GLOBAL = 1;

    /**
     * Prefix the ID with the consumer key and context ID.
     */
    const ID_SCOPE_CONTEXT = 2;

    /**
     * Prefix the ID with the consumer key and resource ID.
     */
    const ID_SCOPE_RESOURCE = 3;

    /**
     * Character used to separate each element of an ID.
     */
    const ID_SCOPE_SEPARATOR = ':';

    /**
     * Names of LTI parameters to be retained in the consumer settings property.
     */
    private static $LTI_CONSUMER_SETTING_NAMES = array('custom_tc_profile_url', 'custom_system_setting_url', 'custom_oauth2_access_token_url');

    /**
     * Names of LTI parameters to be retained in the context settings property.
     */
    private static $LTI_CONTEXT_SETTING_NAMES = array('custom_context_setting_url',
        'custom_context_memberships_url', 'custom_context_memberships_v2_url', 'custom_lineitems_url', 'custom_ags_scopes'
    );

    /**
     * Names of LTI parameters to be retained in the resource link settings property.
     */
    private static $LTI_RESOURCE_LINK_SETTING_NAMES = array('lis_result_sourcedid', 'lis_outcome_service_url',
        'ext_ims_lis_basic_outcome_url', 'ext_ims_lis_resultvalue_sourcedids', 'ext_outcome_data_values_accepted',
        'ext_ims_lis_memberships_id', 'ext_ims_lis_memberships_url',
        'ext_ims_lti_tool_setting', 'ext_ims_lti_tool_setting_id', 'ext_ims_lti_tool_setting_url',
        'custom_link_setting_url', 'custom_link_memberships_url',
        'custom_lineitems_url', 'custom_lineitem_url', 'custom_ags_scopes'
    );

    /**
     * Names of LTI parameters to be retained even when not passed.
     */
    private static $LTI_RETAIN_SETTING_NAMES = array('custom_lineitem_url');

    /**
     * Names of LTI custom parameter substitution variables (or capabilities) and their associated default message parameter names.
     */
    private static $CUSTOM_SUBSTITUTION_VARIABLES = array('User.id' => 'user_id',
        'User.image' => 'user_image',
        'User.username' => 'username',
        'User.scope.mentor' => 'role_scope_mentor',
        'Membership.role' => 'roles',
        'Person.sourcedId' => 'lis_person_sourcedid',
        'Person.name.full' => 'lis_person_name_full',
        'Person.name.family' => 'lis_person_name_family',
        'Person.name.given' => 'lis_person_name_given',
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
        'LtiLink.memberships.url' => 'custom_link_memberships_url'
    );

    /**
     * Tool consumer object.
     *
     * @deprecated Use Tool::$platform instead
     * @see platform
     *
     * @var ToolConsumer|null $consumer
     */
    public $consumer = null;

    /**
     * Platform object.
     *
     * @var Platform|null $platform
     */
    public $platform = null;

    /**
     * Return URL provided by platform.
     *
     * @var string|null $returnUrl
     */
    public $returnUrl = null;

    /**
     * UserResult object.
     *
     * @var UserResult|null $userResult
     */
    public $userResult = null;

    /**
     * Resource link object.
     *
     * @var ResourceLink|null $resourceLink
     */
    public $resourceLink = null;

    /**
     * Context object.
     *
     * @var Context|null $context
     */
    public $context = null;

    /**
     * Default email domain.
     *
     * @var string $defaultEmail
     */
    public $defaultEmail = '';

    /**
     * Scope to use for user IDs.
     *
     * @var int $idScope
     */
    public $idScope = self::ID_SCOPE_ID_ONLY;

    /**
     * Whether shared resource link arrangements are permitted.
     *
     * @var bool $allowSharing
     */
    public $allowSharing = false;

    /**
     * Message for last request processed
     *
     * @var string $message
     */
    public $message = self::CONNECTION_ERROR_MESSAGE;

    /**
     * Base URL for tool service
     *
     * @var string|null $baseUrl
     */
    public $baseUrl = null;

    /**
     * Vendor details
     *
     * @var Item|null $vendor
     */
    public $vendor = null;

    /**
     * Product details
     *
     * @var Item|null $product
     */
    public $product = null;

    /**
     * Services required by Tool
     *
     * @var array|null $requiredServices
     */
    public $requiredServices = null;

    /**
     * Optional services used by Tool
     *
     * @var array|null $optionalServices
     */
    public $optionalServices = null;

    /**
     * Resource handlers for Tool
     *
     * @var array|null $resourceHandlers
     */
    public $resourceHandlers = null;

    /**
     * Default tool for use with service requests
     *
     * @var Tool|null $defaultTool
     */
    public static $defaultTool = null;

    /**
     * URL to redirect user to on successful completion of the request.
     *
     * @var string|null $redirectUrl
     */
    protected $redirectUrl = null;

    /**
     * Media types accepted by the platform.
     *
     * @var array|null $mediaTypes
     */
    protected $mediaTypes = null;

    /**
     * Document targets accepted by the platform.
     *
     * @var array|null $documentTargets
     */
    protected $documentTargets = null;

    /**
     * Default HTML to be displayed on a successful completion of the request.
     *
     * @var string|null $output
     */
    protected $output = null;

    /**
     * HTML to be displayed on an unsuccessful completion of the request and no return URL is available.
     *
     * @var string|null $errorOutput
     */
    protected $errorOutput = null;

    /**
     * LTI parameter constraints for auto validation checks.
     *
     * @var array|null $constraints
     */
    private $constraints = null;

    /**
     * Class constructor
     *
     * @param DataConnector     $dataConnector    Object containing a database connection object
     */
    function __construct($dataConnector = null)
    {
        $this->consumer = &$this->platform;
        $this->constraints = array();
        if (empty($dataConnector)) {
            $dataConnector = DataConnector::getDataConnector();
        }
        $this->dataConnector = $dataConnector;
        $this->vendor = new Profile\Item();
        $this->product = new Profile\Item();
        $this->requiredServices = array();
        $this->optionalServices = array();
        $this->resourceHandlers = array();
    }

    /**
     * Get the message parameters
     *
     * @return array The message parameter array
     */
    public function getMessageParameters()
    {
        if (is_null($this->messageParameters)) {
            $this->parseMessage();
// Set debug mode
            if (!$this->debugMode) {
                $this->debugMode = (isset($this->messageParameters['custom_debug']) &&
                    (strtolower($this->messageParameters['custom_debug']) === 'true'));
                if ($this->debugMode) {
                    $currentLogLevel = Util::$logLevel;
                    Util::$logLevel = Util::LOGLEVEL_DEBUG;
                    if ($currentLogLevel < Util::LOGLEVEL_INFO) {
                        Util::logRequest();
                    }
                }
            }
// Set return URL if available
            if (!empty($this->messageParameters['launch_presentation_return_url'])) {
                $this->returnUrl = $this->messageParameters['launch_presentation_return_url'];
            } elseif (!empty($this->messageParameters['content_item_return_url'])) {
                $this->returnUrl = $this->messageParameters['content_item_return_url'];
            }
        }

        return $this->messageParameters;
    }

    /**
     * Process an incoming request
     */
    public function handleRequest()
    {
        if ($this->debugMode) {
            Util::$logLevel = Util::LOGLEVEL_DEBUG;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {  // Ignore HEAD requests
            Util::logRequest();
        } elseif (!empty($_REQUEST['iss'])) {  // Initiate login request
            Util::logRequest();
            if (empty($_REQUEST['login_hint'])) {
                $this->ok = false;
                $this->reason = 'Missing login_hint parameter';
            } elseif (empty($_REQUEST['target_link_uri'])) {
                $this->ok = false;
                $this->reason = 'Missing target_link_uri parameter';
            } else {
                $hint = isset($_REQUEST['lti_message_hint']) ? $_REQUEST['lti_message_hint'] : null;
                $this->ok = $this->sendAuthenticationRequest($hint);
            }
        } else {  // LTI message
            $this->getMessageParameters();
            if (!empty($this->platform) && $this->platform->debugMode) {
                Util::$logLevel = Util::LOGLEVEL_DEBUG;
            }
            Util::logRequest();
            if ($this->ok && $this->authenticate()) {
                if (empty($this->output)) {
                    $this->doCallback();
                }
            }
        }
        if (!$this->ok) {
            Util::logError("Request failed with reason: '{$this->reason}'");
        }
        $this->result();
    }

    /**
     * Add a parameter constraint to be checked on launch
     *
     * @param string $name           Name of parameter to be checked
     * @param bool    $required      True if parameter is required (optional, default is true)
     * @param int $maxLength         Maximum permitted length of parameter value (optional, default is null)
     * @param array $messageTypes    Array of message types to which the constraint applies (optional, default is all)
     */
    public function setParameterConstraint($name, $required = true, $maxLength = null, $messageTypes = null)
    {
        $name = trim($name);
        if (strlen($name) > 0) {
            $this->constraints[$name] = array('required' => $required, 'max_length' => $maxLength, 'messages' => $messageTypes);
        }
    }

    /**
     * Get an array of defined tool consumers
     *
     * @deprecated Use getPlatforms() instead
     * @see Tool::getPlatforms()
     *
     * @return array Array of ToolConsumer objects
     */
    public function getConsumers()
    {
        Util::logDebug('Method ceLTIc\LTI\Tool::getConsumers() has been deprecated; please use ceLTIc\LTI\Tool::getPlatforms() instead.',
            true);
        return $this->getPlatforms();
    }

    /**
     * Get an array of defined platforms
     *
     * @return array Array of Platform objects
     */
    public function getPlatforms()
    {
        return $this->dataConnector->getPlatforms();
    }

    /**
     * Find an offered service based on a media type and HTTP action(s)
     *
     * @param string $format  Media type required
     * @param array  $methods Array of HTTP actions required
     *
     * @return object The service object
     */
    public function findService($format, $methods)
    {
        $found = false;
        $services = $this->platform->profile->service_offered;
        if (is_array($services)) {
            $n = -1;
            foreach ($services as $service) {
                $n++;
                if (!is_array($service->format) || !in_array($format, $service->format)) {
                    continue;
                }
                $missing = array();
                foreach ($methods as $method) {
                    if (!is_array($service->action) || !in_array($method, $service->action)) {
                        $missing[] = $method;
                    }
                }
                $methods = $missing;
                if (count($methods) <= 0) {
                    $found = $service;
                    break;
                }
            }
        }

        return $found;
    }

    /**
     * Send the tool proxy to the platform
     *
     * @return bool    True if the tool proxy was accepted
     */
    public function doToolProxyService()
    {
// Create tool proxy
        $toolProxyService = $this->findService('application/vnd.ims.lti.v2.toolproxy+json', array('POST'));
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

    /**
     * Generate a web page containing an auto-submitted form of parameters.
     *
     * @deprecated Use Util::sendForm() instead
     * @see Util::sendForm()
     *
     * @param string $url         URL to which the form should be submitted
     * @param array    $params    Array of form parameters
     * @param string $target    Name of target (optional)
     *
     * @return string
     */
    public static function sendForm($url, $params, $target = '')
    {
        Util::logDebug('Method ceLTIc\LTI\Tool::sendForm() has been deprecated; please use ceLTIc\LTI\Util::sendForm() instead.',
            true);
        Util::sendForm($url, $params, $target);
    }

###
###    PROTECTED METHODS
###

    /**
     * Process a valid launch request
     *
     * @return bool    True if no error
     */
    protected function onLaunch()
    {
        $this->onError();
    }

    /**
     * Process a valid configure request
     *
     * @return bool    True if no error
     */
    protected function onConfigure()
    {
        $this->onError();
    }

    /**
     * Process a valid dashboard request
     *
     * @return bool    True if no error
     */
    protected function onDashboard()
    {
        $this->onError();
    }

    /**
     * Process a valid content-item request
     *
     * @return bool    True if no error
     */
    protected function onContentItem()
    {
        $this->onError();
    }

    /**
     * Process a valid tool proxy registration request
     *
     * @return bool    True if no error
     */
    protected function onRegister()
    {
        $this->onError();
    }

    /**
     * Process a response to an invalid request
     *
     * @return bool    True if no further error processing required
     */
    protected function onError()
    {
        $this->ok = false;
    }

###
###    PRIVATE METHODS
###

    /**
     * Call any callback function for the requested action.
     *
     * This function may set the redirect_url and output properties.
     *
     * @param string|null $method  Name of method to be called (optional)
     */
    private function doCallback($method = null)
    {
        $callback = $method;
        if (is_null($callback)) {
            $callback = Util::$METHOD_NAMES[$this->messageParameters['lti_message_type']];
        }
        if (method_exists($this, $callback)) {
            $this->$callback();
        } elseif (is_null($method) && $this->ok) {
            $this->ok = false;
            $this->reason = "Message type not supported: {$this->messageParameters['lti_message_type']}";
        }
        if ($this->ok && ($this->messageParameters['lti_message_type'] === 'ToolProxyRegistrationRequest')) {
            $this->platform->save();
        }
    }

    /**
     * Perform the result of an action.
     *
     * This function may redirect the user to another URL rather than returning a value.
     *
     * @return string Output to be displayed (redirection, or display HTML or message)
     */
    private function result()
    {
        if (!$this->ok) {
            $this->onError();
        }
        if (!$this->ok) {
// If not valid, return an error message to the platform if a return URL is provided
            if (!empty($this->returnUrl)) {
                $errorUrl = $this->returnUrl;
                if (strpos($errorUrl, '?') === false) {
                    $errorUrl .= '?';
                } else {
                    $errorUrl .= '&';
                }
                if ($this->debugMode && !is_null($this->reason)) {
                    $errorUrl .= 'lti_errormsg=' . urlencode("Debug error: $this->reason");
                } else {
                    $errorUrl .= 'lti_errormsg=' . urlencode($this->message);
                    if (!is_null($this->reason)) {
                        $errorUrl .= '&lti_errorlog=' . urlencode("Debug error: $this->reason");
                    }
                }
                if (!is_null($this->platform) && isset($this->messageParameters['lti_message_type']) &&
                    ($this->messageParameters['lti_message_type'] === 'ContentItemSelectionRequest')) {
                    $formParams = array();
                    if (isset($this->messageParameters['data'])) {
                        $formParams['data'] = $this->messageParameters['data'];
                    }
                    $version = (isset($this->messageParameters['lti_version'])) ? $this->messageParameters['lti_version'] : Util::LTI_VERSION1;
                    $formParams = $this->signParameters($errorUrl, 'ContentItemSelection', $version, $formParams);
                    $page = Util::sendForm($errorUrl, $formParams);
                    echo $page;
                } else {
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
     * Check the authenticity of the LTI launch request.
     *
     * The platform, resource link and user objects will be initialised if the request is valid.
     *
     * @return bool    True if the request has been successfully validated.
     */
    private function authenticate()
    {
// Get the platform
        $doSavePlatform = false;
        $this->ok = $_SERVER['REQUEST_METHOD'] === 'POST';
        if (!$this->ok) {
            $this->reason = 'LTI messages must use HTTP POST';
        } elseif (!empty($this->jwt) && !empty($this->jwt->hasJwt())) {
            $this->ok = false;
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
                $this->ok = true;
            }
        }
// Check all required launch parameters
        if ($this->ok) {
            $this->ok = isset($this->messageParameters['lti_message_type']) && array_key_exists($this->messageParameters['lti_message_type'],
                    Util::$METHOD_NAMES);
            if (!$this->ok) {
                $this->reason = 'Invalid or missing lti_message_type parameter.';
            }
        }
        if ($this->ok) {
            $this->ok = isset($this->messageParameters['lti_version']) && in_array($this->messageParameters['lti_version'],
                    Util::$LTI_VERSIONS);
            if (!$this->ok) {
                $this->reason = 'Invalid or missing lti_version parameter.';
            }
        }
        if ($this->ok) {
            if ($this->messageParameters['lti_message_type'] === 'basic-lti-launch-request') {
                $this->ok = isset($this->messageParameters['resource_link_id']) && (strlen(trim($this->messageParameters['resource_link_id'])) > 0);
                if (!$this->ok) {
                    $this->reason = 'Missing resource link ID.';
                }
            } elseif ($this->messageParameters['lti_message_type'] === 'ContentItemSelectionRequest') {
                if (isset($this->messageParameters['accept_media_types']) && (strlen(trim($this->messageParameters['accept_media_types'])) > 0)) {
                    $mediaTypes = array_filter(explode(',', str_replace(' ', '', $this->messageParameters['accept_media_types'])),
                        'strlen');
                    $mediaTypes = array_unique($mediaTypes);
                    $this->ok = count($mediaTypes) > 0;
                    if (!$this->ok) {
                        $this->reason = 'No accept_media_types found.';
                    } else {
                        $this->mediaTypes = $mediaTypes;
                    }
                }
                if ($this->ok && !empty($this->jwt) && $this->jwt->hasJwt()) {
                    if (isset($this->messageParameters['accept_types']) && (strlen(trim($this->messageParameters['accept_types'])) > 0)) {
                        $types = array_filter(explode(',', str_replace(' ', '', $this->messageParameters['accept_types'])), 'strlen');
                        $types = array_unique($types);
                        $this->ok = count($types) > 0;
                        if (!$this->ok) {
                            $this->reason = 'No accept_types found.';
                        } else {
                            $this->mediaTypes = $types;
                        }
                    }
                } elseif ($this->ok) {
                    $this->ok = !empty($this->messageParameters['accept_media_types']);
                    if (!$this->ok) {
                        $this->reason = 'No accept_media_types found.';
                    }
                }
                if (!$this->ok) {
                    $this->reason = 'No accept_media_types found.';
                }
                if ($this->ok) {
                    if (isset($this->messageParameters['accept_types']) && (strlen(trim($this->messageParameters['accept_types'])) > 0)) {
                        $acceptTypes = array_filter(explode(',', str_replace(' ', '', $this->messageParameters['accept_types'])),
                            'strlen');
                        $acceptTypes = array_unique($acceptTypes);
                        $this->ok = count($acceptTypes) > 0;
                        if ($this->ok) {
                            $this->acceptTypes = $acceptTypes;
                        }
                    } else {
                        $this->ok = empty($this->jwt) || !$this->jwt->hasJwt();
                    }
                    if (!$this->ok) {
                        $this->reason = 'No accept_types found.';
                    }
                }
                if ($this->ok) {
                    if (isset($this->messageParameters['accept_presentation_document_targets']) &&
                        (strlen(trim($this->messageParameters['accept_presentation_document_targets'])) > 0)) {
                        $documentTargets = array_filter(explode(',',
                                str_replace(' ', '', $this->messageParameters['accept_presentation_document_targets'])), 'strlen');
                        $documentTargets = array_unique($documentTargets);
                        $this->ok = count($documentTargets) > 0;
                        if (!$this->ok) {
                            $this->reason = 'Missing or empty accept_presentation_document_targets parameter.';
                        } else {
                            foreach ($documentTargets as $documentTarget) {
                                $this->ok = $this->checkValue($documentTarget,
                                    array('embed', 'frame', 'iframe', 'window', 'popup', 'overlay', 'none'),
                                    'Invalid value in accept_presentation_document_targets parameter: %s.');
                                if (!$this->ok) {
                                    break;
                                }
                            }
                            if ($this->ok) {
                                $this->documentTargets = $documentTargets;
                            }
                        }
                    } else {
                        $this->ok = false;
                        $this->reason = '';
                    }
                }
                if ($this->ok) {
                    $this->ok = !empty($this->messageParameters['content_item_return_url']);
                    if (!$this->ok) {
                        $this->reason = 'Missing content_item_return_url parameter.';
                    }
                }
            } elseif ($this->messageParameters['lti_message_type'] === 'ToolProxyRegistrationRequest') {
                $this->ok = ((isset($this->messageParameters['reg_key']) && (strlen(trim($this->messageParameters['reg_key'])) > 0)) && (isset($this->messageParameters['reg_password']) && (strlen(trim($this->messageParameters['reg_password'])) >
                    0)) && (isset($this->messageParameters['tc_profile_url']) && (strlen(trim($this->messageParameters['tc_profile_url'])) >
                    0)) && (isset($this->messageParameters['launch_presentation_return_url']) && (strlen(trim($this->messageParameters['launch_presentation_return_url'])) > 0)));
                if ($this->debugMode && !$this->ok) {
                    $this->reason = 'Missing message parameters.';
                }
            }
        }
        $now = time();
// Check consumer key
        if ($this->ok && ($this->messageParameters['lti_message_type'] !== 'ToolProxyRegistrationRequest')) {
            $this->ok = isset($this->messageParameters['oauth_consumer_key']);
            if (!$this->ok) {
                $this->reason = 'Missing consumer key.';
            }
            if ($this->ok) {
                $this->ok = !is_null($this->platform->created);
                if (!$this->ok) {
                    $this->reason = 'Invalid consumer key: ' . $this->messageParameters['oauth_consumer_key'];
                }
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
                            $this->reason = 'A platform GUID must be included in the launch request.';
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
            if ($this->ok) {
                if ($this->messageParameters['lti_message_type'] === 'ContentItemSelectionRequest') {
                    if (isset($this->messageParameters['accept_unsigned'])) {
                        $this->ok = $this->checkValue($this->messageParameters['accept_unsigned'], array('true', 'false'),
                            'Invalid value for accept_unsigned parameter: %s.');
                    }
                    if ($this->ok && isset($this->messageParameters['accept_multiple'])) {
                        $this->ok = $this->checkValue($this->messageParameters['accept_multiple'], array('true', 'false'),
                            'Invalid value for accept_multiple parameter: %s.');
                    }
                    if ($this->ok && isset($this->messageParameters['accept_copy_advice'])) {
                        $this->ok = $this->checkValue($this->messageParameters['accept_copy_advice'], array('true', 'false'),
                            'Invalid value for accept_copy_advice parameter: %s.');
                    }
                    if ($this->ok && isset($this->messageParameters['auto_create'])) {
                        $this->ok = $this->checkValue($this->messageParameters['auto_create'], array('true', 'false'),
                            'Invalid value for auto_create parameter: %s.');
                    }
                    if ($this->ok && isset($this->messageParameters['can_confirm'])) {
                        $this->ok = $this->checkValue($this->messageParameters['can_confirm'], array('true', 'false'),
                            'Invalid value for can_confirm parameter: %s.');
                    }
                } elseif (isset($this->messageParameters['launch_presentation_document_target'])) {
                    $this->ok = $this->checkValue($this->messageParameters['launch_presentation_document_target'],
                        array('embed', 'frame', 'iframe', 'window', 'popup', 'overlay'),
                        'Invalid value for launch_presentation_document_target parameter: %s.');
                }
            }
        }

        if ($this->ok && ($this->messageParameters['lti_message_type'] === 'ToolProxyRegistrationRequest')) {
            $this->ok = $this->messageParameters['lti_version'] === Util::LTI_VERSION2;
            if (!$this->ok) {
                $this->reason = 'Invalid lti_version parameter';
            }
            if ($this->ok) {
                $url = $this->messageParameters['tc_profile_url'];
                if (strpos($url, '?') === false) {
                    $url .= '?';
                } else {
                    $url .= '&';
                }
                $url .= 'lti_version=' . Util::LTI_VERSION2;
                $http = new HttpMessage($url, 'GET', null, 'Accept: application/vnd.ims.lti.v2.toolconsumerprofile+json');
                $this->ok = $http->send();
                if (!$this->ok) {
                    $this->reason = 'Platform profile not accessible.';
                } else {
                    $tcProfile = json_decode($http->response);
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
                $missing = array();
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
                                    array_keys(array_intersect(self::$CUSTOM_SUBSTITUTION_VARIABLES, array($name)))))) {
                            $missing[$name] = true;
                        }
                    }
                }
                if (!empty($missing)) {
                    ksort($missing);
                    $this->reason = 'Required capability not offered - \'' . implode('\', \'', array_keys($missing)) . '\'';
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
                            $this->reason .= "'{$format}' [" . implode(', ', $service->actions) . ']';
                        }
                    }
                }
            }
            if ($this->ok) {
                if ($this->messageParameters['lti_message_type'] === 'ToolProxyRegistrationRequest') {
                    $this->platform->profile = $tcProfile;
                    $this->platform->secret = $this->messageParameters['reg_password'];
                    $this->platform->ltiVersion = $this->messageParameters['lti_version'];
                    $this->platform->name = $tcProfile->product_instance->service_owner->service_owner_name->default_value;
                    $this->platform->consumerName = $this->platform->name;
                    $this->platform->consumerVersion = "{$tcProfile->product_instance->product_info->product_family->code}-{$tcProfile->product_instance->product_info->product_version}";
                    $this->platform->consumerGuid = $tcProfile->product_instance->guid;
                    $this->platform->enabled = true;
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
            $url .= 'lti_version=' . $this->messageParameters['lti_version'];
            $http = new HttpMessage($url, 'GET', null, 'Accept: application/vnd.ims.lti.v2.toolconsumerprofile+json');
            if ($http->send()) {
                $tcProfile = json_decode($http->response);
                if (!is_null($tcProfile)) {
                    $this->platform->profile = $tcProfile;
                    $doSavePlatform = true;
                }
            }
        }

        if ($this->ok) {

// Check if a relaunch is being requested
            if (isset($this->messageParameters['relaunch_url'])) {
                if (empty($this->messageParameters['platform_state'])) {
                    $this->ok = false;
                    $this->reason = 'Missing or empty platform_state parameter';
                } else {
                    $this->sendRelaunchRequest();
                }
            } else {

// Validate message parameter constraints
                $invalidParameters = array();
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
                            if (strpos($this->context->type, 'http://purl.imsglobal.org/vocab/lis/v2/course#') === 0) {
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
// Delete any existing custom parameters
                        foreach ($this->platform->getSettings() as $name => $value) {
                            if ((strpos($name, 'custom_') === 0) && (!in_array($name, self::$LTI_RETAIN_SETTING_NAMES))) {
                                $this->platform->setSetting($name);
                                $doSavePlatform = true;
                            }
                        }
                        if (!empty($this->context)) {
                            foreach ($this->context->getSettings() as $name => $value) {
                                if ((strpos($name, 'custom_') === 0) && (!in_array($name, self::$LTI_RETAIN_SETTING_NAMES))) {
                                    $this->context->setSetting($name);
                                }
                            }
                        }
                        foreach ($this->resourceLink->getSettings() as $name => $value) {
                            if ((strpos($name, 'custom_') === 0) && (!in_array($name, self::$LTI_RETAIN_SETTING_NAMES))) {
                                $this->resourceLink->setSetting($name);
                            }
                        }
// Save LTI parameters
                        foreach (self::$LTI_CONSUMER_SETTING_NAMES as $name) {
                            if (isset($this->messageParameters[$name])) {
                                $this->platform->setSetting($name, $this->messageParameters[$name]);
                            } else if (!in_array($name, self::$LTI_RETAIN_SETTING_NAMES)) {
                                $this->platform->setSetting($name);
                            }
                        }
                        if (!empty($this->context)) {
                            foreach (self::$LTI_CONTEXT_SETTING_NAMES as $name) {
                                if (isset($this->messageParameters[$name])) {
                                    $this->context->setSetting($name, $this->messageParameters[$name]);
                                } else if (!in_array($name, self::$LTI_RETAIN_SETTING_NAMES)) {
                                    $this->context->setSetting($name);
                                }
                            }
                        }
                        foreach (self::$LTI_RESOURCE_LINK_SETTING_NAMES as $name) {
                            if (isset($this->messageParameters[$name])) {
                                $this->resourceLink->setSetting($name, $this->messageParameters[$name]);
                            } else if (!in_array($name, self::$LTI_RETAIN_SETTING_NAMES)) {
                                $this->resourceLink->setSetting($name);
                            }
                        }
// Save other custom parameters at all levels
                        foreach ($this->messageParameters as $name => $value) {
                            if ((strpos($name, 'custom_') === 0) && !in_array($name,
                                    array_merge(self::$LTI_CONSUMER_SETTING_NAMES, self::$LTI_CONTEXT_SETTING_NAMES,
                                        self::$LTI_RESOURCE_LINK_SETTING_NAMES))) {
                                $this->platform->setSetting($name, $value);
                                if (!empty($this->context)) {
                                    $this->context->setSetting($name, $value);
                                }
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
                    $firstname = (isset($this->messageParameters['lis_person_name_given'])) ? $this->messageParameters['lis_person_name_given'] : '';
                    $lastname = (isset($this->messageParameters['lis_person_name_family'])) ? $this->messageParameters['lis_person_name_family'] : '';
                    $fullname = (isset($this->messageParameters['lis_person_name_full'])) ? $this->messageParameters['lis_person_name_full'] : '';
                    $this->userResult->setNames($firstname, $lastname, $fullname);

// Set the sourcedId
                    if (isset($this->messageParameters['lis_person_sourcedid'])) {
                        $this->userResult->sourcedId = $this->messageParameters['lis_person_sourcedid'];
                    }

// Set the username
                    if (isset($this->messageParameters['ext_username'])) {
                        $this->userResult->username = $this->messageParameters['ext_username'];
                    } elseif (isset($this->messageParameters['ext_user_username'])) {
                        $this->userResult->username = $this->messageParameters['ext_user_username'];
                    } elseif (isset($this->messageParameters['custom_username'])) {
                        $this->userResult->username = $this->messageParameters['custom_username'];
                    } elseif (isset($this->messageParameters['custom_user_username'])) {
                        $this->userResult->username = $this->messageParameters['custom_user_username'];
                    }

// Set the user email
                    $email = (isset($this->messageParameters['lis_person_contact_email_primary'])) ? $this->messageParameters['lis_person_contact_email_primary'] : '';
                    $this->userResult->setEmail($email, $this->defaultEmail);

// Set the user image URI
                    if (isset($this->messageParameters['user_image'])) {
                        $this->userResult->image = $this->messageParameters['user_image'];
                    }

// Set the user roles
                    if (isset($this->messageParameters['roles'])) {
                        $this->userResult->roles = self::parseRoles($this->messageParameters['roles'],
                                $this->messageParameters['lti_version']);
                    }

// Initialise the platform and check for changes
                    $this->platform->defaultEmail = $this->defaultEmail;
                    if ($this->platform->ltiVersion !== $this->messageParameters['lti_version']) {
                        $this->platform->ltiVersion = $this->messageParameters['lti_version'];
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
                        } elseif (!$this->platform->protected) {
                            $doSavePlatform = ($this->platform->consumerGuid !== $this->messageParameters['tool_consumer_instance_guid']);
                            if ($doSavePlatform) {
                                $this->platform->consumerGuid = $this->messageParameters['tool_consumer_instance_guid'];
                            }
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

// Persist changes to cpntext
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
     * @return bool    True if no error is reported
     */
    private function checkForShare()
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
     * @return bool    True if form was generated
     */
    private function sendAuthenticationRequest($hint)
    {

        $clientId = null;
        if (isset($_REQUEST['client_id'])) {
            $clientId = $_REQUEST['client_id'];
        }
        $deploymentId = null;
        if (isset($_REQUEST['lti_deployment_id'])) {
            $deploymentId = $_REQUEST['lti_deployment_id'];
        }
        $this->platform = Platform::fromPlatformId($_REQUEST['iss'], $clientId, $deploymentId, $this->dataConnector);
        if ($this->platform->debugMode) {
            Util::$logLevel = Util::LOGLEVEL_DEBUG;
        }
        $ok = !is_null($this->platform) && !empty($this->platform->authenticationUrl);
        if (!$ok) {
            $this->reason = 'Platform not found or no platform authentication request URL';
        } else {
            $oauthRequest = OAuth\OAuthRequest::from_request();
            do {
                $nonce = new PlatformNonce($this->platform, Util::getRandomString());
                $ok = !$nonce->load();
            } while (!$ok);
            $ok = $nonce->save();
            if ($ok) {
                $redirectUri = $oauthRequest->get_normalized_http_url();
                if (!empty($_SERVER['QUERY_STRING'])) {
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $redirectUri .= "?{$_SERVER['QUERY_STRING']}";
                    } else {  // Remove all parameters added by platform from query string
                        $queryString = '';
                        $params = explode('&', $_SERVER['QUERY_STRING']);
                        foreach ($params as $param) {
                            $parts = explode('=', $param, 2);
                            if (!in_array($parts[0],
                                    array('iss', 'target_link_uri', 'login_hint', 'lti_message_hint', 'client_id', 'lti_deployment_id'))) {
                                if (count($parts) <= 1) {
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
                }
                $params = array(
                    'client_id' => $this->platform->clientId,
                    'login_hint' => $_REQUEST['login_hint'],
                    'nonce' => Util::getRandomString(32),
                    'prompt' => 'none',
                    'redirect_uri' => $redirectUri,
                    'response_mode' => 'form_post',
                    'response_type' => 'id_token',
                    'scope' => 'openid',
                    'state' => $nonce->getValue()
                );
                if (!is_null($hint)) {
                    $params['lti_message_hint'] = $hint;
                }
                $this->output = Util::sendForm($this->platform->authenticationUrl, $params);
            } else {
                $this->reason = 'Unable to generate a state value';
            }
        }

        return $ok;
    }

    /**
     * Generate a form to perform a relaunch request.
     */
    private function sendRelaunchRequest()
    {
        do {
            $nonce = new PlatformNonce($this->platform, Util::getRandomString());
            $ok = !$nonce->load();
        } while (!$ok);
        $ok = $nonce->save();
        if ($ok) {
            $params = array(
                'tool_state' => $nonce->getValue(),
                'platform_state' => $this->messageParameters['platform_state']
            );
            $params = $this->platform->addSignature($this->messageParameters['relaunch_url'], $params);
            $this->output = Util::sendForm($this->messageParameters['relaunch_url'], $params);
        } else {
            $this->reason = 'Unable to generate a state value';
        }
    }

    /**
     * Validate a parameter value from an array of permitted values.
     *
     * @param mixed $value      Value to be checked
     * @param array $values     Array of permitted values
     * @param string $reason    Reason to generate when the value is not permitted
     *
     * @return bool    True if value is valid
     */
    private function checkValue($value, $values, $reason)
    {
        $ok = in_array($value, $values);
        if (!$ok && !empty($reason)) {
            $this->reason = sprintf($reason, $value);
        }

        return $ok;
    }

}
