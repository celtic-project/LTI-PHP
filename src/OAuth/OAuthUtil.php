<?php
declare(strict_types=1);

namespace ceLTIc\LTI\OAuth;

/**
 * Class to provide OAuth utility methods
 *
 * @copyright  Andy Smith (http://oauth.googlecode.com/svn/code/php/)
 * @version  2008-08-04
 * @license  https://opensource.org/licenses/MIT The MIT License
 */
class OAuthUtil
{

    /**
     * URL encode.
     *
     * @param mixed $input  Value to be encoded
     *
     * @return array|string
     */
    public static function urlencode_rfc3986(mixed $input): array|string
    {
        if (is_array($input)) {
            return array_map(['ceLTIc\LTI\OAuth\OAuthUtil', 'urlencode_rfc3986'], $input);
        } elseif (is_scalar($input)) {
            return str_replace('+', ' ', str_replace('%7E', '~', rawurlencode(strval($input))));
        } else {
            return '';
        }
    }

    /**
     * URL decode.
     *
     * This decode function isn't taking into consideration the above
     * modifications to the encoding process. However, this method doesn't
     * seem to be used anywhere so leaving it as is.
     *
     * @param string $string  String to be decoded
     *
     * @return string
     */
    public static function urldecode_rfc3986(string $string): string
    {
        return urldecode($string);
    }

    /**
     * Utility function for turning the Authorization: header into
     * parameters, has to do some unescaping.
     *
     * Can filter out any non-oauth parameters if needed (default behaviour)
     * May 28th, 2010 - method updated to tjerk.meesters for a speed improvement.
     *                  see http://code.google.com/p/oauth/issues/detail?id=163
     *
     * @param string $header                     Header value
     * @param bool $only_allow_oauth_parameters  True if only OAuth parameters are allowed
     *
     * @return array
     */
    public static function split_header(string $header, bool $only_allow_oauth_parameters = true): array
    {
        $params = [];
        if (preg_match_all('/(' . ($only_allow_oauth_parameters ? 'oauth_' : '') . '[a-z_-]*)=(:?"([^"]*)"|([^,]*))/', $header,
                $matches)) {
            foreach ($matches[1] as $i => $h) {
                $params[$h] = OAuthUtil::urldecode_rfc3986(empty($matches[3][$i]) ? $matches[4][$i] : $matches[3][$i]);
            }
            if (isset($params['realm'])) {
                unset($params['realm']);
            }
        }

        return $params;
    }

    /**
     * Helper to try to sort out headers for people who aren't running apache.
     *
     * @return array
     */
    public static function get_headers(): array
    {
        if (function_exists('apache_request_headers')) {
            // we need this to get the actual Authorization: header
            // because apache tends to tell us it doesn't exist
            $headers = apache_request_headers();

            // sanitize the output of apache_request_headers because
            // we always want the keys to be Cased-Like-This and arh()
            // returns the headers in the same case as they are in the
            // request
            $out = [];
            foreach ($headers AS $key => $value) {
                $key = str_replace(" ", "-", ucwords(strtolower(str_replace("-", " ", $key))));
                $out[$key] = $value;
            }
        } else {
            // otherwise we don't have apache and are just going to have to hope
            // that $_SERVER actually contains what we need
            $out = [];
            if (isset($_SERVER['CONTENT_TYPE']))
                $out['Content-Type'] = $_SERVER['CONTENT_TYPE'];
            if (isset($_ENV['CONTENT_TYPE']))
                $out['Content-Type'] = $_ENV['CONTENT_TYPE'];

            foreach ($_SERVER as $key => $value) {
                if (str_starts_with($key, 'HTTP_')) {
                    // this is chaos, basically it is just there to capitalize the first
                    // letter of every word that is not an initial HTTP and strip HTTP
                    // code from przemek
                    $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                    $out[$key] = $value;
                }
            }
        }
        return $out;
    }

    /**
     * Parse parameters.
     *
     * This function takes a input like a=b&a=c&d=e and returns the parsed
     * parameters like this
     * ['a' => ['b','c'], 'd' => 'e']
     *
     * @param string|null $input  Parameter string to be parsed
     *
     * @return array
     */
    public static function parse_parameters(?string $input): array
    {
        if (!isset($input) || !$input)
            return [];

        $pairs = explode('&', $input);

        $parsed_parameters = [];
        foreach ($pairs as $pair) {
            $split = explode('=', $pair, 2);
            $parameter = self::urldecode_rfc3986($split[0]);
            $value = isset($split[1]) ? self::urldecode_rfc3986($split[1]) : '';

            if (isset($parsed_parameters[$parameter])) {
                // We have already recieved parameter(s) with this name, so add to the list
                // of parameters with this name

                if (is_scalar($parsed_parameters[$parameter])) {
                    // This is the first duplicate, so transform scalar (string) into an array
                    // so we can add the duplicates
                    $parsed_parameters[$parameter] = [$parsed_parameters[$parameter]];
                }

                $parsed_parameters[$parameter][] = $value;
            } else {
                $parsed_parameters[$parameter] = $value;
            }
        }

        return $parsed_parameters;
    }

    /**
     * Build HTTP query string.
     *
     * @param array|null $params  Array of parameters
     *
     * @return string
     */
    public static function build_http_query(?array $params): string
    {
        if (!$params)
            return '';

        // Urlencode both keys and values
        $keys = OAuthUtil::urlencode_rfc3986(array_keys($params));
        $values = OAuthUtil::urlencode_rfc3986(array_values($params));
        $params = array_combine($keys, $values);

        // Parameters are sorted by name, using lexicographical byte value ordering.
        // Ref: Spec: 9.1.1 (1)
        uksort($params, 'strcmp');

        $pairs = [];
        foreach ($params as $parameter => $value) {
            if (is_array($value)) {
                // If two or more parameters share the same name, they are sorted by their value
                // Ref: Spec: 9.1.1 (1)
                // June 12th, 2010 - changed to sort because of issue 164 by hidetaka
                sort($value, SORT_STRING);
                foreach ($value as $duplicate_value) {
                    $pairs[] = $parameter . '=' . $duplicate_value;
                }
            } else {
                $pairs[] = $parameter . '=' . $value;
            }
        }

        // For each parameter, the name is separated from the corresponding value by an '=' character (ASCII code 61)
        // Each name-value pair is separated by an '&' character (ASCII code 38)
        return implode('&', $pairs);
    }

    /**
     * Recursively merge two arrays.
     *
     * @param array $array1  First array
     * @param array $array2  Second array
     *
     * @return array
     */
    public static function array_merge_recursive(array $array1, array $array2): array
    {
        $array = [];
        foreach ($array1 as $key => $value) {
            if (!isset($array2[$key])) {
                $array[$key] = $value;
            } elseif (!is_array($value)) {
                if (!is_array($array2[$key])) {
                    $array[$key] = [$value, $array2[$key]];
                } else {
                    $array[$key] = \array_merge([$value], $array2[$key]);
                }
            } else {
                if (!is_array($array2[$key])) {
                    $array3 = [$array2[$key]];
                } else {
                    $array3 = $array2[$key];
                }
                $array[$key] = \array_merge($value, $array3);
            }
        }
        foreach ($array2 as $key => $value) {
            if (!isset($array1[$key])) {
                $array[$key] = $value;
            }
        }

        return $array;
    }

}
