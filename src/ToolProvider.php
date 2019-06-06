<?php

namespace ceLTIc\LTI;

use ceLTIc\LTI\DataConnector;
use ceLTIc\LTI\MediaType;
use ceLTIc\LTI\Profile;
use ceLTIc\LTI\Http\HttpMessage;
use ceLTIc\LTI\OAuth;
use ceLTIc\LTI\ApiHook\ApiHook;

/**
 * Class to represent an LTI Tool Provider
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ToolProvider
{
    use ApiHook;

    /**
     * Default connection error message.
     */
    const CONNECTION_ERROR_MESSAGE = 'Sorry, there was an error connecting you to the application.';

    /**
     * LTI version 1 for messages.
     */
    const LTI_VERSION1 = 'LTI-1p0';

    /**
     * LTI version 2 for messages.
     */
    const LTI_VERSION2 = 'LTI-2p0';

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
     * Permitted LTI versions for messages.
     */
    private static $LTI_VERSIONS = array(self::LTI_VERSION1, self::LTI_VERSION2);

    /**
     * List of supported message types and associated class methods.
     */
    private static $METHOD_NAMES = array('basic-lti-launch-request' => 'onLaunch',
        'ConfigureLaunchRequest' => 'onConfigure',
        'DashboardRequest' => 'onDashboard',
        'ContentItemSelectionRequest' => 'onContentItem',
        'ToolProxyRegistrationRequest' => 'onRegister'
    );

    /**
     * Names of LTI parameters to be retained in the consumer settings property.
     */
    private static $LTI_CONSUMER_SETTING_NAMES = array('custom_tc_profile_url', 'custom_system_setting_url', 'custom_oauth2_access_token_url');

    /**
     * Names of LTI parameters to be retained in the context settings property.
     */
    private static $LTI_CONTEXT_SETTING_NAMES = array('custom_context_setting_url',
        'custom_lineitems_url', 'custom_results_url',
        'custom_context_memberships_url');

    /**
     * Names of LTI parameters to be retained in the resource link settings property.
     */
    private static $LTI_RESOURCE_LINK_SETTING_NAMES = array('lis_result_sourcedid', 'lis_outcome_service_url',
        'ext_ims_lis_basic_outcome_url', 'ext_ims_lis_resultvalue_sourcedids',
        'ext_ims_lis_memberships_id', 'ext_ims_lis_memberships_url',
        'ext_ims_lti_tool_setting', 'ext_ims_lti_tool_setting_id', 'ext_ims_lti_tool_setting_url',
        'custom_link_setting_url',
        'custom_lineitem_url', 'custom_result_url');

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
        'Results.url' => 'custom_results_url',
        'Result.url' => 'custom_result_url',
        'ToolProxyBinding.memberships.url' => 'custom_context_memberships_url',
        'LtiLink.memberships.url' => 'custom_link_memberships_url');

    /**
     * True if the last request was successful.
     *
     * @var bool $ok
     */
    public $ok = true;

    /**
     * Tool Consumer object.
     *
     * @var ToolConsumer|null $consumer
     */
    public $consumer = null;

    /**
     * Return URL provided by tool consumer.
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
     * Data connector object.
     *
     * @var DataConnector|null $dataConnector
     */
    public $dataConnector = null;

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
     * Base URL for tool provider service
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
     * Services required by Tool Provider
     *
     * @var array|null $requiredServices
     */
    public $requiredServices = null;

    /**
     * Optional services used by Tool Provider
     *
     * @var array|null $optionalServices
     */
    public $optionalServices = null;

    /**
     * Resource handlers for Tool Provider
     *
     * @var array|null $resourceHandlers
     */
    public $resourceHandlers = null;

    /**
     * URL to redirect user to on successful completion of the request.
     *
     * @var string|null $redirectUrl
     */
    protected $redirectUrl = null;

    /**
     * Media types accepted by the Tool Consumer.
     *
     * @var array|null $mediaTypes
     */
    protected $mediaTypes = null;

    /**
     * Document targets accepted by the Tool Consumer.
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
     * Whether debug messages explaining the cause of errors are to be returned to the tool consumer.
     *
     * @var bool $debugMode
     */
    protected $debugMode = false;

    /**
     * LTI message parameters.
     *
     * @var array|null $messageParameters
     */
    protected $messageParameters = null;

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
    function __construct($dataConnector)
    {
        $this->constraints = array();
        $this->dataConnector = $dataConnector;
        $this->ok = !is_null($this->dataConnector);
        $this->vendor = new Profile\Item();
        $this->product = new Profile\Item();
        $this->requiredServices = array();
        $this->optionalServices = array();
        $this->resourceHandlers = array();
    }

    /**
     * Process an incoming request
     */
    public function handleRequest()
    {
        if ($this->ok) {
            $this->getMessageParameters();
            if ($this->authenticate()) {
                $this->doCallback();
            }
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
     * @return array Array of ToolConsumer objects
     */
    public function getConsumers()
    {
        return $this->dataConnector->getToolConsumers();
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
        $services = $this->consumer->profile->service_offered;
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
     * Send the tool proxy to the Tool Consumer
     *
     * @return bool    True if the tool proxy was accepted
     */
    public function doToolProxyService()
    {
// Create tool proxy
        $toolProxyService = $this->findService('application/vnd.ims.lti.v2.toolproxy+json', array('POST'));
        $secret = DataConnector\DataConnector::getRandomString(12);
        $toolProxy = new MediaType\ToolProxy($this, $toolProxyService, $secret);
        $http = $this->consumer->doServiceRequest($toolProxyService, 'POST', 'application/vnd.ims.lti.v2.toolproxy+json',
            json_encode($toolProxy));
        $ok = $http->ok && ($http->status == 201) && isset($http->responseJson->tool_proxy_guid) && (strlen($http->responseJson->tool_proxy_guid) > 0);
        if ($ok) {
            $this->consumer->setKey($http->responseJson->tool_proxy_guid);
            $this->consumer->secret = $toolProxy->security_contract->shared_secret;
            $this->consumer->toolProxy = json_encode($toolProxy);
            $this->consumer->save();
        }

        return $ok;
    }

    /**
     * Get the message parameters
     *
     * @return array The message parameter array
     */
    public function getMessageParameters()
    {
        if ($this->ok && is_null($this->messageParameters)) {
            $this->messageParameters = OAuth\OAuthUtil::parse_parameters(file_get_contents(OAuth\OAuthRequest::$POST_INPUT));
            if (!empty($this->messageParameters['oauth_consumer_key'])) {
                $this->consumer = new ToolConsumer($this->messageParameters['oauth_consumer_key'], $this->dataConnector);
            }

// Set debug mode
            $this->debugMode = isset($this->messageParameters['custom_debug']) && (strtolower($this->messageParameters['custom_debug']) === 'true');
// Set return URL if available
            if (isset($this->messageParameters['launch_presentation_return_url'])) {
                $this->returnUrl = $this->messageParameters['launch_presentation_return_url'];
            } elseif (isset($this->messageParameters['content_item_return_url'])) {
                $this->returnUrl = $this->messageParameters['content_item_return_url'];
            }
        }

        return $this->messageParameters;
    }

    /**
     * Get an array of fully qualified user roles
     *
     * @param mixed  $roles       Comma-separated list of roles or array of roles
     * @param string $ltiVersion  LTI version (default is LTI-1p0)
     *
     * @return array Array of roles
     */
    public static function parseRoles($roles, $ltiVersion = self::LTI_VERSION1)
    {
        if (!is_array($roles)) {
            $roles = explode(',', $roles);
        }
        $parsedRoles = array();
        foreach ($roles as $role) {
            $role = trim($role);
            if (!empty($role)) {
                if ($ltiVersion === self::LTI_VERSION1) {
                    if (substr($role, 0, 4) !== 'urn:') {
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
     * Generate a web page containing an auto-submitted form of parameters.
     *
     * @param string $url         URL to which the form should be submitted
     * @param array    $params    Array of form parameters
     * @param string $target    Name of target (optional)
     *
     * @return string
     */
    public static function sendForm($url, $params, $target = '')
    {
        $page = <<< EOD
<html>
<head>
<title>IMS LTI message</title>
<script type="text/javascript">
//<![CDATA[
function doOnLoad() {
    document.forms[0].submit();
}

window.onload=doOnLoad;
//]]>
</script>
</head>
<body>
<form action="{$url}" method="post" target="" encType="application/x-www-form-urlencoded">

EOD;
        foreach ($params as $key => $value) {
            $key = htmlentities($key, ENT_COMPAT | ENT_HTML401, 'UTF-8');
            if (!is_array($value)) {
                $value = htmlentities($value, ENT_COMPAT | ENT_HTML401, 'UTF-8');
                $page .= <<< EOD
    <input type="hidden" name="{$key}" value="{$value}" />

EOD;
            } else {
                foreach ($value as $element) {
                    $element = htmlentities($element, ENT_COMPAT | ENT_HTML401, 'UTF-8');
                    $page .= <<< EOD
    <input type="hidden" name="{$key}" value="{$element}" />

EOD;
                }
            }
        }

        $page .= <<< EOD
</form>
</body>
</html>
EOD;

        return $page;
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
            $callback = self::$METHOD_NAMES[$this->messageParameters['lti_message_type']];
        }
        if (method_exists($this, $callback)) {
            $this->$callback();
        } elseif (is_null($method) && $this->ok) {
            $this->ok = false;
            $this->reason = "Message type not supported: {$this->messageParameters['lti_message_type']}";
        }
        if ($this->ok && ($this->messageParameters['lti_message_type'] == 'ToolProxyRegistrationRequest')) {
            $this->consumer->save();
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

// If not valid, return an error message to the tool consumer if a return URL is provided
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
                if (!is_null($this->consumer) && isset($this->messageParameters['lti_message_type']) && (($this->messageParameters['lti_message_type'] === 'ContentItemSelectionRequest') ||
                    ($this->messageParameters['lti_message_type'] === 'LtiDeepLinkingRequest'))) {
                    $formParams = array();
                    if (isset($this->messageParameters['data'])) {
                        $formParams['data'] = $this->messageParameters['data'];
                    }
                    $version = (isset($this->messageParameters['lti_version'])) ? $this->messageParameters['lti_version'] : self::LTI_VERSION1;
                    $formParams = $this->consumer->signParameters($errorUrl, 'ContentItemSelection', $version, $formParams);
                    $page = self::sendForm($errorUrl, $formParams);
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
     * The consumer, resource link and user objects will be initialised if the request is valid.
     *
     * @return bool    True if the request has been successfully validated.
     */
    private function authenticate()
    {
// Get the consumer
        $doSaveConsumer = false;
        $this->ok = $_SERVER['REQUEST_METHOD'] === 'POST';
        if (!$this->ok) {
            $this->reason = 'Message should be an HTTP POST request';
        }
// Check all required launch parameters
        if ($this->ok) {
            $this->ok = isset($this->messageParameters['lti_message_type']) && array_key_exists($this->messageParameters['lti_message_type'],
                    self::$METHOD_NAMES);
            if (!$this->ok) {
                $this->reason = 'Invalid or missing lti_message_type parameter.';
            }
        }
        if ($this->ok) {
            $this->ok = isset($this->messageParameters['lti_version']) && in_array($this->messageParameters['lti_version'],
                    self::$LTI_VERSIONS);
            if (!$this->ok) {
                $this->reason = 'Invalid or missing lti_version parameter.';
            }
        }
        if ($this->ok) {
            if (($this->messageParameters['lti_message_type'] === 'basic-lti-launch-request') || ($this->messageParameters['lti_message_type'] === 'LtiResourceLinkRequest') || ($this->messageParameters['lti_message_type'] === 'DashboardRequest')) {
                $this->ok = isset($this->messageParameters['resource_link_id']) && (strlen(trim($this->messageParameters['resource_link_id'])) > 0);
                if (!$this->ok) {
                    $this->reason = 'Missing resource link ID.';
                }
            } elseif (($this->messageParameters['lti_message_type'] === 'ContentItemSelectionRequest') || ($this->messageParameters['lti_message_type'] === 'LtiDeepLinkingRequest')) {
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
                } else {
                    $this->ok = false;
                }
                if ($this->ok && isset($this->messageParameters['accept_presentation_document_targets']) && (strlen(trim($this->messageParameters['accept_presentation_document_targets'])) > 0)) {
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
                }
                if ($this->ok) {
                    $this->ok = isset($this->messageParameters['content_item_return_url']) && (strlen(trim($this->messageParameters['content_item_return_url'])) > 0);
                    if (!$this->ok) {
                        $this->reason = 'Missing content_item_return_url parameter.';
                    }
                }
            } elseif ($this->messageParameters['lti_message_type'] == 'ToolProxyRegistrationRequest') {
                $this->ok = ((isset($this->messageParameters['reg_key']) && (strlen(trim($this->messageParameters['reg_key'])) > 0)) &&
                    (isset($this->messageParameters['reg_password']) && (strlen(trim($this->messageParameters['reg_password'])) > 0)) &&
                    (isset($this->messageParameters['tc_profile_url']) && (strlen(trim($this->messageParameters['tc_profile_url'])) > 0)) &&
                    (isset($this->messageParameters['launch_presentation_return_url']) && (strlen(trim($this->messageParameters['launch_presentation_return_url'])) > 0)));
                if ($this->debugMode && !$this->ok) {
                    $this->reason = 'Missing message parameters.';
                }
            }
        }
        $now = time();
// Check consumer key
        if ($this->ok && ($this->messageParameters['lti_message_type'] != 'ToolProxyRegistrationRequest')) {
            $this->ok = isset($this->messageParameters['oauth_consumer_key']);
            if (!$this->ok) {
                $this->reason = 'Missing consumer key.';
            }
            if ($this->ok) {
                $this->ok = !is_null($this->consumer->created);
                if (!$this->ok) {
                    $this->reason = 'Invalid consumer key: ' . $this->messageParameters['oauth_consumer_key'];
                }
            }
            if ($this->ok) {
                $today = date('Y-m-d', $now);
                if (is_null($this->consumer->lastAccess)) {
                    $doSaveConsumer = true;
                } else {
                    $last = date('Y-m-d', $this->consumer->lastAccess);
                    $doSaveConsumer = $doSaveConsumer || ($last !== $today);
                }
                $this->consumer->lastAccess = $now;
                $this->consumer->signatureMethod = isset($this->messageParameters['oauth_signature_method']) ? $this->messageParameters['oauth_signature_method'] :
                    $this->consumer->signatureMethod;
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
                    $res = $server->verify_request($request);
                } catch (\Exception $e) {
                    $this->ok = false;
                    if (empty($this->reason)) {
                        $consumer = new OAuth\OAuthConsumer($this->consumer->getKey(), $this->consumer->secret);
                        $signature = $request->build_signature($method, $consumer, false);
                        if ($this->debugMode) {
                            $this->reason = $e->getMessage();
                        }
                        if (empty($this->reason)) {
                            $this->reason = 'OAuth signature check failed - perhaps an incorrect secret or timestamp.';
                        }
                        $this->details[] = 'Current timestamp: ' . time();
                        $this->details[] = "Expected signature: {$signature}";
                        $this->details[] = "Base string: {$request->base_string}";
                    }
                }
            }
            if ($this->ok) {
                if ($this->consumer->protected) {
                    if (!is_null($this->consumer->consumerGuid)) {
                        $this->ok = empty($this->messageParameters['tool_consumer_instance_guid']) ||
                            ($this->consumer->consumerGuid === $this->messageParameters['tool_consumer_instance_guid']);
                        if (!$this->ok) {
                            $this->reason = 'Request is from an invalid tool consumer.';
                        }
                    } else {
                        $this->ok = isset($this->messageParameters['tool_consumer_instance_guid']);
                        if (!$this->ok) {
                            $this->reason = 'A tool consumer GUID must be included in the launch request.';
                        }
                    }
                }
                if ($this->ok) {
                    $this->ok = $this->consumer->enabled;
                    if (!$this->ok) {
                        $this->reason = 'Tool consumer has not been enabled by the tool provider.';
                    }
                }
                if ($this->ok) {
                    $this->ok = is_null($this->consumer->enableFrom) || ($this->consumer->enableFrom <= $now);
                    if ($this->ok) {
                        $this->ok = is_null($this->consumer->enableUntil) || ($this->consumer->enableUntil > $now);
                        if (!$this->ok) {
                            $this->reason = 'Tool consumer access has expired.';
                        }
                    } else {
                        $this->reason = 'Tool consumer access is not yet available.';
                    }
                }
            }

// Validate other message parameter values
            if ($this->ok) {
                if (($this->messageParameters['lti_message_type'] === 'ContentItemSelectionRequest') || ($this->messageParameters['lti_message_type'] === 'LtiDeepLinkingRequest')) {
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
            $this->ok = $this->messageParameters['lti_version'] == self::LTI_VERSION2;
            if (!$this->ok) {
                $this->reason = 'Invalid lti_version parameter';
            }
            if ($this->ok) {
                $url = $this->messageParameters['tc_profile_url'];
                if (strpos($url, '?') === FALSE) {
                    $url .= '?';
                } else {
                    $url .= '&';
                }
                $url .= 'lti_version=' . self::LTI_VERSION2;
                $http = new HttpMessage($url, 'GET', null, 'Accept: application/vnd.ims.lti.v2.toolconsumerprofile+json');
                $this->ok = $http->send();
                if (!$this->ok) {
                    $this->reason = 'Tool consumer profile not accessible.';
                } else {
                    $tcProfile = json_decode($http->response);
                    $this->ok = !is_null($tcProfile);
                    if (!$this->ok) {
                        $this->reason = 'Invalid JSON in tool consumer profile.';
                    }
                }
            }
// Check for required capabilities
            if ($this->ok) {
                $this->consumer = new ToolConsumer($this->messageParameters['reg_key'], $this->dataConnector);
                $this->consumer->profile = $tcProfile;
                $capabilities = $this->consumer->profile->capability_offered;
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
                    $this->consumer->profile = $tcProfile;
                    $this->consumer->secret = $this->messageParameters['reg_password'];
                    $this->consumer->ltiVersion = $this->messageParameters['lti_version'];
                    $this->consumer->name = $tcProfile->product_instance->service_owner->service_owner_name->default_value;
                    $this->consumer->consumerName = $this->consumer->name;
                    $this->consumer->consumerVersion = "{$tcProfile->product_instance->product_info->product_family->code}-{$tcProfile->product_instance->product_info->product_version}";
                    $this->consumer->consumerGuid = $tcProfile->product_instance->guid;
                    $this->consumer->enabled = true;
                    $this->consumer->protected = true;
                    $doSaveConsumer = true;
                }
            }
        } elseif ($this->ok && !empty($this->messageParameters['custom_tc_profile_url']) && empty($this->consumer->profile)) {
            $url = $this->messageParameters['custom_tc_profile_url'];
            if (strpos($url, '?') === FALSE) {
                $url .= '?';
            } else {
                $url .= '&';
            }
            $url .= 'lti_version=' . $this->messageParameters['lti_version'];
            $http = new HttpMessage($url, 'GET', null, 'Accept: application/vnd.ims.lti.v2.toolconsumerprofile+json');
            if ($http->send()) {
                $tcProfile = json_decode($http->response);
                if (!is_null($tcProfile)) {
                    $this->consumer->profile = $tcProfile;
                    $doSaveConsumer = true;
                }
            }
        }

// Validate message parameter constraints
        if ($this->ok) {
            $invalidParameters = array();
            foreach ($this->constraints as $name => $constraint) {
                if (empty($constraint['messages']) || in_array($this->messageParameters['lti_message_type'], $constraint['messages'])) {
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
        }

        if ($this->ok) {

// Set the request context
            $contextId = '';
            if ($this->hasApiHook(self::$CONTEXT_ID_HOOK, $this->consumer->getFamilyCode())) {
                $className = $this->getApiHook(self::$CONTEXT_ID_HOOK, $this->consumer->getFamilyCode());
                $tpHook = new $className($this);
                $contextId = $tpHook->getContextId();
            }
            if (empty($contextId) && isset($this->messageParameters['context_id'])) {
                $contextId = trim($this->messageParameters['context_id']);
            }
            if (!empty($contextId)) {
                $this->context = Context::fromConsumer($this->consumer, $contextId);
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
                }
            }

// Set the request resource link
            if (isset($this->messageParameters['resource_link_id'])) {
                $contentItemId = '';
                if (isset($this->messageParameters['custom_content_item_id'])) {
                    $contentItemId = $this->messageParameters['custom_content_item_id'];
                }
                $this->resourceLink = ResourceLink::fromConsumer($this->consumer,
                        trim($this->messageParameters['resource_link_id']), $contentItemId);
                if (!empty($this->context)) {
                    $this->resourceLink->setContextId($this->context->getRecordId());
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
                foreach ($this->consumer->getSettings() as $name => $value) {
                    if (strpos($name, 'custom_') === 0) {
                        $this->consumer->setSetting($name);
                        $doSaveConsumer = true;
                    }
                }
                if (!empty($this->context)) {
                    foreach ($this->context->getSettings() as $name => $value) {
                        if (strpos($name, 'custom_') === 0) {
                            $this->context->setSetting($name);
                        }
                    }
                }
                foreach ($this->resourceLink->getSettings() as $name => $value) {
                    if (strpos($name, 'custom_') === 0) {
                        $this->resourceLink->setSetting($name);
                    }
                }
// Save LTI parameters
                foreach (self::$LTI_CONSUMER_SETTING_NAMES as $name) {
                    if (isset($this->messageParameters[$name])) {
                        $this->consumer->setSetting($name, $this->messageParameters[$name]);
                    } else {
                        $this->consumer->setSetting($name);
                    }
                }
                if (!empty($this->context)) {
                    foreach (self::$LTI_CONTEXT_SETTING_NAMES as $name) {
                        if (isset($this->messageParameters[$name])) {
                            $this->context->setSetting($name, $this->messageParameters[$name]);
                        } else {
                            $this->context->setSetting($name);
                        }
                    }
                }
                foreach (self::$LTI_RESOURCE_LINK_SETTING_NAMES as $name) {
                    if (isset($this->messageParameters[$name])) {
                        $this->resourceLink->setSetting($name, $this->messageParameters[$name]);
                    } else {
                        $this->resourceLink->setSetting($name);
                    }
                }
// Save other custom parameters at all levels
                foreach ($this->messageParameters as $name => $value) {
                    if ((strpos($name, 'custom_') === 0) &&
                        !in_array($name,
                            array_merge(self::$LTI_CONSUMER_SETTING_NAMES, self::$LTI_CONTEXT_SETTING_NAMES,
                                self::$LTI_RESOURCE_LINK_SETTING_NAMES))) {
                        $this->consumer->setSetting($name, $value);
                        if (!empty($this->context)) {
                            $this->context->setSetting($name, $value);
                        }
                        $this->resourceLink->setSetting($name, $value);
                    }
                }
            }

// Set the user instance
            $userId = '';
            if ($this->hasApiHook(self::$USER_ID_HOOK, $this->consumer->getFamilyCode())) {
                $className = $this->getApiHook(self::$USER_ID_HOOK, $this->consumer->getFamilyCode());
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

// Set the user email
            $email = (isset($this->messageParameters['lis_person_contact_email_primary'])) ? $this->messageParameters['lis_person_contact_email_primary'] : '';
            $this->userResult->setEmail($email, $this->defaultEmail);

// Set the user image URI
            if (isset($this->messageParameters['user_image'])) {
                $this->userResult->image = $this->messageParameters['user_image'];
            }

// Set the user roles
            if (isset($this->messageParameters['roles'])) {
                $this->userResult->roles = self::parseRoles($this->messageParameters['roles'], $this->consumer->ltiVersion);
            }

// Initialise the consumer and check for changes
            $this->consumer->defaultEmail = $this->defaultEmail;
            if ($this->consumer->ltiVersion !== $this->messageParameters['lti_version']) {
                $this->consumer->ltiVersion = $this->messageParameters['lti_version'];
                $doSaveConsumer = true;
            }
            if (isset($this->messageParameters['tool_consumer_instance_name'])) {
                if ($this->consumer->consumerName !== $this->messageParameters['tool_consumer_instance_name']) {
                    $this->consumer->consumerName = $this->messageParameters['tool_consumer_instance_name'];
                    $doSaveConsumer = true;
                }
            }
            if (isset($this->messageParameters['tool_consumer_info_product_family_code'])) {
                $version = $this->messageParameters['tool_consumer_info_product_family_code'];
                if (isset($this->messageParameters['tool_consumer_info_version'])) {
                    $version .= "-{$this->messageParameters['tool_consumer_info_version']
                        }";
                }
// do not delete any existing consumer version if none is passed
                if ($this->consumer->consumerVersion !== $version) {
                    $this->consumer->consumerVersion = $version;
                    $doSaveConsumer = true;
                }
            } elseif (isset($this->messageParameters['ext_lms']) && ($this->consumer->consumerName !== $this->messageParameters['ext_lms'])) {
                $this->consumer->consumerVersion = $this->messageParameters['ext_lms'];
                $doSaveConsumer = true;
            }
            if (isset($this->messageParameters['tool_consumer_instance_guid'])) {
                if (is_null($this->consumer->consumerGuid)) {
                    $this->consumer->consumerGuid = $this->messageParameters['tool_consumer_instance_guid'];
                    $doSaveConsumer = true;
                } elseif (!$this->consumer->protected) {
                    $doSaveConsumer = ($this->consumer->consumerGuid !== $this->messageParameters['tool_consumer_instance_guid']);
                    if ($doSaveConsumer) {
                        $this->consumer->consumerGuid = $this->messageParameters['tool_consumer_instance_guid'];
                    }
                }
            }
            if (isset($this->messageParameters['launch_presentation_css_url'])) {
                if ($this->consumer->cssPath !== $this->messageParameters['launch_presentation_css_url']) {
                    $this->consumer->cssPath = $this->messageParameters['launch_presentation_css_url'];
                    $doSaveConsumer = true;
                }
            } elseif (isset($this->messageParameters['ext_launch_presentation_css_url']) &&
                ($this->consumer->cssPath !== $this->messageParameters['ext_launch_presentation_css_url'])) {
                $this->consumer->cssPath = $this->messageParameters['ext_launch_presentation_css_url'];
                $doSaveConsumer = true;
            } elseif (!empty($this->consumer->cssPath)) {
                $this->consumer->cssPath = null;
                $doSaveConsumer = true;
            }
        }

// Persist changes to consumer
        if ($doSaveConsumer) {
            $this->consumer->save();
        }
        if ($this->ok && isset($this->context)) {
            $this->context->save();
        }
        if ($this->ok && isset($this->resourceLink)) {

// Check if a share arrangement is in place for this resource link
            $this->ok = $this->checkForShare();

// Persist changes to resource link
            $this->resourceLink->save();

// Save the user instance
            $this->userResult->setResourceLinkId($this->resourceLink->getRecordId());
            if (isset($this->messageParameters['lis_result_sourcedid'])) {
                if ($this->userResult->ltiResultSourcedId !== $this->messageParameters['lis_result_sourcedid']) {
                    $this->userResult->ltiResultSourcedId = $this->messageParameters['lis_result_sourcedid'];
                    $this->userResult->save();
                }
            } elseif (!empty($this->userResult->ltiResultSourcedId)) {
                $this->userResult->ltiResultSourcedId = '';
                $this->userResult->save();
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
                if (!is_null($shareKey->primaryConsumerKey) && !is_null($shareKey->primaryResourceLinkId)) {
// Update resource link with sharing primary resource link details
                    $key = $shareKey->primaryConsumerKey;
                    $id = $shareKey->primaryResourceLinkId;
                    $ok = ($key !== $this->consumer->getKey()) || ($id != $this->resourceLink->getId());
                    if ($ok) {
                        $this->resourceLink->primaryConsumerKey = $key;
                        $this->resourceLink->primaryResourceLinkId = $id;
                        $this->resourceLink->shareApproved = $shareKey->autoApprove;
                        $ok = $this->resourceLink->save();
                        if ($ok) {
                            $doSaveResourceLink = false;
                            $this->userResult->getResourceLink()->primaryConsumerKey = $key;
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
                    $ok = !is_null($key);
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
            $consumer = new ToolConsumer($key, $this->dataConnector);
            $ok = !is_null($consumer->created);
            if ($ok) {
                $resourceLink = ResourceLink::fromConsumer($consumer, $id);
                $ok = !is_null($resourceLink->created);
            }
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
