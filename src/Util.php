<?php
declare(strict_types=1);

namespace ceLTIc\LTI;

use ceLTIc\LTI\OAuth;
use ceLTIc\LTI\Enum\LogLevel;

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
        'content_items' => ['suffix' => 'dl', 'group' => '', 'claim' => 'content_items', 'isObject' => true],
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
        'custom_ap_end_assessment_return' => ['suffix' => 'ap', 'group' => '', 'claim' => 'end_assessment_return', 'isBoolean' => true]
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
     * Current logging level.
     *
     * @var LogLevel $logLevel
     */
    public static LogLevel $logLevel = LogLevel::None;

    /**
     * Delay (in seconds) before a manual button is displayed in case a browser is blocking a form submission.
     *
     * @var int $formSubmissionTimeout
     */
    public static int $formSubmissionTimeout = 2;

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
     */
    public static function logError(string $message, bool $showSource = true): void
    {
        if (self::$logLevel->logError()) {
            self::log("[ERROR] {$message}", $showSource);
        }
    }

    /**
     * Log an information message.
     *
     * @param string $message   Message to be logged
     * @param bool $showSource  True if the name and line number of the current file are to be included
     */
    public static function logInfo(string $message, bool $showSource = false): void
    {
        if (self::$logLevel->logInfo()) {
            self::log("[INFO] {$message}", $showSource);
        }
    }

    /**
     * Log a debug message.
     *
     * @param string $message   Message to be logged
     * @param bool $showSource  True if the name and line number of the current file are to be included
     */
    public static function logDebug(string $message, bool $showSource = false): void
    {
        if (self::$logLevel->logDebug()) {
            self::log("[DEBUG] {$message}", $showSource);
        }
    }

    /**
     * Log a request received.
     *
     * @param bool $debugLevel  True if the request details should be logged at the debug level (optional, default is false for information level)
     */
    public static function logRequest(bool $debugLevel = false): void
    {
        if (self::$logLevel->logDebug() || (!$debugLevel && self::$logLevel->logInfo())) {
            $message = "{$_SERVER['REQUEST_METHOD']} request received for '{$_SERVER['REQUEST_URI']}'";
            $body = file_get_contents(OAuth\OAuthRequest::$POST_INPUT);
            if (!empty($body)) {
                $params = OAuth\OAuthUtil::parse_parameters($body);
                if (!empty($params)) {
                    $message .= " with body parameters of:\n" . var_export($params, true);
                } else {
                    $message .= " with a body of:\n" . var_export($body, true);
                }
            }
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
     * @param string $message   Message to be logged
     * @param bool $showSource  True if the name and line number of the current file are to be included
     */
    public static function log(string $message, bool $showSource = false): void
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
        error_log($message . $source);
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
      <input type="submit" value="Continue" />
    </p>
EOD;
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $key = htmlentities($key, ENT_COMPAT | ENT_HTML401, 'UTF-8');
                if (!is_array($value)) {
                    $value = htmlentities($value, ENT_COMPAT | ENT_HTML401, 'UTF-8');
                    $page .= <<< EOD
    <input type="hidden" name="{$key}" id="id_{$key}" value="{$value}" />

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
        }

        $page .= <<< EOD
  </form>
</body>
</html>
EOD;

        return $page;
    }

    /**
     * Redirect to a URL with query parameters.
     *
     * @param string $url    URL to which the form should be submitted
     * @param array $params  Array of form parameters
     *
     * @return never
     */
    public static function redirect(string $url, array $params): never
    {
        if (!empty($params)) {
            if (strpos($url, '?') === false) {
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
        }

        header("Location: {$url}");
        exit;
    }

    /**
     * Set or delete a test cookie.
     *
     * @param bool $delete  True if the cookie is to be deleted (optional, default is false)
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
            } elseif (substr($path, -1) === '/') {
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
            } else if (is_array($clone->$attrName)) {
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

}
