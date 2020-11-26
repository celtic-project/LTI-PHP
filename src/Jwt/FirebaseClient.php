<?php

namespace ceLTIc\LTI\Jwt;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use ceLTIc\LTI\Http\HttpMessage;
use ceLTIc\LTI\Util;

/**
 * Class to implement the JWT interface using the Firebase JWT library from https://github.com/firebase/php-jwt.
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license   GNU Lesser General Public License, version 3 (<http://www.gnu.org/licenses/lgpl.html>)
 */
class FirebaseClient implements ClientInterface
{

    const SUPPORTED_ALGORITHMS = array('RS256', 'RS384', 'RS512');

    private $jwtString = null;
    private $jwtHeaders = null;
    private $jwtPayload = null;
    private static $lastHeaders = null;
    private static $lastPayload = null;

    /**
     * Return an array of supported signature algorithms.
     *
     * @return string[]  Array of algorithm names
     */
    public static function getSupportedAlgorithms()
    {
        return self::SUPPORTED_ALGORITHMS;
    }

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
     * Check if a JWT's content is encrypted.
     *
     * @return bool True if a JWT is encrypted
     */
    public function isEncrypted()
    {
        return false;  // Not supported by this client
    }

    /**
     * Load a JWT from a string.
     *
     * @param string $jwtString  JWT string
     * @param string $privateKey Private key in PEM format for decrypting encrypted tokens (optional)
     *
     * @return bool True if the JWT was successfully loaded
     */
    public function load($jwtString, $privateKey = null)
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
     * Get the value of the JWE headers.
     *
     * @return array The value of the JWE headers
     */
    public function getJweHeaders()
    {
        return array();  // Encryption not supported by this client
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
     * Get the value of the headers for the last signed JWT (before any encryption).
     *
     * @return array The value of the headers
     */
    public static function getLastHeaders()
    {
        return self::$lastHeaders;
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
     * Get the value of the payload for the last signed JWT (before any encryption).
     *
     * @return array The value of the payload
     */
    public static function getLastPayload()
    {
        return self::$lastPayload;
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
        JWT::$leeway = Jwt::$leeway;
        $retry = false;
        do {
            try {
                JWT::decode($this->jwtString, $publicKey, self::SUPPORTED_ALGORITHMS);
                $ok = true;
            } catch (\Exception $e) {
                Util::logError($e->getMessage());
                if ($retry) {
                    $retry = false;
                } elseif ($hasPublicKey && !empty($jku)) {
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
     * @param string $jku              JSON Web Key URL (optional)
     * @param string $encryptionMethod Encryption method (optional)
     * @param string $publicKey        Public key of recipient for content encryption (optional)
     *
     * @return string Signed JWT
     */
    public static function sign($payload, $signatureMethod, $privateKey, $kid = null, $jku = null, $encryptionMethod = null,
        $publicKey = null)
    {
        if (!empty($encryptionMethod)) {
            $errorMessage = 'Encrypted tokens not supported by the Firebase JWT client';
            Util::logError($errorMessage);
            throw new \Exception($errorMessage);
        }
        $jwtString = JWT::encode($payload, $privateKey, $signatureMethod, $kid);
        $sections = explode('.', $jwtString);
        self::$lastHeaders = json_decode(JWT::urlsafeB64Decode($sections[0]));
        self::$lastPayload = json_decode(JWT::urlsafeB64Decode($sections[1]));

        return $jwtString;
    }

    /**
     * Generate a new private key in PEM format.
     *
     * @param string $signatureMethod  Signature method
     *
     * @return string|null  Key in PEM format
     */
    public static function generateKey($signatureMethod = 'RS256')
    {
        $privateKey = null;
        switch ($signatureMethod) {
            case 'RS512':
                $size = 4096;
                break;
            case 'RS384':
                $size = 3072;
                break;
            default:
                $size = 2048;
                break;
        }
        $config = array(
            "private_key_bits" => $size,
            "private_key_type" => OPENSSL_KEYTYPE_RSA
        );
        $res = openssl_pkey_new($config);
        if (openssl_pkey_export($res, $privateKey)) {
            $privateKey = str_replace('-----BEGIN PRIVATE KEY-----', '-----BEGIN RSA PRIVATE KEY-----', $privateKey);
            $privateKey = str_replace('-----END PRIVATE KEY-----', '-----END RSA PRIVATE KEY-----', $privateKey);
        }

        return $privateKey;
    }

    /**
     * Get the public key for a private key.
     *
     * @param string $privateKey       Private key in PEM format
     *
     * @return string Public key in PEM format
     */
    public static function getPublicKey($privateKey)
    {
        $publicKey = null;
        $res = openssl_pkey_get_private($privateKey);
        if ($res !== false) {
            $details = openssl_pkey_get_details($res);
            $publicKey = $details['key'];
        }

        return $publicKey;
    }

    /**
     * Get the public JWKS from a key in PEM format.
     *
     * @param string $pemKey           Private or public key in PEM format
     * @param string $signatureMethod  Signature method
     * @param string $kid              Key ID (optional)
     *
     * @return array  JWKS keys
     */
    public static function getJWKS($pemKey, $signatureMethod, $kid)
    {
        $keys['keys'] = array();
        $res = openssl_pkey_get_private($pemKey);
        if ($res === false) {
            $res = openssl_pkey_get_public($pemKey);
        }
        if ($res !== false) {
            $details = openssl_pkey_get_details($res);
            $key = [
                'kty' => 'RSA',
                'n' => JWT::urlsafeB64Encode($details['rsa']['n']),
                'e' => JWT::urlsafeB64Encode($details['rsa']['e']),
                'alg' => $signatureMethod,
                'use' => 'sig'
            ];
            if (!empty($kid)) {
                $key['kid'] = $kid;
            }
            $keys['keys'][] = $key;
        }

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
