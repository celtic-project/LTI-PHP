<?php
declare(strict_types=1);

namespace ceLTIc\LTI\OAuth;

/**
 * Class to represent an OAuth server
 *
 * @copyright  Andy Smith (http://oauth.googlecode.com/svn/code/php/)
 * @version  2008-08-04
 * @license  https://opensource.org/licenses/MIT The MIT License
 */
class OAuthServer
{

    /**
     * Timestamp threshhold.
     *
     * @var int $timestamp_threshold
     */
    protected $timestamp_threshold = 300; // in seconds, five minutes

    /**
     * Version string.
     *
     * @var string $version
     */
    protected $version = '1.0';

    /**
     * Signature methods.
     *
     * @var array $signature_methods
     */
    protected $signature_methods = [];

    /**
     * Data store.
     *
     * @var OAuthDataStore $data_store
     */
    protected $data_store;

    /**
     * Class constructor.
     *
     * @param OAuthDataStore $data_store  Data store
     */
    function __construct(OAuthDataStore $data_store)
    {
        $this->data_store = $data_store;
    }

    /**
     * Add a signature method.
     *
     * @param OAuthSignatureMethod $signature_method  Signature method
     *
     * @return void
     */
    public function add_signature_method(OAuthSignatureMethod $signature_method): void
    {
        $this->signature_methods[$signature_method->get_name()] = $signature_method;
    }

    // high level functions

    /**
     * Process a request_token request
     *
     * Returns the request token on success
     *
     * @param OAuthRequest $request  Request
     *
     * @return OAuthToken|null
     */
    public function fetch_request_token(OAuthRequest &$request): ?OAuthToken
    {
        $this->get_version($request);

        $consumer = $this->get_consumer($request);

        // no token required for the initial token request
        $token = null;

        $this->check_signature($request, $consumer, $token);

        // Rev A change
        $callback = $request->get_parameter('oauth_callback');
        $new_token = $this->data_store->new_request_token($consumer, $callback);

        return $new_token;
    }

    /**
     * Process an access_token request.
     *
     * Returns the access token on success
     *
     * @param OAuthRequest $request  Request
     *
     * @return OAuthToken|null
     */
    public function fetch_access_token(OAuthRequest &$request): ?OAuthToken
    {
        $this->get_version($request);

        $consumer = $this->get_consumer($request);

        // requires authorized request token
        $token = $this->get_token($request, $consumer, 'request');

        $this->check_signature($request, $consumer, $token);

        // Rev A change
        $verifier = $request->get_parameter('oauth_verifier');
        $new_token = $this->data_store->new_access_token($token, $consumer, $verifier);

        return $new_token;
    }

    /**
     * Verify an API call, checks all the parameters.
     *
     * @param OAuthRequest $request  Request
     *
     * @return array
     */
    public function verify_request(OAuthRequest &$request): array
    {
        $this->get_version($request);
        $consumer = $this->get_consumer($request);
        $token = $this->get_token($request, $consumer, 'access');
        $this->check_signature($request, $consumer, $token);

        return [$consumer, $token];
    }

    // Internals from here

    /**
     * Get version.
     *
     * version 1
     *
     * @param OAuthRequest $request  Request
     *
     * @return string
     * @throws OAuthException
     */
    private function get_version(OAuthRequest &$request): string
    {
        $version = $request->get_parameter('oauth_version');
        if (!$version) {
            // Service Providers MUST assume the protocol version to be 1.0 if this parameter is not present.
            // Chapter 7.0 ("Accessing Protected Ressources")
            $version = '1.0';
        }
        if ($version !== $this->version) {
            throw new OAuthException("OAuth version '{$version}' not supported");
        }

        return $version;
    }

    /**
     * Figure out the signature with some defaults
     *
     * @param OAuthRequest $request  Rauest
     *
     * return OAuthSignatureMethod
     * @throws OAuthException
     */
    private function get_signature_method(OAuthRequest $request): OAuthSignatureMethod
    {
        $signature_method = $request instanceof OAuthRequest ? $request->get_parameter('oauth_signature_method') : null;

        if (!$signature_method) {
            // According to chapter 7 ("Accessing Protected Ressources") the signature-method
            // parameter is required, and we can't just fallback to PLAINTEXT
            throw new OAuthException('No signature method parameter. This parameter is required');
        }

        if (!in_array($signature_method, array_keys($this->signature_methods))) {
            throw new OAuthException("Signature method '{$signature_method}' not supported " .
                    'try one of the following: ' . implode(', ', array_keys($this->signature_methods))
            );
        }

        return $this->signature_methods[$signature_method];
    }

    /**
     * Try to find the consumer for the provided request's consumer key.
     *
     * @param OAuthRequest $request  Request
     *
     * @return OAuthConsumer
     * @throws OAuthException
     */
    private function get_consumer(OAuthRequest $request): OAuthConsumer
    {
        $consumer_key = $request instanceof OAuthRequest ? $request->get_parameter('oauth_consumer_key') : null;

        if (is_null($consumer_key) || (strlen($consumer_key) <= 0)) {
            throw new OAuthException('Invalid consumer key');
        }

        $consumer = $this->data_store->lookup_consumer($consumer_key);
        if (!$consumer) {
            throw new OAuthException('Invalid consumer');
        }

        return $consumer;
    }

    /**
     * Try to find the token for the provided request's token key.
     *
     * @param OAuthRequest $request    Request
     * @param OAuthConsumer $consumer  Consumer
     * @param string $token_type       Token type
     *
     * @return OAuthToken
     * @throws OAuthException
     */
    private function get_token(OAuthRequest $request, OAuthConsumer $consumer, string $token_type = 'access'): OAuthToken
    {
        $token_field = $request instanceof OAuthRequest ? $request->get_parameter('oauth_token') : null;

        $token = $this->data_store->lookup_token($consumer, $token_type, $token_field);
        if (!$token) {
            throw new OAuthException("Invalid $token_type token: {$token_field}");
        }

        return $token;
    }

    /**
     * All-in-one function to check the signature on a request should guess the signature method appropriately.
     *
     * @param OAuthRequest $request    Request
     * @param OAuthConsumer $consumer  Consumer
     * @param OAuthToken $token        Token
     *
     * @return void
     * @throws OAuthException
     */
    private function check_signature(OAuthRequest $request, OAuthConsumer $consumer, OAuthToken $token): void
    {
        // this should probably be in a different method
        $timestamp = $request instanceof OAuthRequest ? $request->get_parameter('oauth_timestamp') : null;
        $nonce = $request instanceof OAuthRequest ? $request->get_parameter('oauth_nonce') : null;

        $this->check_timestamp($timestamp);
        $this->check_nonce($consumer, $token, $nonce, $timestamp);

        $signature_method = $this->get_signature_method($request);

        $signature = $request->get_parameter('oauth_signature');
        $valid_sig = $signature_method->check_signature($request, $consumer, $token, $signature);

        if (!$valid_sig) {
            throw new OAuthException('Invalid signature');
        }
    }

    /**
     * Check that the timestamp is new enough.
     *
     * @param string|null $timestamp  Timestamp
     *
     * @return void
     * @throws OAuthException
     */
    private function check_timestamp(?string $timestamp): void
    {
        if (!$timestamp)
            throw new OAuthException('Missing timestamp parameter. The parameter is required');

        // verify that timestamp is recentish
        $now = time();
        if (abs($now - $timestamp) > $this->timestamp_threshold) {
            throw new OAuthException("Expired timestamp, yours {$timestamp}, ours {$now}");
        }
    }

    /**
     * Check that the nonce is not repeated.
     *
     * @param OAuthConsumer $consumer  Consumer
     * @param OAuthToken $token        Token
     * @param string|null $nonce       Nonce value
     * @param string|null $timestamp   Timestamp
     *
     * @return void
     * @throws OAuthException
     */
    private function check_nonce(OAuthConsumer $consumer, OAuthToken $token, ?string $nonce, ?string $timestamp): void
    {
        if (!$nonce)
            throw new OAuthException('Missing nonce parameter. The parameter is required');

        // verify that the nonce is uniqueish
        $found = $this->data_store->lookup_nonce($consumer, $token, $nonce, $timestamp);
        if ($found) {
            throw new OAuthException("Nonce already used: {$nonce}");
        }
    }

}
