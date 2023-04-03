<?php
declare(strict_types=1);

namespace ceLTIc\LTI\OAuth;

/**
 * Class to represent an OAuth signature method
 *
 * @copyright  Andy Smith (http://oauth.googlecode.com/svn/code/php/)
 * @version  2008-08-04
 * @license  https://opensource.org/licenses/MIT The MIT License
 */

/**
 * A class for implementing a Signature Method
 * See section 9 ("Signing Requests") in the spec
 */
abstract class OAuthSignatureMethod
{

    /**
     * Needs to return the name of the Signature Method (eg HMAC-SHA1).
     *
     * @return string
     */
    abstract public function get_name(): string;

    /**
     * Build up the signature.
     *
     * NOTE: The output of this function MUST NOT be urlencoded.
     * the encoding is handled in OAuthRequest when the final
     * request is serialized
     *
     * @param OAuthRequest $request    Request
     * @param OAuthConsumer $consumer  Consumer
     * @param OAuthToken $token        Token
     *
     * @return string
     */
    abstract public function build_signature(OAuthRequest $request, OAuthConsumer $consumer, ?OAuthToken $token): string;

    /**
     * Verifies that a given signature is correct.
     *
     * @param OAuthRequest $request
     * @param OAuthConsumer $consumer
     * @param OAuthToken $token
     * @param string $signature
     *
     * @return bool
     */
    public function check_signature(OAuthRequest $request, OAuthConsumer $consumer, ?OAuthToken $token, string $signature): bool
    {
        $built = $this->build_signature($request, $consumer, $token);

        // Check for zero length, although unlikely here
        if ((strlen($built) === 0) || (strlen($signature) === 0)) {
            return false;
        }

        if (strlen($built) !== strlen($signature)) {
            return false;
        }

        // Avoid a timing leak with a (hopefully) time insensitive compare
        $result = 0;
        for ($i = 0; $i < strlen($signature); $i++) {
            $result |= ord($built[$i]) ^ ord($signature[$i]);
        }

        return $result === 0;
    }

}
