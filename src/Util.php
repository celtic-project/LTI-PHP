<?php
declare(strict_types=1);

namespace ceLTIc\LTI;

use ceLTIc\LTI\OAuth;
use ceLTIc\LTI\Enum\LogLevel;
use Psr\Log\LoggerInterface;

/**
 * Class to implement utility methods
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
final class Util
{

    /**
     * Prefix for standard JWT message claims.
     */
    public const JWT_CLAIM_PREFIX = 'https://purl.imsglobal.org/spec/lti';

    /**
     * Mapping for standard message types.
     */
    public const MESSAGE_TYPE_MAPPING = [
        'basic-lti-launch-request' => 'LtiResourceLinkRequest',
        'ContentItemSelectionRequest' => 'LtiDeepLinkingRequest',
        'ContentItemSelection' => 'LtiDeepLinkingResponse',
        'ContentItemUpdateRequest' => 'LtiDeepLinkingUpdateRequest'
    ];

    /**
     * Mapping for standard message parameters to JWT claim.
     */
    public const JWT_CLAIM_MAPPING = [
        'accept_types' => ['suffix' => 'dl', 'group' => 'deep_linking_settings', 'claim' => 'accept_types', 'isArray' => true],
        'accept_copy_advice' => ['suffix' => 'dl', 'group' => 'deep_linking_settings', 'claim' => 'copyAdvice', 'isBoolean' => true],
        'accept_media_types' => ['suffix' => 'dl', 'group' => 'deep_linking_settings', 'claim' => 'accept_media_types'],
        'accept_multiple' => ['suffix' => 'dl', 'group' => 'deep_linking_settings', 'claim' => 'accept_multiple', 'isBoolean' => true],
        'accept_presentation_document_targets' => ['suffix' => 'dl', 'group' => 'deep_linking_settings', 'claim' => 'accept_presentation_document_targets', 'isArray' => true],
        'accept_unsigned' => ['suffix' => 'dl', 'group' => 'deep_linking_settings', 'claim' => 'accept_unsigned', 'isBoolean' => true],
        'auto_create' => ['suffix' => 'dl', 'group' => 'deep_linking_settings', 'claim' => 'auto_create', 'isBoolean' => true],
        'can_confirm' => ['suffix' => 'dl', 'group' => 'deep_linking_settings', 'claim' => 'can_confirm'],
        'content_item_return_url' => ['suffix' => 'dl', 'group' => 'deep_linking_settings', 'claim' => 'deep_link_return_url'],
        'content_items' => ['suffix' => 'dl', 'group' => '', 'claim' => 'content_items', 'isContentItemSelection' => true],
        'data' => ['suffix' => 'dl', 'group' => 'deep_linking_settings', 'claim' => 'data'],
        'data.LtiDeepLinkingResponse' => ['suffix' => 'dl', 'group' => '', 'claim' => 'data'],
        'text' => ['suffix' => 'dl', 'group' => 'deep_linking_settings', 'claim' => 'text'],
        'title' => ['suffix' => 'dl', 'group' => 'deep_linking_settings', 'claim' => 'title'],
        'lti_msg' => ['suffix' => 'dl', 'group' => '', 'claim' => 'msg'],
        'lti_errormsg' => ['suffix' => 'dl', 'group' => '', 'claim' => 'errormsg'],
        'lti_log' => ['suffix' => 'dl', 'group' => '', 'claim' => 'log'],
        'lti_errorlog' => ['suffix' => 'dl', 'group' => '', 'claim' => 'errorlog'],
        'context_id' => ['suffix' => '', 'group' => 'context', 'claim' => 'id'],
        'context_label' => ['suffix' => '', 'group' => 'context', 'claim' => 'label'],
        'context_title' => ['suffix' => '', 'group' => 'context', 'claim' => 'title'],
        'context_type' => ['suffix' => '', 'group' => 'context', 'claim' => 'type', 'isArray' => true],
        'lis_course_offering_sourcedid' => ['suffix' => '', 'group' => 'lis', 'claim' => 'course_offering_sourcedid'],
        'lis_course_section_sourcedid' => ['suffix' => '', 'group' => 'lis', 'claim' => 'course_section_sourcedid'],
        'launch_presentation_css_url' => ['suffix' => '', 'group' => 'launch_presentation', 'claim' => 'css_url'],
        'launch_presentation_document_target' => ['suffix' => '', 'group' => 'launch_presentation', 'claim' => 'document_target'],
        'launch_presentation_height' => ['suffix' => '', 'group' => 'launch_presentation', 'claim' => 'height', 'isInteger' => true],
        'launch_presentation_locale' => ['suffix' => '', 'group' => 'launch_presentation', 'claim' => 'locale'],
        'launch_presentation_return_url' => ['suffix' => '', 'group' => 'launch_presentation', 'claim' => 'return_url'],
        'launch_presentation_width' => ['suffix' => '', 'group' => 'launch_presentation', 'claim' => 'width', 'isInteger' => true],
        'lis_person_contact_email_primary' => ['suffix' => '', 'group' => null, 'claim' => 'email'],
        'lis_person_name_family' => ['suffix' => '', 'group' => null, 'claim' => 'family_name'],
        'lis_person_name_full' => ['suffix' => '', 'group' => null, 'claim' => 'name'],
        'lis_person_name_given' => ['suffix' => '', 'group' => null, 'claim' => 'given_name'],
        'lis_person_name_middle' => ['suffix' => '', 'group' => null, 'claim' => 'middle_name'],
        'lis_person_sourcedid' => ['suffix' => '', 'group' => 'lis', 'claim' => 'person_sourcedid'],
        'user_id' => ['suffix' => '', 'group' => null, 'claim' => 'sub'],
        'user_image' => ['suffix' => '', 'group' => null, 'claim' => 'picture'],
        'roles' => ['suffix' => '', 'group' => '', 'claim' => 'roles', 'isArray' => true],
        'role_scope_mentor' => ['suffix' => '', 'group' => '', 'claim' => 'role_scope_mentor', 'isArray' => true],
        'platform_id' => ['suffix' => '', 'group' => null, 'claim' => 'iss'],
        'deployment_id' => ['suffix' => '', 'group' => '', 'claim' => 'deployment_id'],
        'oauth_nonce' => ['suffix' => '', 'group' => null, 'claim' => 'nonce'],
        'oauth_timestamp' => ['suffix' => '', 'group' => null, 'claim' => 'iat', 'isInteger' => true],
        'lti_message_type' => ['suffix' => '', 'group' => '', 'claim' => 'message_type'],
        'lti_version' => ['suffix' => '', 'group' => '', 'claim' => 'version'],
        'resource_link_description' => ['suffix' => '', 'group' => 'resource_link', 'claim' => 'description'],
        'resource_link_id' => ['suffix' => '', 'group' => 'resource_link', 'claim' => 'id'],
        'resource_link_title' => ['suffix' => '', 'group' => 'resource_link', 'claim' => 'title'],
        'target_link_uri' => ['suffix' => '', 'group' => '', 'claim' => 'target_link_uri'],
        'tool_consumer_info_product_family_code' => ['suffix' => '', 'group' => 'tool_platform', 'claim' => 'product_family_code'],
        'tool_consumer_info_version' => ['suffix' => '', 'group' => 'tool_platform', 'claim' => 'version'],
        'tool_consumer_instance_contact_email' => ['suffix' => '', 'group' => 'tool_platform', 'claim' => 'contact_email'],
        'tool_consumer_instance_description' => ['suffix' => '', 'group' => 'tool_platform', 'claim' => 'description'],
        'tool_consumer_instance_guid' => ['suffix' => '', 'group' => 'tool_platform', 'claim' => 'guid'],
        'tool_consumer_instance_name' => ['suffix' => '', 'group' => 'tool_platform', 'claim' => 'name'],
        'tool_consumer_instance_url' => ['suffix' => '', 'group' => 'tool_platform', 'claim' => 'url'],
        'for_user_contact_email_primary' => ['suffix' => '', 'group' => 'for_user', 'claim' => 'email'],
        'for_user_name_family' => ['suffix' => '', 'group' => 'for_user', 'claim' => 'family_name'],
        'for_user_name_full' => ['suffix' => '', 'group' => 'for_user', 'claim' => 'name'],
        'for_user_name_given' => ['suffix' => '', 'group' => 'for_user', 'claim' => 'given_name'],
        'for_user_name_middle' => ['suffix' => '', 'group' => 'for_user', 'claim' => 'middle_name'],
        'for_user_sourcedid' => ['suffix' => '', 'group' => 'for_user', 'claim' => 'person_sourcedid'],
        'for_user_id' => ['suffix' => '', 'group' => 'for_user', 'claim' => 'user_id'],
        'for_user_roles' => ['suffix' => '', 'group' => 'for_user', 'claim' => 'roles', 'isArray' => true],
        'tool_state' => ['suffix' => '', 'group' => 'tool', 'claim' => 'state'],
        'custom_context_memberships_v2_url' => ['suffix' => 'nrps', 'group' => 'namesroleservice', 'claim' => 'context_memberships_url'],
        'custom_nrps_versions' => ['suffix' => 'nrps', 'group' => 'namesroleservice', 'claim' => 'service_versions', 'isArray' => true],
        'custom_lineitems_url' => ['suffix' => 'ags', 'group' => 'endpoint', 'claim' => 'lineitems'],
        'custom_lineitem_url' => ['suffix' => 'ags', 'group' => 'endpoint', 'claim' => 'lineitem'],
        'custom_ags_scopes' => ['suffix' => 'ags', 'group' => 'endpoint', 'claim' => 'scope', 'isArray' => true],
        'custom_context_groups_url' => ['suffix' => 'gs', 'group' => 'groupsservice', 'claim' => 'context_groups_url'],
        'custom_context_group_sets_url' => ['suffix' => 'gs', 'group' => 'groupsservice', 'claim' => 'context_group_sets_url'],
        'custom_gs_scopes' => ['suffix' => 'gs', 'group' => 'groupsservice', 'claim' => 'scope', 'isArray' => true],
        'custom_gs_versions' => ['suffix' => 'gs', 'group' => 'groupsservice', 'claim' => 'service_versions', 'isArray' => true],
        'lis_outcome_service_url' => ['suffix' => 'bo', 'group' => 'basicoutcome', 'claim' => 'lis_outcome_service_url'],
        'lis_result_sourcedid' => ['suffix' => 'bo', 'group' => 'basicoutcome', 'claim' => 'lis_result_sourcedid'],
        'custom_ap_attempt_number' => ['suffix' => 'ap', 'group' => '', 'claim' => 'attempt_number', 'isInteger' => true],
        'custom_ap_start_assessment_url' => ['suffix' => 'ap', 'group' => '', 'claim' => 'start_assessment_url'],
        'custom_ap_session_data' => ['suffix' => 'ap', 'group' => '', 'claim' => 'session_data'],
        'custom_ap_acs_actions' => ['suffix' => 'ap', 'group' => 'acs', 'claim' => 'actions', 'isArray' => true],
        'custom_ap_acs_url' => ['suffix' => 'ap', 'group' => 'acs', 'claim' => 'assessment_control_url'],
        'custom_ap_proctoring_settings_data' => ['suffix' => 'ap', 'group' => 'proctoring_settings', 'claim' => 'data'],
        'custom_ap_email_verified' => ['suffix' => '', 'group' => null, 'claim' => 'email_verified', 'isBoolean' => true],
        'custom_ap_verified_user_given_name' => ['suffix' => 'ap', 'group' => 'verified_user', 'claim' => 'given_name'],
        'custom_ap_verified_user_middle_name' => ['suffix' => 'ap', 'group' => 'verified_user', 'claim' => 'middle_name'],
        'custom_ap_verified_user_family_name' => ['suffix' => 'ap', 'group' => 'verified_user', 'claim' => 'family_name'],
        'custom_ap_verified_user_full_name' => ['suffix' => 'ap', 'group' => 'verified_user', 'claim' => 'full_name'],
        'custom_ap_verified_user_image' => ['suffix' => 'ap', 'group' => 'verified_user', 'claim' => 'picture'],
        'custom_ap_end_assessment_return' => ['suffix' => 'ap', 'group' => '', 'claim' => 'end_assessment_return', 'isBoolean' => true],
        'custom_caliper_endpoint_url' => ['suffix' => 'ces', 'group' => 'caliper-endpoint-service', 'claim' => 'caliper_endpoint_url'],
        'custom_caliper_federated_session_id' => ['suffix' => 'ces', 'group' => 'caliper-endpoint-service', 'claim' => 'caliper_federated_session_id'],
        'custom_caliper_maximum_payload_size' => ['suffix' => 'ces', 'group' => 'caliper-endpoint-service', 'claim' => 'caliper_maximum_payload_size', 'isInteger' => true],
        'custom_caliper_supported_extensions' => ['suffix' => 'ces', 'group' => 'caliper-endpoint-service', 'claim' => 'caliper_supported_extensions', 'isObject' => true],
        'custom_caliper_supported_versions' => ['suffix' => 'ces', 'group' => 'caliper-endpoint-service', 'claim' => 'caliper_supported_versions', 'isArray' => true],
        'custom_caliper_scopes' => ['suffix' => 'ces', 'group' => 'caliper-endpoint-service', 'claim' => 'scopes', 'isArray' => true],
        'custom_linkcontentitems_url' => ['suffix' => '', 'group' => 'linkcontentservice', 'claim' => 'contentitems'],
        'custom_linkcontentitem_url' => ['suffix' => '', 'group' => 'linkcontentservice', 'claim' => 'contentitem'],
        'custom_linkcontent_version' => ['suffix' => '', 'group' => 'linkcontentservice', 'claim' => 'version', 'isArray' => true],
        'custom_linkcontent_scopes' => ['suffix' => '', 'group' => 'linkcontentservice', 'claim' => 'scopes', 'isArray' => true],
        'custom_linkcontent_types' => ['suffix' => '', 'group' => 'linkcontentservice', 'claim' => 'types', 'isArray' => true]
    ];

    /**
     * Name of test cookie.
     */
    public const TEST_COOKIE_NAME = 'celtic_lti_test_cookie';

    /**
     * List of supported message types and associated class methods.
     *
     * @var array $METHOD_NAMES
     */
    public static array $METHOD_NAMES = [
        'basic-lti-launch-request' => 'onLaunch',
        'ConfigureLaunchRequest' => 'onConfigure',
        'DashboardRequest' => 'onDashboard',
        'ContentItemSelectionRequest' => 'onContentItem',
        'ContentItemSelection' => 'onContentItem',
        'ContentItemUpdateRequest' => 'onContentItemUpdate',
        'LtiSubmissionReviewRequest' => 'onSubmissionReview',
        'ToolProxyRegistrationRequest' => 'onRegister',
        'LtiStartProctoring' => 'onLtiStartProctoring',
        'LtiStartAssessment' => 'onLtiStartAssessment',
        'LtiEndAssessment' => 'onLtiEndAssessment'
    ];

    /**
     * GET and POST request parameters
     *
     * @var array|null $requestParameters
     */
    public static ?array $requestParameters = null;

    /**
     * Value of library version.
     *
     * @var string $version
     */
    public static string $version = '5.3.0';

    /**
     * Value of User-Agent header of HTTP requests.
     *
     * @var string $userAgentHeaderValue
     */
    public static string $userAgentHeaderValue = 'LTI-PHP/{VERSION} (from ceLTIc Project)';

    /**
     * Current logging level.
     *
     * @var LogLevel $logLevel
     */
    public static LogLevel $logLevel = LogLevel::None;

    /**
     * Whether full compliance with the LTI specification is required.
     *
     * @var bool $strictMode
     */
    public static bool $strictMode = false;

    /**
     * Whether the automatic saving of fetched valid public keys should be disabled.
     *
     * @var bool $disableFetchedPublicKeysSave
     */
    public static bool $disableFetchedPublicKeysSave = false;

    /**
     * Delay (in seconds) before a manual button is displayed in case a browser is blocking a form submission.
     *
     * @var int $formSubmissionTimeout
     */
    public static int $formSubmissionTimeout = 2;

    /**
     * Key to use when encrypting and decrypting values.
     *
     * @var string|null $encryptionKey
     */
    public static ?string $encryptionKey = null;

    /**
     * The cryptography algorithm used by the encryption/decryption methods.
     */
    private const CIPHER_METHOD = 'aes-256-gcm';

    /**
     * The client used to handle log messages.
     *
     * @var LoggerInterface $loggerClient
     */
    private static LoggerInterface $loggerClient;

    /**
     * Messages relating to service request.
     *
     * @var array $messages
     */
    private static array $messages = [true => [], false => []];

    /**
     * Check whether the request received could be an LTI message.
     *
     * @return bool
     */
    public static function isLtiMessage(): bool
    {
        $isLti = ($_SERVER['REQUEST_METHOD'] === 'POST') &&
            (!empty($_POST['lti_message_type']) || !empty($_POST['id_token']) || !empty($_POST['JWT']) ||
            !empty($_POST['iss']));
        if (!$isLti) {
            $isLti = ($_SERVER['REQUEST_METHOD'] === 'GET') && (!empty($_GET['iss']) || !empty($_GET['openid_configuration']));
        }

        return $isLti;
    }

    /**
     * Return GET and POST request parameters (POST parameters take precedence).
     *
     * @return array
     */
    public static function getRequestParameters(): array
    {
        if (is_null(self::$requestParameters)) {
            self::$requestParameters = array_merge($_GET, $_POST);
        }

        return self::$requestParameters;
    }

    /**
     * Log an error message.
     *
     * @param string $message   Message to be logged
     * @param bool $showSource  True if the name and line number of the current file are to be included
     *
     * @return void
     */
    public static function logError(string $message, bool $showSource = true): void
    {
        if (self::$logLevel->logError()) {
            self::logMessage($message, LogLevel::Error, $showSource);
        }
    }

    /**
     * Log an information message.
     *
     * @param string $message   Message to be logged
     * @param bool $showSource  True if the name and line number of the current file are to be included
     *
     * @return void
     */
    public static function logInfo(string $message, bool $showSource = false): void
    {
        if (self::$logLevel->logInfo()) {
            self::logMessage($message, LogLevel::Info, $showSource);
        }
    }

    /**
     * Log a debug message.
     *
     * @param string $message   Message to be logged
     * @param bool $showSource  True if the name and line number of the current file are to be included
     *
     * @return void
     */
    public static function logDebug(string $message, bool $showSource = false): void
    {
        if (self::$logLevel->logDebug()) {
            self::logMessage($message, LogLevel::Debug, $showSource);
        }
    }

    /**
     * Log a request received.
     *
     * @param bool $debugLevel  True if the request details should be logged at the debug level (optional, default is false for information level)
     *
     * @return void
     */
    public static function logRequest(bool $debugLevel = false): void
    {
        if (self::$logLevel->logDebug() || (!$debugLevel && self::$logLevel->logInfo())) {
            $message = '';
            $headers = OAuth\OAuthUtil::get_headers();
            $body = file_get_contents(OAuth\OAuthRequest::$POST_INPUT);
            if (!empty($body)) {
                if (isset($headers['Content-Type']) && (trim(explode(';', $headers['Content-Type'])[0]) == 'application/x-www-form-urlencoded')) {
                    $params = OAuth\OAuthUtil::parse_parameters($body);
                    $message .= " with body parameters of:\n" . var_export($params, true);
                } else {
                    $message .= ' with a body of:';
                    if (!preg_match('/[^\s\x20-\x7e]/', $body)) {
                        $message .= "\n" . var_export($body, true);
                    } else {
                        $message .= ' <Body contains binary data>';
                    }
                }
            }
            if (!empty($headers)) {
                if (!empty($message)) {
                    $message .= "\nand ";
                } else {
                    $message .= " with ";
                }
                $message .= "headers of:\n" . var_export($headers, true);
            }
            $message = "{$_SERVER['REQUEST_METHOD']} request received for '{$_SERVER['REQUEST_URI']}'{$message}";
            if (!$debugLevel) {
                self::logInfo($message);
            } else {
                self::logDebug($message);
            }
        }
    }

    /**
     * Log a form submission.
     *
     * @param string $url       URL to which the form should be submitted
     * @param array $params     Array of form parameters
     * @param string $method    HTTP Method used to submit form (optional, default is POST)
     * @param bool $debugLevel  True if the form details should always be logged (optional, default is false to use current log level)
     *
     * @return void
     */
    public static function logForm(string $url, array $params, string $method = 'POST', bool $debugLevel = false): void
    {
        if ($debugLevel || self::$logLevel->logInfo()) {
            $message = "Form submitted using {$method} to '{$url}'";
            if (!empty($params)) {
                $message .= " with parameters of:\n" . var_export($params, true);
            } else {
                $message .= " with no parameters";
            }
            if ($debugLevel || self::$logLevel->logDebug()) {
                self::logDebug($message);
            } else {
                self::logInfo($message);
            }
        }
    }

    /**
     * Log an error message irrespective of the logging level.
     *
     * @deprecated Use logMessage() instead
     *
     * @param string $message   Message to be logged
     * @param bool $showSource  True if the name and line number of the current file are to be included
     *
     * @return void
     */
    public static function log(string $message, bool $showSource = false): void
    {
        self::logDebug('Method ceLTIc\LTI\Util::log has been deprecated; please use ceLTIc\LTI\Util::logMessage instead.', true);
        self::logMessage($message, LogLevel::None, $showSource);
    }

    /**
     * Log an error message irrespective of the logging level.
     *
     * @param string $message   Message to be logged
     * @param LogLevel $type    Type of message to be logged (optional, default is none)
     * @param bool $showSource  True if the name and line number of the current file are to be included
     *
     * @return void
     */
    public static function logMessage(string $message, LogLevel $type = LogLevel::None, bool $showSource = false): void
    {
        $source = '';
        if ($showSource) {
            $backtraces = debug_backtrace();
            foreach ($backtraces as $backtrace) {
                if (isset($backtrace['file'])) {
                    $source .= PHP_EOL . "  {$backtrace['file']}";
                    if (isset($backtrace['line'])) {
                        $source .= " line {$backtrace['line']}";
                    }
                }
            }
            if (!empty($source)) {
                $source = PHP_EOL . "See: {$source}";
            }
        }
        $message = $message . $source;
        $loggerClient = Util::getLoggerClient();
        if (empty($loggerClient)) {
            $prefix = match ($type) {
                LogLevel::Error => '[ERROR] ',
                LogLevel::Info => '[INFO] ',
                LogLevel::Debug => '[DEBUG] ',
                default => ''
            };
            error_log($prefix . $message);
        } else {
            $context = ['ip' => $_SERVER['REMOTE_ADDR']];
            switch ($type) {
                case LogLevel::Error:
                    $loggerClient->error($message, $context);
                    break;
                case LogLevel::Info:
                    $loggerClient->info($message, $context);
                    break;
                case LogLevel::Debug:
                    $loggerClient->debug($message, $context);
                    break;
                default:
                    $loggerClient->notice($message, $context);
                    break;
            }
        }
    }

    /**
     * Set the Logger client to use for logging messages.
     *
     * @param LoggerInterface|null $loggerClient  Logger client (use null to reset to default)
     *
     * @return void
     */
    public static function setLoggerClient(?LoggerInterface $loggerClient): void
    {
        Util::$loggerClient = $loggerClient;
        if (!empty($loggerClient)) {
            Util::logDebug('LoggerClient set to \'' . get_class(self::$loggerClient) . '\'');
        } else {
            Util::logDebug('LoggerClient set to use error_log');
        }
    }

    /**
     * Get the Logger client to use for logging messages.
     *
     * @return LoggerInterface|null  Logger client
     */
    public static function getLoggerClient(): ?LoggerInterface
    {
        if (!empty(Util::$loggerClient)) {
            return Util::$loggerClient;
        } else {
            return null;
        }
    }

    /**
     * Set an error or warning message.
     *
     * @param bool $isError    True if the message represents an error
     * @param string $message  Message
     *
     * @return void
     */
    public static function setMessage(bool $isError, string $message): void
    {
        if (!in_array($message, self::$messages[$isError])) {
            self::$messages[$isError][] = $message;
        }
    }

    /**
     * Get the system error or warning messages.
     *
     * @param bool $isError  True if error messages are to be returned
     *
     * @return array  Array of messages
     */
    public static function getMessages(bool $isError): array
    {
        return self::$messages[$isError];
    }

    /**
     * Generate a web page containing an auto-submitted form of parameters.
     *
     * @param string $url         URL to which the form should be submitted
     * @param array $params       Array of form parameters
     * @param string $target      Name of target (optional)
     * @param string $javascript  Javascript to be inserted (optional, default is to just auto-submit form)
     *
     * @return string
     */
    public static function sendForm(string $url, array $params, string $target = '', string $javascript = ''): string
    {
        $timeout = static::$formSubmissionTimeout;
        if (empty($javascript)) {
            $javascript = <<< EOD
function doUnblock() {
  var el = document.getElementById('id_blocked');
  el.style.display = 'block';
}
function doOnLoad() {
  if ((document.forms[0].target === '_blank') && (window.top === window.self)) {
    document.forms[0].target = '';
  }
  window.setTimeout(doUnblock, {$timeout}000);
  document.forms[0].submit();
}
window.onload=doOnLoad;
EOD;
        }
        self::logForm($url, $params, 'POST');
        $page = <<< EOD
<!DOCTYPE html>
<head>
<title>1EdTech LTI message</title>
<script type="text/javascript">
{$javascript}
</script>
</head>
<body>
  <form action="{$url}" method="post" target="{$target}" encType="application/x-www-form-urlencoded">
    <p id="id_blocked" style="display: none; color: red; font-weight: bold;">
      Your browser may be blocking this request; try clicking the button below.<br><br>
      <input type="submit" value="Continue">
    </p>

EOD;
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $key = htmlentities($key, ENT_COMPAT | ENT_HTML401, 'UTF-8');
                if (!is_array($value)) {
                    $value = htmlentities($value, ENT_COMPAT | ENT_HTML401, 'UTF-8');
                    $page .= <<< EOD
    <input type="hidden" name="{$key}" id="id_{$key}" value="{$value}">

EOD;
                } else {
                    foreach ($value as $element) {
                        $element = htmlentities($element, ENT_COMPAT | ENT_HTML401, 'UTF-8');
                        $page .= <<< EOD
    <input type="hidden" name="{$key}" value="{$element}">

EOD;
                    }
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

    /**
     * Send a service response.
     *
     * @param string $body           Body of response (default is empty)
     * @param string $contentType    Content type of response (default is empty)
     * @param int $statusCode        Status code of response (default is 200)
     * @param string $statusMessage  Status message of response (default is 'OK')
     *
     * @return void
     */
    public static function sendResponse(string $body = '', string $contentType = '', int $statusCode = 200,
        string $statusMessage = 'OK'): void
    {
        $status = strval($statusCode);
        $response = "{$_SERVER['SERVER_PROTOCOL']} {$status} {$statusMessage}";
        header($response);
        $response .= "\nDate: " . gmdate(DATE_RFC2822);
        $response .= "\nServer: " . $_SERVER['SERVER_SOFTWARE'];
        if (!empty($body)) {
            if (!empty($contentType)) {
                header("Content-Type: {$contentType}");
            }
            header("Content-Length: " . strval(strlen($body)));
        }
        foreach (headers_list() as $header) {
            $response .= "\n{$header}";
        }
        if (!empty($body)) {
            $response .= "\n\n";
            if (!preg_match('/[^\s\x20-\x7e]/', $body)) {
                $response .= $body;
            } else {
                $response .= '<Body contains binary data>';
            }
            echo $body;
        }
        self::logDebug('Response sent: ' . $response);
    }

    /**
     * Add query parameters to a URL.
     *
     * @param string $url    URL to which the form should be submitted
     * @param array $params  Array of form parameters
     *
     * @return string
     */
    public static function addQueryParameters(string $url, array $params): string
    {
        if (!str_contains($url, '?')) {
            $url .= '?';
            $sep = '';
        } else {
            $sep = '&';
        }
        foreach ($params as $key => $value) {
            $key = self::urlEncode($key);
            if (!is_array($value)) {
                $value = self::urlEncode($value);
                $url .= "{$sep}{$key}={$value}";
                $sep = '&';
            } else {
                foreach ($value as $element) {
                    $element = self::urlEncode($element);
                    $url .= "{$sep}{$key}={$element}";
                    $sep = '&';
                }
            }
        }

        return $url;
    }

    /**
     * Redirect to a URL with query parameters.
     *
     * @param string $url        URL to which the form should be submitted
     * @param array $params      Array of form parameters (optional, default is none)
     * @param bool $disableExit  True if the exit statement should not be called
     *
     * @return void
     */
    public static function redirect(string $url, array $params = [], bool $disableExit = false): void
    {
        if (!empty($params)) {
            $url = Util::addQueryParameters($url, $params);
        }
        header("Location: {$url}");
        if (!$disableExit) {
            exit;
        }
    }

    /**
     * Set or delete a test cookie.
     *
     * @param bool $delete  True if the cookie is to be deleted (optional, default is false)
     *
     * @return void
     */
    public static function setTestCookie(bool $delete = false): void
    {
        if (!$delete || isset($_COOKIE[self::TEST_COOKIE_NAME])) {
            $oauthRequest = OAuth\OAuthRequest::from_request();
            $url = $oauthRequest->get_normalized_http_url();
            $secure = (parse_url($url, PHP_URL_SCHEME) === 'https');
            $path = parse_url($url, PHP_URL_PATH);
            if (empty($path)) {
                $path = '/';
            } elseif (str_ends_with($path, '/')) {
                $path = substr($path, 0, -1);
            }
            if (!$delete) {
                $expires = 0;
            } else {
                $expires = time() - 3600;
            }
            if ((PHP_MAJOR_VERSION > 7) || ((PHP_MAJOR_VERSION >= 7) && (PHP_MINOR_VERSION >= 3))) {  // PHP 7.3 or later?
                setcookie(self::TEST_COOKIE_NAME, 'LTI cookie check',
                    [
                        'expires' => $expires,
                        'path' => $path,
                        'domain' => $_SERVER['HTTP_HOST'],
                        'secure' => $secure,
                        'httponly' => true,
                        'SameSite' => 'None'
                    ]
                );
            } else {
                setcookie(self::TEST_COOKIE_NAME, 'LTI cookie check', $expires, $path, $_SERVER['HTTP_HOST'], $secure);
            }
        }
    }

    /**
     * Generate a random string.
     *
     * The generated string will only comprise letters (upper- and lower-case) and digits.
     *
     * @param int $length  Length of string to be generated (optional, default is 8 characters)
     *
     * @return string  Random string
     */
    public static function getRandomString(int $length = 8): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

        $value = '';
        $charsLength = strlen($chars) - 1;

        for ($i = 1; $i <= $length; $i++) {
            $value .= $chars[rand(0, $charsLength)];
        }

        return $value;
    }

    /**
     * Strip HTML tags from a string.
     *
     * @param string $html  HTML string to be stripped
     *
     * @return string
     */
    public static function stripHtml(string $html): string
    {
        $html = strip_tags($html);
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML401);

        return $html;
    }

    /**
     * URL encode a value.
     *
     * @param string|null $val   The value to be encoded
     *
     * @return string
     */
    public static function urlEncode(?string $val): string
    {
        if (!is_string($val)) {
            $val = strval($val);
        }

        return urlencode($val);
    }

    /**
     * Convert a value to a string.
     *
     * @param mixed $val            The value to be converted
     * @param string|null $default  Value to return when a conversion is not possible (optional, default is an empty string)
     *
     * @return string
     */
    public static function valToString(mixed $val, ?string $default = ''): string
    {
        if (!is_string($val)) {
            if (is_bool($val)) {
                $val = ($val) ? 'true' : 'false';
            } elseif (is_scalar($val)) {
                $val = strval($val);
            } else {
                $val = $default;
            }
        }

        return $val;
    }

    /**
     * Convert a value to a numeric.
     *
     * @param mixed $val   The value to be converted
     *
     * @return int|float|null
     */
    public static function valToNumber(mixed $val): int|float|null
    {
        if (!is_int($val) && !is_float($val)) {
            if (!is_numeric($val)) {
                $val = null;
            } elseif (!str_contains($val, '.')) {
                $val = intval($val);
            } else {
                $val = floatval($val);
            }
        }

        return $val;
    }

    /**
     * Convert a value to a boolean.
     *
     * @param mixed $val   The value to be converted
     *
     * @return bool
     */
    public static function valToBoolean(mixed $val): bool
    {
        if (!is_bool($val)) {
            if (($val === 'true' || $val === 1)) {
                $val = true;
            } else {
                $val = false;
            }
        }

        return $val;
    }

    /**
     * Get the named object element from object.
     *
     * @param object $obj         Object containing the element
     * @param string $fullname    Name of element
     * @param bool $required      True if the element must be present
     * @param bool $stringValues  True if the values must be strings
     *
     * @return object|null  Value of element (or null if not found)
     */
    public static function checkObject(object $obj, string $fullname, bool $required = false, bool $stringValues = false): ?object
    {
        $element = null;
        $name = $fullname;
        $pos = strrpos($name, '/');
        if ($pos !== false) {
            $name = substr($name, $pos + 1);
        }
        if (property_exists($obj, $name)) {
            if (is_object($obj->{$name})) {
                $element = $obj->{$name};
                if ($stringValues) {
                    foreach (get_object_vars($element) as $elementName => $elementValue) {
                        if (!is_string($elementValue)) {
                            if (!self::$strictMode) {
                                self::setMessage(false,
                                    "Properties of the {$fullname} element should have a string value (" . gettype($elementValue) . ' found)');
                                $element->{$elementName} = self::valToString($elementValue);
                            } else {
                                $element = null;
                                self::setMessage(true,
                                    "Properties of the {$fullname} element must have a string value (" . gettype($elementValue) . ' found)');
                                break;
                            }
                        }
                    }
                }
            } else {
                self::setMessage(false, "The '{$fullname}' element must be an object (" . gettype($obj->{$name}) . ' found)');
            }
        } elseif ($required) {
            self::setMessage(true, "The '{$fullname}' element is missing");
        }

        return $element;
    }

    /**
     * Get the named array element from object.
     *
     * @param object $obj          Object containing the element
     * @param string $fullname     Name of element
     * @param bool $required       True if the element must be present
     * @param bool $notEmpty       True if the element must not have an empty value
     * @param array|null $default  Value to return when a conversion is not possible (optional, default is an empty array)
     *
     * @return array|null  Value of element (or default value if not found)
     */
    public static function checkArray(object $obj, string $fullname, bool $required = false, bool $notEmpty = false,
        ?array $default = []): ?array
    {
        $arr = $default;
        $name = $fullname;
        $pos = strrpos($name, '/');
        if ($pos !== false) {
            $name = substr($name, $pos + 1);
        }
        if (property_exists($obj, $name)) {
            if (is_array($obj->{$name})) {
                $arr = $obj->{$name};
                if ($notEmpty && empty($arr)) {
                    self::setMessage(true, "The '{$fullname}' element must not be empty");
                }
            } elseif (self::$strictMode) {
                self::setMessage(false, "The '{$fullname}' element must have an array value (" . gettype($obj->{$name}) . ' found)');
            } else {
                self::setMessage(false,
                    "The '{$fullname}' element should have an array value (" . gettype($obj->{$name}) . ' found)');
                if (is_object($obj->{$name})) {
                    $arr = (array) $obj->{$name};
                }
            }
        } elseif ($required) {
            self::setMessage(true, "The '{$fullname}' element is missing");
        }

        return $arr;
    }

    /**
     * Get the named string element from object.
     *
     * @param object $obj               Object containing the element
     * @param string $fullname          Name of element (may include a path)
     * @param bool $required            True if the element must be present
     * @param bool|null $notEmpty       True if the element must not have an empty value (use null to issue a warning message when it does)
     * @param string $fixedValue        Required value of element (empty string if none)
     * @param bool $overrideStrictMode  Ignore strict mode setting
     * @param string|null $default      Value to return when a conversion is not possible (optional, default is an empty string)
     *
     * @return string  Value of element (or default value if not found or valid)
     */
    public static function checkString(object $obj, string $fullname, bool $required = false, ?bool $notEmpty = false,
        string|array $fixedValue = '', bool $overrideStrictMode = false, ?string $default = ''): ?string
    {
        $value = $default;
        $name = $fullname;
        $pos = strrpos($name, '/');
        if ($pos !== false) {
            $name = substr($name, $pos + 1);
        }
        if (property_exists($obj, $name)) {
            if (is_string($obj->{$name})) {
                if (!empty($fixedValue) && is_string($fixedValue) && ($obj->{$name} !== $fixedValue)) {
                    if (self::$strictMode || $overrideStrictMode) {
                        self::setMessage(true,
                            "The '{$fullname}' element must have a value of '{$fixedValue}' ('{$obj->{$name}}' found)");
                    } else {
                        self::setMessage(false,
                            "The '{$fullname}' element should have a value of '{$fixedValue}' ('{$obj->{$name}}' found)");
                    }
                } elseif (!empty($fixedValue) && is_array($fixedValue) && !in_array($obj->{$name}, $fixedValue)) {
                    self::setMessage(self::$strictMode || $overrideStrictMode,
                        "Value of the '{$fullname}' element not recognised ('{$obj->{$name}}' found)");
                } elseif ($notEmpty && empty($obj->{$name})) {
                    if (self::$strictMode || $overrideStrictMode) {
                        self::setMessage(true, "The '{$fullname}' element must not be empty");
                    } else {
                        self::setMessage(false, "The '{$fullname}' element should not be empty");
                    }
                } else {
                    $value = $obj->{$name};
                    if (is_null($notEmpty) && empty($value)) {
                        self::setMessage(false, "The '{$fullname}' element is empty");
                    }
                }
            } elseif ($required || !is_null($obj->{$name})) {
                if (self::$strictMode || $overrideStrictMode) {
                    self::setMessage(true,
                        "The '{$fullname}' element must have a string value (" . gettype($obj->{$name}) . ' found)');
                } else {
                    self::setMessage(false,
                        "The '{$fullname}' element should have a string value (" . gettype($obj->{$name}) . ' found)');
                    $value = self::valToString($obj->{$name}, $default);
                }
            } else {
                $value = $default;
                if (is_null($notEmpty) && empty($value)) {
                    self::setMessage(false, "The '{$fullname}' element is empty");
                }
            }
        } elseif ($required) {
            self::setMessage(true, "The '{$fullname}' element is missing");
        }

        return $value;
    }

    /**
     * Get the named filly-qualified URL element from object.
     *
     * @param object $obj               Object containing the element
     * @param string $fullname          Name of element (may include a path)
     * @param bool $required            True if the element must be present
     * @param bool $allowEmpty          True if the element can be empty
     *
     * @return string  Value of element (or default value if not found or valid)
     */
    public static function checkUrl(object $obj, string $fullname, bool $required = false, bool $allowEmpty = false): ?string
    {
        $value = self::checkString($obj, $fullname, $required);
        if (is_string($value)) {
            if (empty($value)) {
                if (!$allowEmpty) {
                    $value = null;
                    self::setMessage(true, "The '{$fullname}' element cannot be empty");
                }
            } elseif (!str_starts_with($value, 'http://') && !str_starts_with($value, 'https://')) {
                $value = null;
                self::setMessage(true, "The '{$fullname}' element must be a fully-qualified URL");
            }
        }

        return $value;
    }

    /**
     * Get the named number element from object.
     *
     * @param object $obj               Object containing the element
     * @param string $fullname          Name of element (may include a path)
     * @param bool $required            True if the element must be present
     * @param int|false $minimum        Minimum value (or false is none)
     * @param bool $minimumExclusive    True if value must exceed the minimum
     * @param bool $overrideStrictMode  Ignore strict mode setting
     *
     * @return int|float|null  Value of element (or null if not found or valid)
     */
    public static function checkNumber(object $obj, string $fullname, bool $required = false, int|false $minimum = false,
        bool $minimumExclusive = false, bool $overrideStrictMode = false): int|float|null
    {
        $value = null;
        $name = $fullname;
        $pos = strrpos($name, '/');
        if ($pos !== false) {
            $name = substr($name, $pos + 1);
        }
        if (property_exists($obj, $name)) {
            $num = self::valToNumber($obj->{$name});
            if (!is_null($num)) {
                if (($minimum !== false) && !$minimumExclusive && ($num < $minimum)) {
                    self::setMessage(true, "The '{$fullname}' element must have a numeric value of at least {$minimum}");
                } elseif (($minimum !== false) && $minimumExclusive && ($num <= $minimum)) {
                    self::setMessage(true, "The '{$fullname}' element must have a numeric value greater than {$minimum}");
                } else {
                    $value = $num;
                }
            }
            if (is_null($num) || (!is_null($value) && ($obj->{$name} !== $value))) {
                if (($required && is_null($value)) || self::$strictMode || $overrideStrictMode) {
                    self::setMessage(true,
                        "The '{$fullname}' element must have a numeric value (" . gettype($obj->{$name}) . ' found)');
                    $value = null;
                } else {
                    self::setMessage(false,
                        "The '{$fullname}' element should have a numeric value (" . gettype($obj->{$name}) . ' found)');
                }
            }
        } elseif ($required) {
            self::setMessage(true, "The '{$fullname}' element is missing");
        }

        return $value;
    }

    /**
     * Get the named integer element from object.
     *
     * @param object $obj               Object containing the element
     * @param string $fullname          Name of element (may include a path)
     * @param bool $required            True if the element must be present
     * @param int|false $minimum        Minimum value (or false is none)
     * @param bool $minimumExclusive    True if value must exceed the minimum
     * @param bool $overrideStrictMode  Ignore strict mode setting
     *
     * @return int|null  Value of element (or null if not found or valid)
     */
    public static function checkInteger(object $obj, string $fullname, bool $required = false, int|false $minimum = false,
        bool $minimumExclusive = false, bool $overrideStrictMode = false): int|null
    {
        $value = self::checkNumber($obj, $fullname, $required, $minimum, $minimumExclusive, $overrideStrictMode);
        if (is_float($value)) {
            if (self::$strictMode) {
                self::setMessage(true, "The '{$fullname}' element must have an integer value");
                $value = null;
            } else {
                self::setMessage(false, "The '{$fullname}' element should have an integer value");
                $value = intval($value);
            }
        }

        return $value;
    }

    /**
     * Get the named boolean element from object.
     *
     * @param object $obj           Object containing the element
     * @param string $fullname      Name of element (may include a path)
     * @param bool $required        True if the element must be present
     * @param string|null $default  Value to return when a conversion is not possible (optional, default is an empty string)
     *
     * @return bool|null  Value of element (or null if not found or valid)
     */
    public static function checkBoolean(object $obj, string $fullname, bool $required = false, ?bool $default = null): ?bool
    {
        $value = $default;
        $name = $fullname;
        $pos = strrpos($name, '/');
        if ($pos !== false) {
            $name = substr($name, $pos + 1);
        }
        if (property_exists($obj, $name)) {
            if (is_bool($obj->{$name})) {
                $value = $obj->{$name};
            } elseif (!self::$strictMode) {
                self::setMessage(false,
                    "The '{$fullname}' element should have a boolean value (" . gettype($obj->{$name}) . ' found)');
                $value = self::valToBoolean($obj->{$name});
            } else {
                self::setMessage(true, "The '{$fullname}' element must have a boolean value (" . gettype($obj->{$name}) . ' found)');
            }
        } elseif ($required) {
            self::setMessage(true, "The '{$fullname}' element is missing");
        }

        return $value;
    }

    /**
     * Get the named number element from object.
     *
     * @param object $obj       Object containing the element
     * @param string $fullname  Name of element (may include a path)
     * @param bool $required    True if the element must be present
     *
     * @return int  Value of element (or 0 if not found or valid)
     */
    public static function checkDateTime(object $obj, string $fullname, bool $required = false): int
    {
        $value = 0;
        $name = $fullname;
        $pos = strrpos($name, '/');
        if ($pos !== false) {
            $name = substr($name, $pos + 1);
        }
        if (property_exists($obj, $name)) {
            if (is_string($obj->{$name})) {
                $value = strtotime($obj->{$name});
                if ($value === false) {
                    self::setMessage(true, "The '{$fullname}' element must have a datetime value");
                    $value = 0;
                }
            } else {
                self::setMessage(true, "The '{$fullname}' element must have a string value (" . gettype($obj->{$name}) . ' found)');
            }
        } elseif ($required) {
            self::setMessage(true, "The '{$fullname}' element is missing");
        }

        return $value;
    }

    /**
     * Decode a JSON string.
     *
     * @param string|null $str   The JSON string to be decoded
     * @param bool $associative  True to return JSON objects as associative arrays
     *
     * @return mixed
     */
    public static function jsonDecode(?string $str, bool $associative = false): object|array|null
    {
        if (!empty($str)) {
            $json = \json_decode($str, $associative);
        } else {
            $json = null;
        }

        return $json;
    }

    /**
     * Clone an object and any objects it contains.
     *
     * @param object $obj  Object to be cloned
     *
     * @return object
     */
    public static function cloneObject(object $obj): object
    {
        $clone = clone $obj;
        $objVars = get_object_vars($clone);
        foreach ($objVars as $attrName => $attrValue) {
            if (is_object($clone->$attrName)) {
                $clone->$attrName = self::cloneObject($clone->$attrName);
            } elseif (is_array($clone->$attrName)) {
                foreach ($clone->$attrName as &$attrArrayValue) {
                    if (is_object($attrArrayValue)) {
                        $attrArrayValue = self::cloneObject($attrArrayValue);
                    }
                    unset($attrArrayValue);
                }
            }
        }

        return $clone;
    }

    /**
     * Check whether the application instance supports the encryption/decryption methods.
     *
     * @return bool  True if encryption is supported by this application instance
     */
    public static function canEncrypt(): bool
    {
        return !empty(self::$encryptionKey) && function_exists('openssl_encrypt') &&
            in_array(self::CIPHER_METHOD, openssl_get_cipher_methods());
    }

    /**
     * Encrypt a string value.
     *
     * @param string|null $value  String to be encrypted
     * @param int $maximumLength  Maximum length allowed for encrypted string (optional, default is 0 for no restriction)
     *
     * @return string|null  Encrypted string, or the original value if encryption was not possible
     */
    public static function encrypt(?string $value, int $maximumLength = 0): ?string
    {
        if (!empty($value) && self::canEncrypt()) {
            $ivLength = openssl_cipher_iv_length(self::CIPHER_METHOD);
            $iv = openssl_random_pseudo_bytes($ivLength);
            $encryptedValue = openssl_encrypt($value, self::CIPHER_METHOD, self::$encryptionKey, 0, $iv, $tag);
            if ($encryptedValue !== false) {
                $encryptedValue = base64_encode($iv) . ':' . base64_encode($tag) . ':' . $encryptedValue;
                if (($maximumLength <= 0) || (strlen($encryptedValue) <= $maximumLength)) {
                    $value = $encryptedValue;
                }
            }
        }

        return $value;
    }

    /**
     * Decrypt an encrypted string value.
     *
     * @param string|null $value   String to be decrypted
     *
     * @return string|null  Decrypted string, or the unchanged value if decryption was not possible
     */
    public static function decrypt(?string $value): ?string
    {
        if (!empty($value) && self::canEncrypt()) {
            $parts = explode(':', $value, 3);
            if (count($parts) === 3) {
                $iv = base64_decode($parts[0], true);
                $tag = base64_decode($parts[1], true);
                $encryptedValue = $parts[2];
                if (!empty($iv) && !empty($tag) && !empty($encryptedValue)) {
                    $decryptedValue = openssl_decrypt($encryptedValue, self::CIPHER_METHOD, self::$encryptionKey, 0, $iv, $tag);
                    if ($decryptedValue !== false) {
                        $value = $decryptedValue;
                    }
                }
            }
        }

        return $value;
    }

}
