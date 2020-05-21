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
     * No logging.
     */
    const LOGLEVEL_NONE = 0;

    /**
     * Log errors only.
     */
    const LOGLEVEL_ERROR = 1;

    /**
     * Log all messages.
     */
    const LOGLEVEL_INFO = 2;

    /**
     * Current logging level.
     *
     * @var int $logLevel
     */
    public static $logLevel = self::LOGLEVEL_NONE;

    /**
     * Log an error message.
     *
     * @param string  $message     Message to be logged
     * @param bool    $showSource  True if the name and line number of the current file are to be included
     */
    public static function logError($message, $showSource = true)
    {
        if (self::$logLevel >= self::LOGLEVEL_ERROR) {
            self::log($message, $showSource);
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
            self::log($message, $showSource);
        }
    }

    /**
     * Log a request.
     */
    public static function logRequest()
    {
        if (self::$logLevel >= self::LOGLEVEL_INFO) {
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
            self::log($message);
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

}
