<?php

namespace ceLTIc\LTI\Jwt;

use Jose\Component\Core;
use Jose\Component\Signature;
use Jose\Component\KeyManagement;
use Jose\Component\Checker;
use Jose\Component\Encryption;
use ceLTIc\LTI\Util;
use ceLTIc\LTI\Http\HttpMessage;

//use ceLTIc\LTI\Http\PSR;

/**
 * Class to implement the JWT interface using the Web Token JWT Framework library from https://web-token.spomky-labs.com.
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license   GNU Lesser General Public License, version 3 (<http://www.gnu.org/licenses/lgpl.html>)
 */
class WebTokenClient implements ClientInterface
{

    private $jwe = null;
    private $jwt = null;
    private $claims = array();
    private static $lastHeaders = null;
    private static $lastPayload = null;

    /**
     * Check if a JWT is defined.
     *
     * @return bool True if a JWT is defined
     */
    public function hasJwt()
    {
        return !empty($this->jwt);
    }

    /**
     * Check if a JWT's content is encrypted.
     *
     * @return bool True if a JWT is encrypted
     */
    public function isEncrypted()
    {
        return !empty($this->jwe);
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
        $this->jwe = null;
        $this->jwt = null;
        $this->claims = null;
        try {
            $serializer = new Signature\Serializer\CompactSerializer();
            $this->jwt = $serializer->unserialize($jwtString);
        } catch (\Exception $e) {
            $serializer = new Encryption\Serializer\CompactSerializer();
            $this->jwt = $serializer->unserialize($jwtString);
        }
        if ($this->decrypt($privateKey)) {
            $this->claims = json_decode($this->jwt->getPayload(), true);
        }
    }

    /**
     * Get the value of the JWE headers.
     *
     * @return array The value of the JWE headers
     */
    public function getJweHeaders()
    {
        if ($this->isEncrypted()) {
            $headers = $this->jwe->getSharedProtectedHeader();
        } else {
            $headers = array();
        }

        return $headers;
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
        if ($this->jwt instanceof JWS) {
            $ok = $this->jwt->getSignature(0)->hasProtectedHeaderParameter($name);
        } else {
            $ok = false;
        }

        return $ok;
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
     * @return array The value of the headers
     */
    public function getHeaders()
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
        return array_key_exists($name, $this->claims);
    }

    /**
     * Get the value of the claim with the specified name.
     *
     * @param string $name  Claim name
     * @param string $defaultValue  Default value
     *
     * @return string The value of the claim with the specified name, or the default value if it does not exist
     */
    public function getClaim($name, $defaultValue = null)
    {
        if ($this->hasClaim($name)) {
            $value = $this->claims[$name];
            $value = json_decode(json_encode($value));
        } else {
            $value = $defaultValue;
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
        return $this->claims;
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
                $claimCheckerManager->check($this->claims);
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
                        if ((Jwt::$allowJkuHeader && $this->hasHeader('jku')) || (!empty($jku) && empty($publicKey))) {
//                            $psrClientInterface = new PSR\ClientInterface();
//                            $psrRequestInterface = new PSR\RequestInterface();
//                            $jkuFactory = new KeyManagement\JKUFactory($psrClientInterface, $psrRequestInterface);
                            if (Jwt::$allowJkuHeader && $this->hasHeader('jku')) {
//                                $jwks = $jkuFactory->loadFromUrl($this->getHeader('jku'));
                                $jwksUrl = $this->getHeader('jku');
                            } else {
//                                $jwks = $jkuFactory->loadFromUrl($jku);
                                $jwksUrl = $jku;
                            }
                            $jwks = $this->fetchPublicKey($jwksUrl);
                            $jwsVerifier->verifyWithKeySet($this->jwt, $jwks, 0);
//                            $jwk = $jwks->selectKey('sig', $this->getHeader('alg'), ['kid' => $this->getHeader('kid')]);
                        } else {
                            $json = json_decode($publicKey, true);
                            if (is_null($json)) {
                                $jwk = self::getJwk($publicKey, ['alg' => $this->getHeader('alg'), 'use' => 'sig']);
                            } else {
                                $jwk = new Core\JWK($json);
                            }
                            $jwsVerifier->verifyWithKey($this->jwt, $jwk, 0);
                        }
                        break;
                }
                $ok = true;
            } catch (\Exception $e) {
                Util::logError($e->getMessage());
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
     * @param array  $payload          Payload
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
        switch ($signatureMethod) {
            case 'RS512':
                $sig = new Signature\Algorithm\RS512();
                break;
            case 'RS384':
                $sig = new Signature\Algorithm\RS384();
                break;
            default:
                $signatureMethod = 'RS256';
                $sig = new Signature\Algorithm\RS256();
                break;
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
    public static function generateKey($signatureMethod = 'RS256')
    {
        return FirebaseClient::generateKey($signatureMethod);
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
        return FirebaseClient::getPublicKey($privateKey);
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
     * @param string $privateKey       Private key in PEM format
     */
    private function decrypt($privateKey)
    {
        $ok = true;
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
                $jwt = $this->jwt->getPayload();
                $serializer = new Signature\Serializer\CompactSerializer();
                $this->jwt = $serializer->unserialize($jwt);
            } else {
                $ok = false;
            }
        }

        return $ok;
    }

    /**
     * Get the JWK from a key in PEM or JWK format.
     *
     * @param string   $Key               Private or public key in PEM or JWK format
     * @param string[] $additionalValues  Additional values for key
     *
     * @return JWK  Key
     */
    private static function getJwk($key, $additionalValues)
    {
        $keyValues = json_decode($key, true);
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
     * @param string $jku     Endpoint for retrieving JSON web keys
     *
     * @return array    Array of keys
     */
    private function fetchPublicKey($jku)
    {
        $publicKey = array();
        $http = new HttpMessage($jku);
        if ($http->send()) {
            $keys = Core\Util\JsonConverter::decode($http->response);
            $publicKey = Core\JWKSet::createFromKeyData($keys);
        }

        return $publicKey;
    }

}
