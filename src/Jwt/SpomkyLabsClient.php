<?php

namespace ceLTIc\LTI\Jwt;

use Jose;
use Jose\Object\JWK;
use Jose\Factory\JWKFactory;
use Jose\KeyConverter;
use Jose\Algorithm\Signature;
use ceLTIc\LTI\Util;

/**
 * Class to implement the JWT interface using the Spomky-Labs library.
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license   GNU Lesser General Public License, version 3 (<http://www.gnu.org/licenses/lgpl.html>)
 */
class SpomkyLabsClient implements ClientInterface
{

    private $jwt = null;

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
     * Load a JWT from a string.
     *
     * @param string $jwtString  JWT string
     *
     * @return bool True if the JWT was successfully loaded
     */
    public function load($jwtString)
    {
        $loader = new Jose\Loader();
        $this->jwt = $loader->load($jwtString);
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
        return $this->jwt->getSignature(0)->hasProtectedHeader($name);
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
        return $this->jwt->getSignature(0)->getProtectedHeaders();
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
        return $this->jwt->hasClaim($name);
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
        try {
            $value = $this->jwt->getClaim($name);
            $value = json_decode(json_encode($value));
        } catch (\Exception $e) {
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
        return $this->jwt->getPayload();
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
        do {
            try {
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
                            $json = json_decode($publicKey, true);
                            if (is_null($json)) {
                                $jwk = JWKFactory::createFromKey($publicKey, null,
                                        [
                                            'alg' => $this->getHeader('alg'),
                                            'use' => 'sig'
                                        ]
                                );
                            } else {
                                $jwk = new JWK($json);
                            }
                            $verifier->verifyWithKey($this->jwt, $jwk);
                        }
                        break;
                }
                $jwk = $jwk->toPublic();
                $rsa = new KeyConverter\RSAKey($jwk);
                $rsa->toPEM();
                $ok = true;
            } catch (\Exception $e) {
                Util::logError($e->getMessage());
                if (!$retry && $hasPublicKey && !empty($jku)) {
                    $retry = true;
                    $publicKey = null;
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
        switch ($signatureMethod) {
            case 'RS512':
                $sig = new Signature\RS512();
                break;
            case 'RS384':
                $sig = new Signature\RS384();
                break;
            default:
                $signatureMethod = 'RS256';
                $sig = new Signature\RS256();
                break;
        }
        try {
            $jwk = JWKFactory::createFromKey($privateKey, null,
                    [
                        'alg' => $signatureMethod,
                        'use' => 'sig'
                    ]
            );
        } catch (\Exception $e) {
            $ok = false;
        }
        $headers = ['typ' => 'JWT', 'alg' => $signatureMethod];
        if (!empty($kid)) {
            $headers['kid'] = $kid;
            if (!empty($jku)) {
                $headers['jku'] = $jku;
            }
        }
        $signer = new \Jose\Signer([$sig]);
        $jwt_creator = new \Jose\JWTCreator($signer);

        return $jwt_creator->sign($payload, $headers, $jwk);
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
        $keys['keys'] = array();
        try {
            $jwk = JWKFactory::createFromKey($privateKey, null, ['alg' => $signatureMethod, 'kid' => $kid, 'use' => 'sig']);
            $jwk = $jwk->toPublic();
            $rsa = new KeyConverter\RSAKey($jwk);
            $rsa = $rsa::toPublic($rsa);
            $keys['keys'][] = $rsa->toArray();
        } catch (Exception $e) {

        }

        return $keys;
    }

}
