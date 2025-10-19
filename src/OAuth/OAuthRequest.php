<?php
declare(strict_types=1);

namespace ceLTIc\LTI\OAuth;

/**
 * Class to represent an OAuth request
 *
 * @copyright  Andy Smith (http://oauth.googlecode.com/svn/code/php/)
 * @version  2008-08-04
 * @license  https://opensource.org/licenses/MIT The MIT License
 */
class OAuthRequest
{

    /**
     * Request parameters.
     *
     * @var array|null $parameters
     */
    protected ?array $parameters;

    /**
     * HTTP method.
     *
     * @var string $http_method
     */
    protected string $http_method;

    /**
     * HTTP URL.
     *
     * @var string $http_url
     */
    protected string $http_url;

    // For debug purposes

    /**
     * Base string.
     *
     * @var string $base_string
     */
    public string $base_string;

    /**
     * Version.
     *
     * @var string $version
     */
    public static string $version = '1.0';

    /**
     * Access to POST data.
     *
     * @var string $POST_INPUT
     */
    public static string $POST_INPUT = 'php://input';

    /**
     * Class constructor.
     *
     * @param string $http_method     HTTP method
     * @param string $http_url        HTTP URL
     * @param array|null $parameters  Request parameters
     */
    function __construct(string $http_method, string $http_url, ?array $parameters = null)
    {
        $parameters = ($parameters) ? $parameters : [];
        $query_params = parse_url($http_url, PHP_URL_QUERY);
        if ($query_params) {
            $parameters = OAuthUtil::array_merge_recursive(OAuthUtil::parse_parameters($query_params), $parameters);
        }
        $this->parameters = $parameters;
        $this->http_method = $http_method;
        $this->http_url = $http_url;
    }

    /**
     * Attempt to build up a request from what was passed to the server.
     *
     * @param string|null $http_method  HTTP method
     * @param string|null $http_url     HTTP URL
     * @param array|null $parameters    Request parameters
     *
     * @return OAuthRequest
     */
    public static function from_request(?string $http_method = null, ?string $http_url = null, ?array $parameters = null): OAuthRequest
    {
        if (!$http_url) {
            $proto = null;
            if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
                $forwardedProto = explode(',', str_replace(' ', ',', trim($_SERVER['HTTP_X_FORWARDED_PROTO'])));
                $proto = reset($forwardedProto);
            } elseif (isset($_SERVER['HTTP_X_URL_SCHEME'])) {
                $proto = $_SERVER['HTTP_X_URL_SCHEME'];
            }
            $ssl = null;
            if (isset($_SERVER['HTTP_X_FORWARDED_SSL'])) {
                $forwardedSsl = explode(',', str_replace(' ', ',', trim($_SERVER['HTTP_X_FORWARDED_SSL'])));
                $ssl = reset($forwardedSsl);
            }
            if (($proto === 'https') || ($ssl === 'on')) {
                $_SERVER['HTTPS'] = 'on';
                $_SERVER['SERVER_PORT'] = 443;
            } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
                $_SERVER['HTTPS'] = 'off';
                $_SERVER['SERVER_PORT'] = 80;
            } elseif (!isset($_SERVER['HTTPS'])) {
                $_SERVER['HTTPS'] = 'off';
            }
            if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
                $forwardedHosts = str_replace(' ', ',', trim($_SERVER['HTTP_X_FORWARDED_HOST']));  // Use first if multiple hosts listed
                $hosts = explode(',', $forwardedHosts);
                if (!empty($hosts[0])) {
                    $host = explode(':', $hosts[0], 2);
                    $_SERVER['SERVER_NAME'] = $host[0];
                    if (count($host) > 1) {
                        $_SERVER['SERVER_PORT'] = $host[1];
                    } elseif ($_SERVER['HTTPS'] === 'on') {
                        $_SERVER['SERVER_PORT'] = 443;
                    } else {
                        $_SERVER['SERVER_PORT'] = 80;
                    }
                }
            } elseif (!empty($_SERVER['HTTP_X_ORIGINAL_HOST'])) {
                $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_X_ORIGINAL_HOST'];
            }
            $scheme = ($_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
            $http_url = "{$scheme}://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}{$_SERVER['REQUEST_URI']}";
        }
        $http_method = ($http_method) ? $http_method : $_SERVER['REQUEST_METHOD'];

        // We weren't handed any parameters, so let's find the ones relevant to
        // this request.
        // If you run XML-RPC or similar you should use this to provide your own
        // parsed parameter-list
        if (!$parameters) {
            // Find request headers
            $request_headers = OAuthUtil::get_headers();

            $parameters = [];
            if (($http_method === 'POST' && isset($request_headers['Content-Type']) && stristr($request_headers['Content-Type'],
                    'application/x-www-form-urlencoded')) || !empty($_POST)) {
                // It's a POST request of the proper content-type, so parse POST
                // parameters and add those overriding any duplicates from GET
                $parameters = OAuthUtil::parse_parameters(file_get_contents(static::$POST_INPUT));
            }

            // We have a Authorization-header with OAuth data. Parse the header
            // and add those overriding any duplicates from GET or POST
            if (isset($request_headers['Authorization']) && str_starts_with($request_headers['Authorization'], 'OAuth ')) {
                $header_parameters = OAuthUtil::split_header($request_headers['Authorization']);
                $parameters = OAuthUtil::array_merge_recursive($parameters, $header_parameters);
            }
        }

        return new OAuthRequest($http_method, $http_url, $parameters);
    }

    /**
     * Pretty much a helper function to set up the request.
     *
     * @param OAuthConsumer $consumer  Consumer
     * @param OAuthToken|null $token   Token
     * @param string $http_method      HTTP method
     * @param string $http_url         HTTP URL
     * @param array|null $parameters   Request parameters
     *
     * @return OAuthRequest
     */
    public static function from_consumer_and_token(OAuthConsumer $consumer, ?OAuthToken $token, string $http_method,
        string $http_url, ?array $parameters = null): OAuthRequest
    {
        $parameters = ($parameters) ? $parameters : [];
        $defaults = [
            'oauth_version' => OAuthRequest::$version,
            'oauth_nonce' => OAuthRequest::generate_nonce(),
            'oauth_timestamp' => strval(OAuthRequest::generate_timestamp()),
            'oauth_consumer_key' => $consumer->key]
        ;
        if ($token) {
            $defaults['oauth_token'] = $token->key;
        }

        $parameters = OAuthUtil::array_merge_recursive($defaults, $parameters);

        return new OAuthRequest($http_method, $http_url, $parameters);
    }

    /**
     * Set a parameter.
     *
     * @param string $name            Parameter name
     * @param string $value           Parameter value
     * @param bool $allow_duplicates  True if duplicates are allowed
     *
     * @return void
     */
    public function set_parameter(string $name, string $value, bool $allow_duplicates = true): void
    {
        if ($allow_duplicates && isset($this->parameters[$name])) {
            // We have already added parameter(s) with this name, so add to the list
            if (is_scalar($this->parameters[$name])) {
                // This is the first duplicate, so transform scalar (string)
                // into an array so we can add the duplicates
                $this->parameters[$name] = [$this->parameters[$name]];
            }

            $this->parameters[$name][] = $value;
        } else {
            $this->parameters[$name] = $value;
        }
    }

    /**
     * Get a parameter.
     *
     * @param string $name  Parameter name
     *
     * @return string|array|null
     */
    public function get_parameter(string $name): string|array|null
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
    }

    /**
     * Get request parameters.
     *
     * @return array
     */
    public function get_parameters(): array
    {
        return $this->parameters;
    }

    /**
     * Delete a parameter.
     *
     * @param string $name  Parameter name
     *
     * @return void
     */
    public function unset_parameter(string $name): void
    {
        unset($this->parameters[$name]);
    }

    /**
     * The request parameters, sorted and concatenated into a normalized string.
     *
     * @return string
     */
    public function get_signable_parameters(): string
    {
        // Grab all parameters
        $params = $this->parameters;

        // Remove oauth_signature if present
        // Ref: Spec: 9.1.1 ("The oauth_signature parameter MUST be excluded.")
        if (isset($params['oauth_signature'])) {
            unset($params['oauth_signature']);
        }

        return OAuthUtil::build_http_query($params);
    }

    /**
     * Returns the base string of this request.
     *
     * The base string defined as the method, the url
     * and the parameters (normalized), each urlencoded
     * and then concatenated with &.
     *
     * @return string
     */
    public function get_signature_base_string(): string
    {
        $parts = [
            $this->get_normalized_http_method(),
            $this->get_normalized_http_url(),
            $this->get_signable_parameters()
        ];

        $parts = OAuthUtil::urlencode_rfc3986($parts);

        return implode('&', $parts);
    }

    /**
     * Just uppercases the http method.
     *
     * @return string
     */
    public function get_normalized_http_method(): string
    {
        return strtoupper($this->http_method);
    }

    /**
     * Parses the url and rebuilds it to be scheme://host/path
     *
     * @return string
     */
    public function get_normalized_http_url(): string
    {
        $parts = parse_url($this->http_url);
        if (is_array($parts)) {
            $scheme = (isset($parts['scheme'])) ? $parts['scheme'] : 'http';
            $port = (isset($parts['port'])) ? $parts['port'] : (($scheme === 'https') ? '443' : '80');
            $host = (isset($parts['host'])) ? strtolower($parts['host']) : '';
            $path = (isset($parts['path'])) ? $parts['path'] : '';
            if ((($scheme === 'https') && (intval($port) !== 443)) || (($scheme === 'http') && (intval($port) !== 80))) {
                $host = "{$host}:{$port}";
            }
            $url = "{$scheme}://{$host}{$path}";
        } else {
            $url = '';
        }

        return $url;
    }

    /**
     * Builds a url usable for a GET request.
     *
     * @return string
     */
    public function to_url(): string
    {
        $post_data = $this->to_postdata();
        $out = $this->get_normalized_http_url();
        if ($post_data) {
            $out .= '?' . $post_data;
        }

        return $out;
    }

    /**
     * Builds the data one would send in a POST request.
     *
     * @return string
     */
    public function to_postdata(): string
    {
        return OAuthUtil::build_http_query($this->parameters);
    }

    /**
     * Builds the Authorization: header.
     *
     * @param string|null $realm  Realm
     *
     * @return string
     * @throws OAuthException
     */
    public function to_header(?string $realm = null): string
    {
        $first = true;
        if ($realm) {
            $out = 'Authorization: OAuth realm="' . OAuthUtil::urlencode_rfc3986($realm) . '"';
            $first = false;
        } else
            $out = 'Authorization: OAuth';

        $total = [];
        foreach ($this->parameters as $k => $v) {
            if (!str_starts_with($k, 'oauth')) {
                continue;
            }
            if (is_array($v)) {
                throw new OAuthException('Arrays not supported in headers');
            }
            $out .= ($first) ? ' ' : ',';
            $out .= OAuthUtil::urlencode_rfc3986($k) . '="' . OAuthUtil::urlencode_rfc3986($v) . '"';
            $first = false;
        }

        return $out;
    }

    /**
     * Convert object to a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->to_url();
    }

    /**
     * Sign the request.
     *
     * @param OAuthSignatureMethod $signature_method  Signature method
     * @param OAuthConsumer $consumer                 Consumer
     * @param OAuthToken|null $token                  Token
     *
     * @return void
     */
    public function sign_request(OAuthSignatureMethod $signature_method, OAuthConsumer $consumer, ?OAuthToken $token): void
    {
        $this->set_parameter('oauth_signature_method', $signature_method->get_name(), false);
        $signature = $this->build_signature($signature_method, $consumer, $token);
        $this->set_parameter('oauth_signature', $signature, false);
    }

    /**
     * Build the signature.
     *
     * @param OAuthSignatureMethod $signature_method  Signature method
     * @param OAuthConsumer $consumer                 Consumer
     * @param OAuthToken|null $token                  Token
     *
     * @return string
     */
    public function build_signature(OAuthSignatureMethod $signature_method, OAuthConsumer $consumer, ?OAuthToken $token): string
    {
        $signature = $signature_method->build_signature($this, $consumer, $token);
        return $signature;
    }

    /**
     * Util function: current timestamp
     *
     * @return int
     */
    private static function generate_timestamp(): int
    {
        return time();
    }

    /**
     * Util function: current nonce
     *
     * @return string
     */
    private static function generate_nonce(): string
    {
        $mt = microtime();
        $rand = mt_rand();

        return md5($mt . $rand); // md5s look nicer than numbers
    }

}
