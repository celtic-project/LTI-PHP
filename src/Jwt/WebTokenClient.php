<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Jwt;

use Jose\Component\Core;
use Jose\Component\Signature;
use Jose\Component\Signature\JWS;
use Jose\Component\KeyManagement;
use Jose\Component\Checker;
use Jose\Component\Encryption;
use Jose\Component\Encryption\JWE;
use ceLTIc\LTI\Util;
use ceLTIc\LTI\Http\HttpMessage;

/**
 * Class to implement the JWT interface using the Web Token JWT Framework library from https://web-token.spomky-labs.com.
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license   GNU Lesser General Public License, version 3 (<http://www.gnu.org/licenses/lgpl.html>)
 */
class WebTokenClient implements ClientInterface
{

    /**
     * Supported signature algorithms.
     */
    public const SUPPORTED_ALGORITHMS = ['RS256', 'RS384', 'RS512'];

    /**
     * Signed JSON web token.
     *
     * @var JWS $jwt
     */
    private ?JWS $jwt = null;

    /**
     * Encrypted JSON web token.
     *
     * @var JWE $jwe
     */
    private ?JWE $jwe = null;

    /**
     * Claims from JWT payload.
     *
     * @var object|null $claims
     */
    private ?object $claims = null;

    /**
     * Headers from last JSON web token.
     *
     * @var array|null $lastHeaders
     */
    private static ?array $lastHeaders = null;

    /**
     * Payload from last JSON web token.
     *
     * @var array|null $lastPayload
     */
    private static ?array $lastPayload = null;

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
        return !empty($this->jwt);
    }

    /**
     * Check if a JWT's content is encrypted.
     *
     * @return bool  True if a JWT is encrypted
     */
    public function isEncrypted(): bool
    {
        return !empty($this->jwe);
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
        $ok = true;
        $this->jwe = null;
        $this->jwt = null;
        $this->claims = null;
        try {
            $serializer = new Signature\Serializer\CompactSerializer();
            $this->jwt = $serializer->unserialize($jwtString);
        } catch (\Exception $e) {
            $ok = false;
        }
        if (!$ok) {
            try {
                $serializer = new Encryption\Serializer\CompactSerializer();
                $this->jwt = $serializer->unserialize($jwtString);
                $ok = $this->decrypt($privateKey);
            } catch (\Exception $e) {
                $ok = false;
            }
        }
        if ($ok) {
            $this->claims = Util::jsonDecode($this->jwt->getPayload());
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
        if ($this->isEncrypted()) {
            $headers = $this->jwe->getSharedProtectedHeader();
        } else {
            $headers = [];
        }

        return $headers;
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
        if ($this->jwt instanceof Signature\JWS) {
            $ok = $this->jwt->getSignature(0)->hasProtectedHeaderParameter($name);
        } else {
            $ok = false;
        }

        return $ok;
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
        try {
            $value = $this->jwt->getSignature(0)->getProtectedHeaderParameter($name);
        } catch (\Exception $e) {
            $value = $defaultValue;
        }

        return $value;
    }

    /**
     * Get the value of the headers.
     *
     * @return array  The value of the headers
     */
    public function getHeaders(): array|object
    {
        $headers = null;
        if ($this->jwt instanceof Signature\JWS) {
            $headers = $this->jwt->getSignature(0)->getProtectedHeader();
        }

        return $headers;
    }

    /**
     * Get the value of the headers for the last signed JWT (before any encryption).
     *
     * @return array  The value of the headers
     */
    public static function getLastHeaders(): array
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
        return isset($this->claims->{$name});
    }

    /**
     * Get the value of the claim with the specified name.
     *
     * @param string $name                                     Claim name
     * @param int|string|bool|array|object|null $defaultValue  Default value
     *
     * @return int|string|bool|array|object|null  The value of the claim with the specified name, or the default value if it does not exist
     */
    public function getClaim(string $name, int|string|bool|array|object|null $defaultValue = null): int|string|bool|array|object|null
    {
        if ($this->hasClaim($name)) {
            $value = $this->claims->{$name};
        } else {
            $value = $defaultValue;
        }

        return $value;
    }

    /**
     * Get the value of the payload.
     *
     * @return array  The value of the payload
     */
    public function getPayload(): array|object
    {
        return $this->claims;
    }

    /**
     * Get the value of the payload for the last signed JWT (before any encryption).
     *
     * @return array  The value of the payload
     */
    public static function getLastPayload(): array
    {
        return self::$lastPayload;
    }

    /**
     * Verify the signature of the JWT.
     *
     * @param string|null $publicKey  Public key of issuer
     * @param string|null $jku        JSON Web Key URL of issuer (optional)
     *
     * @return bool  True if the JWT has a valid signature
     */
    public function verify(?string $publicKey, ?string $jku = null): bool
    {
        $ok = false;
        $hasPublicKey = !empty($publicKey);
        $retry = false;
        $leeway = Jwt::$leeway;
        do {
            try {
                $claimCheckerManager = new Checker\ClaimCheckerManager(
                    [
                    new Checker\IssuedAtChecker($leeway),
                    new Checker\NotBeforeChecker($leeway),
                    new Checker\ExpirationTimeChecker($leeway)
                    ]
                );
                $claimCheckerManager->check(Util::jsonDecode($this->jwt->getPayload(), true));
                $algorithmManager = new Core\AlgorithmManager([
                    new Signature\Algorithm\RS256(),
                    new Signature\Algorithm\RS384(),
                    new Signature\Algorithm\RS512()
                ]);
                $jwsVerifier = new Signature\JWSVerifier(
                    $algorithmManager
                );
                switch ($this->getHeader('alg')) {
                    case 'RS256':
                    case 'RS384':
                    case 'RS512':
                        if ($this->hasHeader('kid') && ((Jwt::$allowJkuHeader && $this->hasHeader('jku')) || (!empty($jku) && empty($publicKey)))) {
                            if (Jwt::$allowJkuHeader && $this->hasHeader('jku')) {
                                $jwksUrl = $this->getHeader('jku');
                            } else {
                                $jwksUrl = $jku;
                            }
                            $jwks = $this->fetchPublicKey($jwksUrl, $this->getHeader('kid'));
                            $ok = $jwsVerifier->verifyWithKeySet($this->jwt, $jwks, 0);
                        } else {
                            $json = Util::jsonDecode($publicKey, true);
                            if (is_null($json)) {
                                $jwk = self::getJwk($publicKey, ['alg' => $this->getHeader('alg'), 'use' => 'sig']);
                            } else {
                                $jwk = new Core\JWK($json);
                            }
                            $ok = $jwsVerifier->verifyWithKey($this->jwt, $jwk, 0);
                        }
                        break;
                }
            } catch (\Exception $e) {
                Util::logError($e->getMessage());
            } catch (\TypeError $e) {
                Util::logError($e->getMessage());
            }
            if (!$ok) {
                if ($retry) {
                    $retry = false;
                } elseif ($hasPublicKey && !empty($jku)) {
                    $retry = true;
                    $publicKey = null;
                    $hasPublicKey = false;
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
        $sig = match ($signatureMethod) {
            'RS512' => new Signature\Algorithm\RS512(),
            'RS384' => new Signature\Algorithm\RS384(),
            default => null
        };
        if (empty($sig)) {
            $signatureMethod = 'RS256';
            $sig = new Signature\Algorithm\RS256();
        }
        $jwk = self::getJwk($privateKey, ['alg' => $signatureMethod, 'use' => 'sig']);
        $headers = ['typ' => 'JWT', 'alg' => $signatureMethod];
        if (!empty($kid)) {
            $headers['kid'] = $kid;
            if (!empty($jku)) {
                $headers['jku'] = $jku;
            }
        }
        $algorithmManager = new Core\AlgorithmManager(
            [
            new Signature\Algorithm\RS256(),
            new Signature\Algorithm\RS384(),
            new Signature\Algorithm\RS512()
            ]
        );
        $jwsBuilder = new Signature\JWSBuilder($algorithmManager);
        $jsonPayload = json_encode($payload);
        $jws = $jwsBuilder->create()
            ->withPayload($jsonPayload)
            ->addSignature($jwk, $headers)
            ->build();
        $serializer = new Signature\Serializer\CompactSerializer();
        $jwt = $serializer->serialize($jws);
        if (!empty($encryptionMethod)) {
            if (!empty($publicKey)) {
                $keyEnc = 'RSA-OAEP-256';
                $jwk = self::getJwk($publicKey, ['alg' => $keyEnc, 'use' => 'enc', 'zip' => 'DEF']);
                $keyEncryptionAlgorithmManager = new Core\AlgorithmManager([new Encryption\Algorithm\KeyEncryption\RSAOAEP256()]);
                $contentEncryptionAlgorithmManager = new Core\AlgorithmManager(
                    [
                    new Encryption\Algorithm\ContentEncryption\A128CBCHS256(),
                    new Encryption\Algorithm\ContentEncryption\A192CBCHS384(),
                    new Encryption\Algorithm\ContentEncryption\A256CBCHS512(),
                    ]
                );
                $compressionMethodManager = new Encryption\Compression\CompressionMethodManager([new Encryption\Compression\Deflate()]);
                $jweBuilder = new Encryption\JWEBuilder($keyEncryptionAlgorithmManager, $contentEncryptionAlgorithmManager,
                    $compressionMethodManager);
                $jwe = $jweBuilder
                    ->create()
                    ->withPayload($jwt)
                    ->withSharedProtectedHeader(['alg' => $keyEnc, 'enc' => $encryptionMethod, 'zip' => 'DEF'])
                    ->addRecipient($jwk)
                    ->build();
                $serializer = new Encryption\Serializer\CompactSerializer();
                $jwt = $serializer->serialize($jwe);
            } else {
                $errorMessage = 'No public key provided for encrypting JWT content';
                Util::logError($errorMessage);
                throw new \Exception($errorMessage);
            }
        }
        self::$lastHeaders = $headers;
        self::$lastPayload = $payload;

        return $jwt;
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
        return FirebaseClient::generateKey($signatureMethod);
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
        return FirebaseClient::getPublicKey($privateKey);
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
        $additionalValues = ['alg' => $signatureMethod, 'use' => 'sig'];
        if (!empty($kid)) {
            $additionalValues['kid'] = $kid;
        }
        try {
            $jwk = KeyManagement\JWKFactory::createFromKey($pemKey, null, $additionalValues);
            $jwk = $jwk->toPublic();
            $rsa = KeyManagement\KeyConverter\RSAKey::createFromJWK($jwk);
            $rsa = $rsa::toPublic($rsa);
            $keys['keys'][] = $rsa->toArray();
        } catch (\Exception $e) {

        }

        return $keys;
    }

###
###  PRIVATE METHODS
###

    /**
     * Decrypt the JWT.
     *
     * @param string $privateKey  Private key in PEM format
     *
     * @return bool  True if successful
     */
    private function decrypt(string $privateKey): bool
    {
        $ok = false;
        if ($this->jwt instanceof Encryption\JWE) {
            $this->jwe = clone $this->jwt;
            $keyEnc = $this->jwe->getSharedProtectedHeaderParameter('alg');
            $jwk = KeyManagement\JWKFactory::createFromKey($privateKey, null, ['alg' => $keyEnc, 'use' => 'enc']);
            $keyEncryptionAlgorithmManager = new Core\AlgorithmManager([new Encryption\Algorithm\KeyEncryption\RSAOAEP256()]);
            $contentEncryptionAlgorithmManager = new Core\AlgorithmManager(
                [
                new Encryption\Algorithm\ContentEncryption\A128CBCHS256(),
                new Encryption\Algorithm\ContentEncryption\A192CBCHS384(),
                new Encryption\Algorithm\ContentEncryption\A256CBCHS512()
                ]
            );
            $compressionMethodManager = new Encryption\Compression\CompressionMethodManager([new Encryption\Compression\Deflate()]);
            $jweDecrypter = new Encryption\JWEDecrypter($keyEncryptionAlgorithmManager, $contentEncryptionAlgorithmManager,
                $compressionMethodManager);
            if ($jweDecrypter->decryptUsingKey($this->jwt, $jwk, 0)) {
                try {
                    $jwt = $this->jwt->getPayload();
                    $serializer = new Signature\Serializer\CompactSerializer();
                    $this->jwt = $serializer->unserialize($jwt);
                    $ok = true;
                } catch (\Exception $e) {
                    $ok = false;
                }
            }
        }

        return $ok;
    }

    /**
     * Get the JWK from a key in PEM or JWK format.
     *
     * @param string   $key               Private or public key in PEM or JWK format
     * @param string[] $additionalValues  Additional values for key
     *
     * @return JWK  Key
     */
    private static function getJwk(string $key, array $additionalValues): Core\JWK
    {
        $keyValues = Util::jsonDecode($key, true);
        if (!is_array($keyValues)) {
            $jwk = KeyManagement\JWKFactory::createFromKey($key, null, $additionalValues);
        } else {
            $keyValues = array_merge($keyValues, $additionalValues);
            $jwk = new Core\JWK($keyValues);
        }

        return $jwk;
    }

    /**
     * Fetch the public keys from a URL.
     *
     * @param string $jku  Endpoint for retrieving JSON web keys
     * @param string $kid  Key ID
     *
     * @return array    Array of keys
     */
    private function fetchPublicKey(string $jku, string $kid): Core\JWKSet
    {
        $publicKey = null;
        $http = new HttpMessage($jku);
        if ($http->send()) {
            $keys = Core\Util\JsonConverter::decode($http->response);
            foreach ($keys['keys'] as $id => $key) {
                if (!isset($key['kid']) || ($key['kid'] !== $kid)) {
                    unset($keys['keys'][$id]);
                }
            }
            $publicKey = Core\JWKSet::createFromKeyData($keys);
        }

        return $publicKey;
    }

}
