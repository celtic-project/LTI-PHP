<?php
declare(strict_types=1);

namespace ceLTIc\LTI\OAuth;

/**
 * Class to represent an OAuth HMAC_SHA224 signature method
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @version  2015-11-30
 * @license  https://opensource.org/licenses/MIT The MIT License
 */

/**
 * The HMAC-SHA224 signature method uses the HMAC-SHA224 signature algorithm as defined in [RFC6234]
 * where the Signature Base String is the text and the key is the concatenated values (each first
 * encoded per Parameter Encoding) of the Consumer Secret and Token Secret, separated by an '&'
 * character (ASCII code 38) even if empty.
 */
class OAuthSignatureMethod_HMAC_SHA224 extends OAuthSignatureMethod
{

    /**
     * Name of the Signature Method.
     *
     * @return string
     */
    function get_name(): string
    {
        return 'HMAC-SHA224';
    }

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
    public function build_signature(OAuthRequest $request, OAuthConsumer $consumer, ?OAuthToken $token): string
    {
        $base_string = $request->get_signature_base_string();
        $request->base_string = $base_string;

        $key_parts = [
            $consumer->secret,
            ($token) ? $token->secret : ''
        ];

        $key_parts = OAuthUtil::urlencode_rfc3986($key_parts);
        $key = implode('&', $key_parts);

        return base64_encode(hash_hmac('sha224', $base_string, $key, true));
    }

}
