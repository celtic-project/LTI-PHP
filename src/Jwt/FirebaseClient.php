<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Jwt;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;
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

    /**
     * Supported signature algorithms.
     */
    public const SUPPORTED_ALGORITHMS = ['RS256', 'RS384', 'RS512', 'ES256', 'ES384'];

    /**
     * JSON web token string.
     *
     * @var string|null $jwtString
     */
    private ?string $jwtString = null;

    /**
     * Headers from JSON web token.
     *
     * @var object|null $jwtHeaders
     */
    private ?object $jwtHeaders = null;

    /**
     * Payload from JSON web token.
     *
     * @var object|null $jwtHeaders
     */
    private ?object $jwtPayload = null;

    /**
     * Headers from last JSON web token.
     *
     * @var object|null $lastHeaders
     */
    private static ?object $lastHeaders = null;

    /**
     * Payload from last JSON web token.
     *
     * @var object|null $lastPayload
     */
    private static ?object $lastPayload = null;

    /**
     * Return an array of supported signature algorithms.
     *
     * @return string[]  Array of algorithm names
     */
    public static function getSupportedAlgorithms(): array
    {
        return self::SUPPORTED_ALGORITHMS;
    }

    /**
     * Check if a JWT is defined.
     *
     * @return bool  True if a JWT is defined
     */
    public function hasJwt(): bool
    {
        return !empty($this->jwtString);
    }

    /**
     * Check if a JWT's content is encrypted.
     *
     * @return bool  True if a JWT is encrypted
     */
    public function isEncrypted(): bool
    {
        return false;  // Not supported by this client
    }

    /**
     * Load a JWT from a string.
     *
     * @param string $jwtString        JWT string
     * @param string|null $privateKey  Private key in PEM format for decrypting encrypted tokens (optional)
     *
     * @return bool  True if the JWT was successfully loaded
     */
    public function load(string $jwtString, ?string $privateKey = null): bool
    {
        $sections = explode('.', $jwtString);
        $ok = count($sections) === 3;
        if ($ok) {
            $headers = Util::jsonDecode(JWT::urlsafeB64Decode($sections[0]));
            $payload = Util::jsonDecode(JWT::urlsafeB64Decode($sections[1]));
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
     * @return array  The value of the JWE headers
     */
    public function getJweHeaders(): array
    {
        return [];  // Encryption not supported by this client
    }

    /**
     * Check whether a JWT has a header with the specified name.
     *
     * @param string $name  Header name
     *
     * @return bool  True if the JWT has a header of the specified name
     */
    public function hasHeader(string $name): bool
    {
        return !empty($this->jwtHeaders) && isset($this->jwtHeaders->{$name});
    }

    /**
     * Get the value of the header with the specified name.
     *
     * @param string $name               Header name
     * @param string|null $defaultValue  Default value
     *
     * @return string|null  The value of the header with the specified name, or the default value if it does not exist
     */
    public function getHeader(string $name, ?string $defaultValue = null): ?string
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
     * @return array|object|null  The value of the headers
     */
    public function getHeaders(): array|object|null
    {
        return $this->jwtHeaders;
    }

    /**
     * Get the value of the headers for the last signed JWT (before any encryption).
     *
     * @return array|object|null  The value of the headers
     */
    public static function getLastHeaders(): array|object|null
    {
        return self::$lastHeaders;
    }

    /**
     * Check whether a JWT has a claim with the specified name.
     *
     * @param string $name  Claim name
     *
     * @return bool  True if the JWT has a claim of the specified name
     */
    public function hasClaim(string $name): bool
    {
        return !empty($this->jwtPayload) && isset($this->jwtPayload->{$name});
    }

    /**
     * Get the value of the claim with the specified name.
     *
     * @param string $name                                           Claim name
     * @param int|float|string|bool|array|object|null $defaultValue  Default value
     *
     * @return int|float|string|bool|array|object|null  The value of the claim with the specified name, or the default value if it does not exist
     */
    public function getClaim(string $name, int|float|string|bool|array|object|null $defaultValue = null): int|float|string|bool|array|object|null
    {
        if ($this->hasClaim($name)) {
            $value = $this->jwtPayload->{$name};
        } else {
            $value = $defaultValue;
        }

        return $value;
    }

    /**
     * Get the value of the payload.
     *
     * @return array|object|null  The value of the payload
     */
    public function getPayload(): array|object|null
    {
        return $this->jwtPayload;
    }

    /**
     * Get the value of the payload for the last signed JWT (before any encryption).
     *
     * @return array|object|null  The value of the payload
     */
    public static function getLastPayload(): array|object|null
    {
        return self::$lastPayload;
    }

    /**
     * Verify the signature of the JWT.
     *
     * @deprecated Use verifySignature() instead
     *
     * @param string|null $publicKey  Public key of issuer
     * @param string|null $jku        JSON Web Key URL of issuer (optional)
     *
     * @return bool  True if the JWT has a valid signature
     */
    public function verify(?string $publicKey, ?string $jku = null): bool
    {
        Util::logDebug('Method ceLTIc\LTI\Jwt\FirebaseClient->verify has been deprecated; please use ceLTIc\LTI\Jwt\FirebaseClient->verifySignature instead.',
            true);
        return $this->verifySignature($publicKey, $jku);
    }

    /**
     * Verify the signature of the JWT.
     *
     * If a new public key is fetched and used to successfully verify the signature, the value of the publicKey parameter is updated.
     *
     * @param string|null $publicKey  Public key of issuer (passed by reference)
     * @param string|null $jku        JSON Web Key URL of issuer (optional)
     *
     * @return bool  True if the JWT has a valid signature
     */
    public function verifySignature(?string &$publicKey, ?string $jku = null): bool
    {
        $ok = false;
        $hasPublicKey = !empty($publicKey);
        if ($hasPublicKey) {
            $json = Util::jsonDecode($publicKey, true);
            if (!is_null($json)) {
                try {
                    $jwks = [
                        'keys' => [$json]
                    ];
                    $jwk = JWK::parseKeySet($jwks, $this->getHeader('alg'));
                } catch (\Exception $e) {

                }
            } else {
                $jwk = new Key($publicKey, $this->getHeader('alg'));
            }
        } elseif (!empty($jku)) {
            $jwk = $this->fetchPublicKey($jku);
        }
        JWT::$leeway = Jwt::$leeway;
        $retry = false;
        do {
            try {
                JWT::decode($this->jwtString, $jwk);
                $ok = true;
                if (!$hasPublicKey || $retry) {
                    $key = openssl_pkey_get_public($jwk[$this->getHeader('kid')]->getKeyMaterial());
                    $keyDetails = openssl_pkey_get_details($key);
                    if ($keyDetails !== false) {
                        $publicKey = str_replace("\n", "\r\n", $keyDetails['key']);
                    }
                }
            } catch (\Exception | \TypeError $e) {
                if ($retry) {
                    Util::logError($e->getMessage());
                    $retry = false;
                } elseif ($hasPublicKey && !empty($jku)) {
                    Util::logDebug($e->getMessage() . ' [will retry]');
                    try {
                        $key = $this->fetchPublicKey($jku);
                        $retry = !empty($key);
                    } catch (\Exception $e) {

                    }
                }
            }
        } while (!$ok && $retry);

        return $ok;
    }

    /**
     * Sign the JWT.
     *
     * @param array $payload                 Payload
     * @param string $signatureMethod        Signature method
     * @param string $privateKey             Private key in PEM format
     * @param string|null $kid               Key ID (optional)
     * @param string|null $jku               JSON Web Key URL (optional)
     * @param string|null $encryptionMethod  Encryption method (optional)
     * @param string|null $publicKey         Public key of recipient for content encryption (optional)
     *
     * @return string  Signed JWT
     * @throws Exception
     */
    public static function sign(array $payload, string $signatureMethod, string $privateKey, ?string $kid = null,
        ?string $jku = null, ?string $encryptionMethod = null, ?string $publicKey = null): string
    {
        if (!empty($encryptionMethod)) {
            $errorMessage = 'Encrypted tokens not supported by the Firebase JWT client';
            Util::logError($errorMessage);
            throw new \Exception($errorMessage);
        }
        $jwtString = JWT::encode($payload, $privateKey, $signatureMethod, $kid);
        $sections = explode('.', $jwtString);
        self::$lastHeaders = Util::jsonDecode(JWT::urlsafeB64Decode($sections[0]));
        self::$lastPayload = Util::jsonDecode(JWT::urlsafeB64Decode($sections[1]));

        return $jwtString;
    }

    /**
     * Generate a new private key in PEM format.
     *
     * @param string $signatureMethod  Signature method
     *
     * @return string|null  Key in PEM format
     */
    public static function generateKey(string $signatureMethod = 'RS256'): ?string
    {
        $privateKey = null;
        $config = match ($signatureMethod) {
            'ES512' => [
                'private_key_type' => OPENSSL_KEYTYPE_EC,
                'curve_name' => 'secp521r1'
            ],
            'ES384' => [
                'private_key_type' => OPENSSL_KEYTYPE_EC,
                'curve_name' => 'secp384r1'
            ],
            'ES256' => [
                'private_key_type' => OPENSSL_KEYTYPE_EC,
                'curve_name' => 'prime256v1' // 'secp256k1'
            ],
            'RS512' => [
                'private_key_bits' => 4096,
                'private_key_type' => OPENSSL_KEYTYPE_RSA
            ],
            'RS384' => [
                'private_key_bits' => 3072,
                'private_key_type' => OPENSSL_KEYTYPE_RSA
            ],
            default => [
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA
            ]
        };
        $res = openssl_pkey_new($config);
        if ($res !== false) {
            openssl_pkey_export($res, $privateKey);
        }

        return $privateKey;
    }

    /**
     * Get the public key for a private key.
     *
     * @param string $privateKey  Private key in PEM format
     *
     * @return string|null  Public key in PEM format
     */
    public static function getPublicKey(string $privateKey): ?string
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
     * @param string|null $kid         Key ID (optional)
     *
     * @return array  JWKS keys
     */
    public static function getJWKS(string $pemKey, string $signatureMethod, ?string $kid = null): array
    {
        $keys['keys'] = [];
        $res = openssl_pkey_get_private($pemKey);
        if ($res === false) {
            $res = openssl_pkey_get_public($pemKey);
        }
        if ($res !== false) {
            $details = openssl_pkey_get_details($res);
            if (isset($details['rsa']) && isset($details['rsa']['n']) && isset($details['rsa']['e'])) {
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
            } elseif (isset($details['ec']) && isset($details['ec']['x']) && isset($details['ec']['y']) && isset($details['ec']['curve_name'])) {
                $key = [
                    'kty' => 'EC',
                    'crv' => $details['ec']['curve_name'],
                    'x' => JWT::urlsafeB64Encode($details['ec']['x']),
                    'y' => JWT::urlsafeB64Encode($details['ec']['y']),
                    'alg' => $signatureMethod,
                    'use' => 'sig'
                ];
                if (!empty($kid)) {
                    $key['kid'] = $kid;
                }
                $keys['keys'][] = $key;
            }
        }

        return $keys;
    }

###
###  PRIVATE METHODS
###

    /**
     * Fetch the public keys from a URL.
     *
     * @param string $jku  Endpoint for retrieving JSON web keys
     *
     * @return array  Array of keys
     */
    private function fetchPublicKey(string $jku): array
    {
        $publicKey = [];
        $http = new HttpMessage($jku);
        if ($http->send()) {
            $keys = Util::jsonDecode($http->response, true);
            if (is_array($keys) && isset($keys['keys'])) {
                $publicKeys = ['keys' => []];
                foreach ($keys['keys'] as $key) {
                    if (isset($key['kid']) && ($key['kid'] === $this->getHeader('kid'))) {
                        if ($key['kty'] === 'EC') {
                            switch ($key['crv']) {
                                case 'prime256v1':
                                case 'secp256r1':
                                    $key['crv'] = 'P-256';
                                    break;
                                case 'ansip256k1':
                                    $key['crv'] = 'secp256k1';
                                    break;
                                case 'ansip384r1':
                                case 'secp384r1':
                                    $key['crv'] = 'P-384';
                                    break;
                            }
                        }
                        $publicKeys['keys'][] = $key;
                        try {
                            $keys = JWK::parseKeySet($publicKeys, $this->getHeader('alg'));
                            if (array_key_exists($this->getHeader('kid'), $keys)) {
                                $publicKey[$this->getHeader('kid')] = $keys[$this->getHeader('kid')];
                            }
                        } catch (\Exception $e) {

                        }
                        break;
                    }
                }
            }
            if (empty($publicKey)) {
                Util::logError("Public key not found for kid: '{$this->getHeader('kid')}'");
            }
        }

        return $publicKey;
    }

}
