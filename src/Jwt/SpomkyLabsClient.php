<?php

namespace ceLTIc\LTI\Jwt;

use Jose;
use Jose\Object\JWK;
use Jose\Object\JWE;
use Jose\Object\JWS;
use Jose\Factory\JWKFactory;
use Jose\KeyConverter\RSAKey;
use Jose\Algorithm\Signature;
use Jose\Checker\CheckerManager;
use Jose\Checker\ExpirationTimeChecker;
use Jose\Checker\IssuedAtChecker;
use Jose\Checker\NotBeforeChecker;
use Base64Url\Base64Url;
use ceLTIc\LTI\Util;

/**
 * Class to implement the JWT interface using the Spomky-Labs JWT library from https://github.com/Spomky-Labs/jose.
 *
 * @deprecated Use WebTokenClient instead
 * @see WebTokenClient
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license   GNU Lesser General Public License, version 3 (<http://www.gnu.org/licenses/lgpl.html>)
 */
class SpomkyLabsClient implements ClientInterface
{

    /**
     * Supported signature algorithms.
     */
    const SUPPORTED_ALGORITHMS = array('RS256', 'RS384', 'RS512');

    /**
     * Encrypted JSON web token.
     *
     * @var JWE $jwe
     */
    private $jwe = null;

    /**
     * Signed JSON web token.
     *
     * @var JWS $jwt
     */
    private $jwt = null;

    /**
     * JWT payload.
     *
     * @var object|null $payload
     */
    private $payload = null;

    /**
     * Headers from last JSON web token.
     *
     * @var array|null $lastHeaders
     */
    private static $lastHeaders = null;

    /**
     * Payload from last JSON web token.
     *
     * @var array|null $lastPayload
     */
    private static $lastPayload = null;

    /**
     * Class constructor.
     */
    public function __construct()
    {
        Util::logDebug('Class ceLTIc\LTI\Jwt\SpomkyLabsClient has been deprecated; please try using ceLTIc\LTI\Jwt\WebTokenClient instead.',
            true);
    }

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
     * @param string|null $privateKey Private key in PEM format for decrypting encrypted tokens (optional)
     *
     * @return bool True if the JWT was successfully loaded
     */
    public function load($jwtString, $privateKey = null)
    {
        $ok = true;
        $this->jwe = null;
        $this->jwt = null;
        try {
            $loader = new Jose\Loader();
            $this->jwt = $loader->load($jwtString);
            $parts = explode('.', $jwtString);
            if (count($parts) >= 2) {
                $this->payload = Util::jsonDecode(Base64Url::decode($parts[1]));
            }
            $this->decrypt($privateKey);
        } catch (\Exception $e) {
            $ok = false;
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
        if ($this->isEncrypted()) {
            $headers = $this->jwe->getSharedProtectedHeaders();
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
            $ok = $this->jwt->getSignature(0)->hasProtectedHeader($name);
        } else {
            $ok = false;
        }

        return $ok;
    }

    /**
     * Get the value of the header with the specified name.
     *
     * @param string $name  Header name
     * @param string|null $defaultValue  Default value
     *
     * @return string|null The value of the header with the specified name, or the default value if it does not exist
     */
    public function getHeader($name, $defaultValue = null)
    {
        try {
            $value = $this->jwt->getSignature(0)->getProtectedHeader($name);
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
        if ($this->jwt instanceof JWS) {
            $headers = $this->jwt->getSignature(0)->getProtectedHeaders();
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
        return isset($this->payload->{$name});
    }

    /**
     * Get the value of the claim with the specified name.
     *
     * @param string $name  Claim name
     * @param int|string|bool|array|object|null $defaultValue  Default value
     *
     * @return int|string|bool|array|object|null The value of the claim with the specified name, or the default value if it does not exist
     */
    public function getClaim($name, $defaultValue = null)
    {
        if ($this->hasClaim($name)) {
            $value = $this->payload->{$name};
        } else {
            $value = defaultValue;
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
        return $this->payload;
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
     * @param string|null $publicKey  Public key of issuer
     * @param string|null $jku        JSON Web Key URL of issuer (optional)
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
                $checkerManager = new CheckerManager();
                $checkerManager->addClaimChecker(new ExpirationTimeChecker($leeway));
                $checkerManager->addClaimChecker(new IssuedAtChecker($leeway));
                $checkerManager->addClaimChecker(new NotBeforeChecker($leeway));
                $checkerManager->checkJWS($this->jwt, 0);
                $verifier = new Jose\Verifier(['RS256', 'RS384', 'RS512']);
                switch ($this->getHeader('alg')) {
                    case 'RS256':
                    case 'RS384':
                    case 'RS512':
                        if ((Jwt::$allowJkuHeader && $this->hasHeader('jku')) || (!empty($jku) && empty($publicKey))) {
                            if (Jwt::$allowJkuHeader && $this->hasHeader('jku')) {
                                $jwks = JWKFactory::createFromJKU($this->getHeader('jku'), true, null, 86400, true);
                            } else {
                                $jwks = JWKFactory::createFromJKU($jku, true, null, 86400, true);
                            }
                            $verifier->verifyWithKeySet($this->jwt, $jwks);
                            $jwk = $jwks->selectKey('sig', $this->getHeader('alg'), ['kid' => $this->getHeader('kid')]);
                        } else {
                            $jwk = self::getJwk($publicKey, ['alg' => $this->getHeader('alg'), 'use' => 'sig']);
                            $verifier->verifyWithKey($this->jwt, $jwk);
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
     * @param string|null $kid              Key ID (optional)
     * @param string|null $jku              JSON Web Key URL (optional)
     * @param string|null $encryptionMethod Encryption method (optional)
     * @param string|null $publicKey        Public key of recipient for content encryption (optional)
     *
     * @return string Signed JWT
     * @throws Exception
     */
    public static function sign($payload, $signatureMethod, $privateKey, $kid = null, $jku = null, $encryptionMethod = null,
        $publicKey = null)
    {
        switch ($signatureMethod) {
            case 'RS512':
                $signature = new Signature\RS512();
                break;
            case 'RS384':
                $signature = new Signature\RS384();
                break;
            default:
                $signatureMethod = 'RS256';
                $signature = new Signature\RS256();
                break;
        }
        $jwk = self::getJwk($privateKey, ['alg' => $signatureMethod, 'use' => 'sig']);
        $signer = new Jose\Signer([$signature]);
        $jwtCreator = new Jose\JWTCreator($signer);
        $headers = ['typ' => 'JWT', 'alg' => $signatureMethod];
        if (!empty($kid)) {
            $headers['kid'] = $kid;
            if (!empty($jku)) {
                $headers['jku'] = $jku;
            }
        }
        if (empty($encryptionMethod)) {
            $jws = $jwtCreator->sign($payload, $headers, $jwk);
        } elseif (!empty($publicKey)) {
            $keyEnc = 'RSA-OAEP-256';
            $encHeaders = ['use' => 'enc', 'alg' => $keyEnc, 'enc' => $encryptionMethod, 'zip' => 'DEF'];
            $encJwk = self::getJwk($publicKey, $encHeaders);
            $encrypter = Jose\Encrypter::createEncrypter([$keyEnc], [$encryptionMethod], ['DEF']);
            $jwtCreator->enableEncryptionSupport($encrypter);
            $jws = $jwtCreator->signAndEncrypt($payload, $headers, $jwk, $encHeaders, $encJwk);
        } else {
            $errorMessage = 'No public key provided for encrypting JWT content';
            Util::logError($errorMessage);
            throw new \Exception($errorMessage);
        }
        self::$lastHeaders = $headers;
        self::$lastPayload = $payload;

        return $jws;
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
        switch ($signatureMethod) {
            case 'RS512':
                $size = 4096;
                break;
            case 'RS384':
                $size = 3072;
                break;
            case 'RS256':
            default:
                $size = 2048;
                $signatureMethod = 'RS256';
                break;
        }
        $jwk = JWKFactory::createKey([
                'kty' => 'RSA',
                'size' => $size,
                'alg' => $signatureMethod,
                'use' => 'sig',
        ]);
        $rsa = new RSAKey($jwk);
        $privateKey = $rsa->toPEM();

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
        try {
            $jwk = self::getJwk($privateKey, ['use' => 'sig']);
            $jwk = $jwk->toPublic();
            $rsa = new RSAKey($jwk);
            $publicKey = $rsa->toPEM();
        } catch (\Exception $e) {

        }

        return $publicKey;
    }

    /**
     * Get the public JWKS from a key in PEM or JWK format.
     *
     * @param string $key              Private or public key in PEM or JWK format
     * @param string $signatureMethod  Signature method
     * @param string|null $kid              Key ID (optional)
     *
     * @return array  JWKS keys
     */
    public static function getJWKS($key, $signatureMethod, $kid)
    {
        $keys['keys'] = array();
        $additionalValues = ['alg' => $signatureMethod, 'use' => 'sig'];
        if (!empty($kid)) {
            $additionalValues['kid'] = $kid;
        }
        try {
            $jwk = self::getJwk($key, $additionalValues);
            $jwk = $jwk->toPublic();
            $rsa = new RSAKey($jwk);
            $rsa = $rsa::toPublic($rsa);
            $keys['keys'][] = $rsa->toArray();
        } catch (\Exception $e) {

        }

        return $keys;
    }

###
###    PRIVATE METHODS
###

    /**
     * Decrypt the JWT.
     *
     * @param string $privateKey       Private key in PEM format
     *
     * @return bool  True if successful
     */
    private function decrypt($privateKey)
    {
        if ($this->jwt instanceof JWE) {
            $this->jwe = clone $this->jwt;
            $keyEnc = $this->jwe->getSharedProtectedHeader('alg');
            $encryptionMethod = $this->jwe->getSharedProtectedHeader('enc');
            $jwk = self::getJwk($privateKey, ['alg' => $keyEnc, 'use' => 'enc']);
            $decrypter = Jose\Decrypter::createDecrypter([$keyEnc], [$encryptionMethod], ['DEF', 'GZ', 'ZLIB']);
            $decrypter->decryptUsingKey($this->jwt, $jwk);
            $jwtString = $this->jwt->getPayload();
            $loader = new Jose\Loader();
            $this->jwt = $loader->load($jwtString);
            $parts = explode('.', $jwtString);
            if (count($parts) >= 2) {
                $this->payload = Util::jsonDecode(Base64Url::decode($parts[1]));
            }
            $ok = $this->jwt instanceof JWS;
        }
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
        $keyValues = Util::jsonDecode($key, true);
        if (!is_array($keyValues)) {
            $jwk = JWKFactory::createFromKey($key, null, $additionalValues);
        } else {
            $keyValues = array_merge($keyValues, $additionalValues);
            $jwk = new JWK($keyValues);
        }

        return $jwk;
    }

}
