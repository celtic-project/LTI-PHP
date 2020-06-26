<?php

namespace ceLTIc\LTI\Jwt;

/**
 * Interface to represent an HWT client
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license   GNU Lesser General Public License, version 3 (<http://www.gnu.org/licenses/lgpl.html>)
 */
interface ClientInterface
{

    /**
     * Check if a JWT is defined.
     *
     * @return bool True if a JWT is defined
     */
    public function hasJwt();

    /**
     * Load a JWT from a string.
     *
     * @param string $jwtString  JWT string
     *
     * @return bool True if the JWT was successfully loaded
     */
    public function load($jwtString);

    /**
     * Check whether a JWT has a header with the specified name.
     *
     * @param string $name  Header name
     *
     * @return bool True if the JWT has a header of the specified name
     */
    public function hasHeader($name);

    /**
     * Get the value of the header with the specified name.
     *
     * @param string $name  Header name
     * @param string $defaultValue  Default value
     *
     * @return string The value of the header with the specified name, or the default value if it does not exist
     */
    public function getHeader($name, $defaultValue = null);

    /**
     * Get the value of the headers.
     *
     * @return array The value of the headers
     */
    public function getHeaders();

    /**
     * Check whether a JWT has a claim with the specified name.
     *
     * @param string $name  Claim name
     *
     * @return bool True if the JWT has a claim of the specified name
     */
    public function hasClaim($name);

    /**
     * Get the value of the claim with the specified name.
     *
     * @param string $name  Claim name
     * @param string $defaultValue  Default value
     *
     * @return string The value of the claim with the specified name, or the default value if it does not exist
     */
    public function getClaim($name, $defaultValue = null);

    /**
     * Get the value of the payload.
     *
     * @return array The value of the payload
     */
    public function getPayload();

    /**
     * Verify the signature of the JWT.
     *
     * @param string $publicKey  Public key of issuer
     * @param string $jku        JSON Web Key URL of issuer (optional)
     *
     * @return bool True if the JWT has a valid signature
     */
    public function verify($publicKey, $jku = null);

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
    public static function sign($payload, $signatureMethod, $privateKey, $kid = null, $jku = null);

    /**
     * Get the public JWKS from a private key.
     *
     * @param string $privateKey       Private key in PEM format
     * @param string $signatureMethod  Signature method
     * @param string $kid              Key ID (optional)
     *
     * @return array  JWKS keys
     */
    public static function getJWKS($privateKey, $signatureMethod, $kid);
}
