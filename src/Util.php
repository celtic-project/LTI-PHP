<?php

namespace ceLTIc\LTI;

use ceLTIc\LTI\OAuth;

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
     * LTI version 1 for messages.
     */
    const LTI_VERSION1 = 'LTI-1p0';

    /**
     * LTI version 1.3 for messages.
     */
    const LTI_VERSION1P3 = '1.3.0';

    /**
     * LTI version 2 for messages.
     */
    const LTI_VERSION2 = 'LTI-2p0';

    /**
     * Prefix for standard JWT message claims.
     */
    const JWT_CLAIM_PREFIX = 'https://purl.imsglobal.org/spec/lti';

    /**
     * Mapping for standard message types.
     */
    const MESSAGE_TYPE_MAPPING = array(
        'basic-lti-launch-request' => 'LtiResourceLinkRequest',
        'ContentItemSelectionRequest' => 'LtiDeepLinkingRequest',
        'ContentItemSelection' => 'LtiDeepLinkingResponse'
    );

    /**
     * Mapping for standard message parameters to JWT claim.
     */
    const JWT_CLAIM_MAPPING = array(
        'accept_types' => array('suffix' => 'dl', 'group' => 'deep_linking_settings', 'claim' => 'accept_types', 'isArray' => true),
        'accept_copy_advice' => array('suffix' => 'dl', 'group' => 'deep_linking_settings', 'claim' => 'copyAdvice', 'isBoolean' => true),
        'accept_media_types' => array('suffix' => 'dl', 'group' => 'deep_linking_settings', 'claim' => 'accept_media_types'),
        'accept_multiple' => array('suffix' => 'dl', 'group' => 'deep_linking_settings', 'claim' => 'accept_multiple', 'isBoolean' => true),
        'accept_presentation_document_targets' => array('suffix' => 'dl', 'group' => 'deep_linking_settings', 'claim' => 'accept_presentation_document_targets', 'isArray' => true),
        'accept_unsigned' => array('suffix' => 'dl', 'group' => 'deep_linking_settings', 'claim' => 'accept_unsigned', 'isBoolean' => true),
        'auto_create' => array('suffix' => 'dl', 'group' => 'deep_linking_settings', 'claim' => 'auto_create', 'isBoolean' => true),
        'can_confirm' => array('suffix' => 'dl', 'group' => 'deep_linking_settings', 'claim' => 'can_confirm'), // Not included in Deep Linking v2 spec
        'content_item_return_url' => array('suffix' => 'dl', 'group' => 'deep_linking_settings', 'claim' => 'deep_link_return_url'),
        'content_items' => array('suffix' => 'dl', 'group' => '', 'claim' => 'content_items', 'isObject' => true),
        'data' => array('suffix' => 'dl', 'group' => 'deep_linking_settings', 'claim' => 'data'),
        'text' => array('suffix' => 'dl', 'group' => 'deep_linking_settings', 'claim' => 'text'),
        'title' => array('suffix' => 'dl', 'group' => 'deep_linking_settings', 'claim' => 'title'),
        'lti_msg' => array('suffix' => 'dl', 'group' => '', 'claim' => 'msg'),
        'lti_errormsg' => array('suffix' => 'dl', 'group' => '', 'claim' => 'errormsg'),
        'lti_log' => array('suffix' => 'dl', 'group' => '', 'claim' => 'log'),
        'lti_errorlog' => array('suffix' => 'dl', 'group' => '', 'claim' => 'errorlog'),
        'context_id' => array('suffix' => '', 'group' => 'context', 'claim' => 'id'),
        'context_label' => array('suffix' => '', 'group' => 'context', 'claim' => 'label'),
        'context_title' => array('suffix' => '', 'group' => 'context', 'claim' => 'title'),
        'context_type' => array('suffix' => '', 'group' => 'context', 'claim' => 'type', 'isArray' => true),
        'lis_course_offering_sourcedid' => array('suffix' => '', 'group' => 'lis', 'claim' => 'course_offering_sourcedid'),
        'lis_course_section_sourcedid' => array('suffix' => '', 'group' => 'lis', 'claim' => 'course_section_sourcedid'),
        'launch_presentation_css_url' => array('suffix' => '', 'group' => 'launch_presentation', 'claim' => 'css_url'),
        'launch_presentation_document_target' => array('suffix' => '', 'group' => 'launch_presentation', 'claim' => 'document_target'),
        'launch_presentation_height' => array('suffix' => '', 'group' => 'launch_presentation', 'claim' => 'height'),
        'launch_presentation_locale' => array('suffix' => '', 'group' => 'launch_presentation', 'claim' => 'locale'),
        'launch_presentation_return_url' => array('suffix' => '', 'group' => 'launch_presentation', 'claim' => 'return_url'),
        'launch_presentation_width' => array('suffix' => '', 'group' => 'launch_presentation', 'claim' => 'width'),
        'lis_person_contact_email_primary' => array('suffix' => '', 'group' => null, 'claim' => 'email'),
        'lis_person_name_family' => array('suffix' => '', 'group' => null, 'claim' => 'family_name'),
        'lis_person_name_full' => array('suffix' => '', 'group' => null, 'claim' => 'name'),
        'lis_person_name_given' => array('suffix' => '', 'group' => null, 'claim' => 'given_name'),
        'lis_person_sourcedid' => array('suffix' => '', 'group' => 'lis', 'claim' => 'person_sourcedid'),
        'user_id' => array('suffix' => '', 'group' => null, 'claim' => 'sub'),
        'user_image' => array('suffix' => '', 'group' => null, 'claim' => 'picture'),
        'roles' => array('suffix' => '', 'group' => '', 'claim' => 'roles', 'isArray' => true),
        'platform_id' => array('suffix' => '', 'group' => null, 'claim' => 'iss'),
        'deployment_id' => array('suffix' => '', 'group' => '', 'claim' => 'deployment_id'),
        'lti_message_type' => array('suffix' => '', 'group' => '', 'claim' => 'message_type'),
        'lti_version' => array('suffix' => '', 'group' => '', 'claim' => 'version'),
        'resource_link_description' => array('suffix' => '', 'group' => 'resource_link', 'claim' => 'description'),
        'resource_link_id' => array('suffix' => '', 'group' => 'resource_link', 'claim' => 'id'),
        'resource_link_title' => array('suffix' => '', 'group' => 'resource_link', 'claim' => 'title'),
        'tool_consumer_info_product_family_code' => array('suffix' => '', 'group' => 'tool_platform', 'claim' => 'family_code'),
        'tool_consumer_info_version' => array('suffix' => '', 'group' => 'tool_platform', 'claim' => 'version'),
        'tool_consumer_instance_contact_email' => array('suffix' => '', 'group' => 'tool_platform', 'claim' => 'contact_email'),
        'tool_consumer_instance_description' => array('suffix' => '', 'group' => 'tool_platform', 'claim' => 'description'),
        'tool_consumer_instance_guid' => array('suffix' => '', 'group' => 'tool_platform', 'claim' => 'guid'),
        'tool_consumer_instance_name' => array('suffix' => '', 'group' => 'tool_platform', 'claim' => 'name'),
        'tool_consumer_instance_url' => array('suffix' => '', 'group' => 'tool_platform', 'claim' => 'url'),
        'tool_state' => array('suffix' => '', 'group' => 'tool', 'claim' => 'state'),
        'custom_context_memberships_v2_url' => array('suffix' => 'nrps', 'group' => 'namesroleservice', 'claim' => 'context_memberships_url'),
        'custom_nrps_versions' => array('suffix' => 'nrps', 'group' => 'namesroleservice', 'claim' => 'service_versions', 'isArray' => true),
        'custom_lineitems_url' => array('suffix' => 'ags', 'group' => 'endpoint', 'claim' => 'lineitems'),
        'custom_lineitem_url' => array('suffix' => 'ags', 'group' => 'endpoint', 'claim' => 'lineitem'),
        'custom_ags_scopes' => array('suffix' => 'ags', 'group' => 'endpoint', 'claim' => 'scope', 'isArray' => true),
        'custom_context_groups_url' => array('suffix' => 'gs', 'group' => 'groupsservice', 'claim' => 'context_groups_url'),
        'custom_context_group_sets_url' => array('suffix' => 'gs', 'group' => 'groupsservice', 'claim' => 'context_group_sets_url'),
        'custom_gs_scopes' => array('suffix' => 'gs', 'group' => 'groupsservice', 'claim' => 'scope', 'isArray' => true),
        'custom_gs_versions' => array('suffix' => 'gs', 'group' => 'groupsservice', 'claim' => 'service_versions', 'isArray' => true),
        'lis_outcome_service_url' => array('suffix' => 'bos', 'group' => 'basicoutcomesservice', 'claim' => 'lis_outcome_service_url'),
        'lis_result_sourcedid' => array('suffix' => 'bos', 'group' => 'basicoutcomesservice', 'claim' => 'lis_result_sourcedid')
    );

    /**
     * No logging.
     */
    const LOGLEVEL_NONE = 0;

    /**
     * Log errors only.
     */
    const LOGLEVEL_ERROR = 1;

    /**
     * Log error and information messages.
     */
    const LOGLEVEL_INFO = 2;

    /**
     * Log all messages.
     */
    const LOGLEVEL_DEBUG = 3;

    /**
     * Permitted LTI versions for messages.
     */
    public static $LTI_VERSIONS = array(self::LTI_VERSION1, self::LTI_VERSION1P3, self::LTI_VERSION2);

    /**
     * List of supported message types and associated class methods.
     */
    public static $METHOD_NAMES = array('basic-lti-launch-request' => 'onLaunch',
        'ConfigureLaunchRequest' => 'onConfigure',
        'DashboardRequest' => 'onDashboard',
        'ContentItemSelectionRequest' => 'onContentItem',
        'ToolProxyRegistrationRequest' => 'onRegister'
    );

    /**
     * GET and POST request parameters
     */
    public static $requestParameters = null;

    /**
     * Current logging level.
     *
     * @var int $logLevel
     */
    public static $logLevel = self::LOGLEVEL_NONE;

    /**
     * Return GET and POST request parameters (POST parameters take precedence)
     *
     * @return array
     */
    public static function getRequestParameters()
    {
        if (is_null(self::$requestParameters)) {
            self::$requestParameters = array_merge($_GET, $_POST);
        }

        return self::$requestParameters;
    }

    /**
     * Log an error message.
     *
     * @param string  $message     Message to be logged
     * @param bool    $showSource  True if the name and line number of the current file are to be included
     */
    public static function logError($message, $showSource = true)
    {
        if (self::$logLevel >= self::LOGLEVEL_ERROR) {
            self::log("[ERROR] {$message}", $showSource);
        }
    }

    /**
     * Log an information message.
     *
     * @param string  $message     Message to be logged
     * @param bool    $showSource  True if the name and line number of the current file are to be included
     */
    public static function logInfo($message, $showSource = false)
    {
        if (self::$logLevel >= self::LOGLEVEL_INFO) {
            self::log("[INFO] {$message}", $showSource);
        }
    }

    /**
     * Log a debug message.
     *
     * @param string  $message     Message to be logged
     * @param bool    $showSource  True if the name and line number of the current file are to be included
     */
    public static function logDebug($message, $showSource = false)
    {
        if (self::$logLevel >= self::LOGLEVEL_DEBUG) {
            self::log("[DEBUG] {$message}", $showSource);
        }
    }

    /**
     * Log a request.
     *
     * @param bool    $debugLevel  True if the request details should be logged at the debug level (optional, default is false for information level)
     */
    public static function logRequest($debugLevel = false)
    {
        if (!$debugLevel) {
            $logLevel = self::LOGLEVEL_INFO;
        } else {
            $logLevel = self::LOGLEVEL_DEBUG;
        }
        if (self::$logLevel >= $logLevel) {
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
     * Log an error message irrespective of the logging level.
     *
     * @param string  $message     Message to be logged
     * @param bool    $showSource  True if the name and line number of the current file are to be included
     */
    public static function log($message, $showSource = false)
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
     * @param string $url       URL to which the form should be submitted
     * @param array  $params    Array of form parameters
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

    /**
     * Redirect to a URL with query parameters.
     *
     * @param string $url         URL to which the form should be submitted
     * @param array    $params    Array of form parameters
     *
     * @return string
     */
    public static function redirect($url, $params)
    {
        if (!empty($params)) {
            if (strpos($url, '?') === false) {
                $url .= '?';
                $sep = '';
            } else {
                $sep = '&';
            }
            foreach ($params as $key => $value) {
                $key = urlencode($key);
                if (!is_array($value)) {
                    $value = urlencode($value);
                    $url .= "{$sep}{$key}={$value}";
                    $sep = '&';
                } else {
                    foreach ($value as $element) {
                        $element = urlencode($element);
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
     * Generate a random string.
     *
     * The generated string will only comprise letters (upper- and lower-case) and digits.
     *
     * @param int $length Length of string to be generated (optional, default is 8 characters)
     *
     * @return string Random string
     */
    public static function getRandomString($length = 8)
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

        $value = '';
        $charsLength = strlen($chars) - 1;

        for ($i = 1; $i <= $length; $i++) {
            $value .= $chars[rand(0, $charsLength)];
        }

        return $value;
    }

}
