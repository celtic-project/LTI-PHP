<?php

namespace ceLTIc\LTI\Jwt;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use ceLTIc\LTI\Http\HttpMessage;
use ceLTIc\LTI\Util;

/**
 * Class to implement the JWT interface using the Spomky-Labs library.
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license   GNU Lesser General Public License, version 3 (<http://www.gnu.org/licenses/lgpl.html>)
 */
class FirebaseClient implements ClientInterface
{

    private $jwtString = null;
    private $jwtHeaders = null;
    private $jwtPayload = null;

    /**
     * Check if a JWT is defined.
     *
     * @return bool True if a JWT is defined
     */
    public function hasJwt()
    {
        return !empty($this->jwtString);
    }

    /**
     * Load a JWT from a string.
     *
     * @param string $jwtString  JWT string
     *
     * @return bool True if the JWT was successfully loaded
     */
    public function load($jwtString)
    {
        $sections = explode('.', $jwtString);
        $ok = count($sections) === 3;
        if ($ok) {
            $headers = json_decode(JWT::urlsafeB64Decode($sections[0]));
            $payload = json_decode(JWT::urlsafeB64Decode($sections[1]));
            $ok = !is_null($headers) && !is_null($payload);
        }
        if ($ok) {
            $this->jwtString = $jwtString;
            $this->jwtHeaders = $headers;
            $this->jwtPayload = $payload;
        } else {
            $this->jwtString = null;
            $this->jwtHeaders = null;
            $this->jwtPayload = null;
        }

        return $ok;
    }

    /**
     * Check whether a JWT has a header with the specified name.
     *
     * @param string $name  Header name
     *
     * @return bool True if the JWT has a header of the specified name
     */
    public function hasHeader($name)
    {
        return !empty($this->jwtHeaders) && isset($this->jwtHeaders->{$name});
    }

    /**
     * Get the value of the header with the specified name.
     *
     * @param string $name  Header name
     * @param string $defaultValue  Default value
     *
     * @return string The value of the header with the specified name, or the default value if it does not exist
     */
    public function getHeader($name, $defaultValue = null)
    {
        if ($this->hasHeader($name)) {
            $value = $this->jwtHeaders->{$name};
        } else {
            $value = $defaultValue;
        }

        return $value;
    }

    /**
     * Get the value of the headers.
     *
     * @return array The value of the headers
     */
    public function getHeaders()
    {
        return $this->jwtHeaders;
    }

    /**
     * Check whether a JWT has a claim with the specified name.
     *
     * @param string $name  Claim name
     *
     * @return bool True if the JWT has a claim of the specified name
     */
    public function hasClaim($name)
    {
        return !empty($this->jwtPayload) && isset($this->jwtPayload->{$name});
    }

    /**
     * Get the value of the claim with the specified name.
     *
     * @param string $name  Claim name
     * @param string $defaultValue  Default value
     *
     * @return string|array The value of the claim with the specified name, or the default value if it does not exist
     */
    public function getClaim($name, $defaultValue = null)
    {
        if ($this->hasClaim($name)) {
            $value = $this->jwtPayload->{$name};
        } else {
            $value = $defaultValue;
        }
        if (is_object($value)) {
            $value = (array) $value;
        }

        return $value;
    }

    /**
     * Get the value of the payload.
     *
     * @return array The value of the payload
     */
    public function getPayload()
    {
        return $this->jwtPayload;
    }

    /**
     * Verify the signature of the JWT.
     *
     * @param string $publicKey  Public key of issuer
     * @param string $jku        JSON Web Key URL of issuer (optional)
     *
     * @return bool True if the JWT has a valid signature
     */
    public function verify($publicKey, $jku = null)
    {
        $ok = false;
        $hasPublicKey = !empty($publicKey);
        if ($hasPublicKey) {
            if (is_string($publicKey)) {
                $json = json_decode($publicKey, true);
                if (!is_null($json)) {
                    try {
                        $jwks = array('keys' => array($json));
                        $publicKey = JWK::parseKeySet($jwks);
                    } catch (\Exception $e) {

                    }
                }
            }
        } elseif (!empty($jku)) {
            $publicKey = $this->fetchPublicKey($jku);
        }
        $retry = false;
        do {
            try {
                JWT::decode($this->jwtString, $publicKey, array('RS256', 'RS384', 'RS512'));
                $ok = true;
            } catch (\Exception $e) {
                Util::logError($e->getMessage());
                if (!$retry && $hasPublicKey && !empty($jku)) {
                    $retry = true;
                    $publicKey = $this->fetchPublicKey($jku);
                }
            }
        } while (!$ok && $retry);

        return $ok;
    }

    /**
     * Sign the JWT.
     *
     * @param  array $payload          Payload
     * @param string $signatureMethod  Signature method
     * @param string $privateKey       Private key in PEM format
     * @param string $kid              Key ID (optional)
     * @param string $jku              JSON Web Key URL (optional)     *
     *
     * @return string Signed JWT
     */
    public static function sign($payload, $signatureMethod, $privateKey, $kid = null, $jku = null)
    {
        return JWT::encode($payload, $privateKey, $signatureMethod, $kid);
    }

    /**
     * Get the public JWKS from a private key.
     *
     * @param string $privateKey       Private key in PEM format
     * @param string $signatureMethod  Signature method
     * @param string $kid              Key ID (optional)
     *
     * @return array  JWKS keys
     */
    public static function getJWKS($privateKey, $signatureMethod, $kid)
    {
        $res = openssl_pkey_get_private($privateKey);
        $details = openssl_pkey_get_details($res);
        $keys['keys'][] = [
            'kty' => 'RSA',
            'n' => JWT::urlsafeB64Encode($details['rsa']['n']),
            'e' => JWT::urlsafeB64Encode($details['rsa']['e']),
            'kid' => $kid,
            'alg' => $signatureMethod,
            'use' => 'sig'
        ];

        return $keys;
    }

###
###  PRIVATE METHOD
###

    /**
     * Fetch the public keys from a URL.
     *
     * @param string $jku     Endpoint for retrieving JSON web keys
     *
     * @return array    Array of keys
     */
    private function fetchPublicKey($jku)
    {
        $publicKey = array();
        $http = new HttpMessage($jku);
        if ($http->send()) {
            $keys = json_decode($http->response, true);
            $publicKey = JWK::parseKeySet($keys);
        }

        return $publicKey;
    }

}
