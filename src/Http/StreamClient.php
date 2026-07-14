<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Http;

use ceLTIc\LTI\Http\HttpMessage;

/**
 * Class to implement the HTTP message interface using a file stream
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license   GNU Lesser General Public License, version 3 (<http://www.gnu.org/licenses/lgpl.html>)
 */
class StreamClient implements ClientInterface
{

    /**
     * Send the request to the target URL.
     *
     * @param HttpMessage $message
     *
     * @return bool  True if the request was successful
     */
    public function send(HttpMessage $message): bool
    {
        $opts = [
            'method' => $message->getMethod(),
            'content' => $message->request,
            'header' => $message->requestHeaders,
            'ignore_errors' => true,
        ];

        $message->requestHeaders = array_merge(["{$message->getMethod()} {$message->getUrl()}"], $message->requestHeaders);
        try {
            $ctx = stream_context_create(['http' => $opts]);
            $fp = @fopen($message->getUrl(), 'rb', false, $ctx);
            if ($fp) {
                $resp = stream_get_contents($fp);
                $message->ok = $resp !== false;
                if ($message->ok) {
                    $message->response = $resp;
                    // see http://php.net/manual/en/reserved.variables.httpresponseheader.php
                    if (function_exists('http_get_last_response_headers')) {
                        $headers = http_get_last_response_headers();
                    } else {
                        $headers = $http_response_header;
                    }
                    if (!empty($headers)) {
                        $message->responseHeaders = $headers;
                        if (preg_match("/HTTP\/\d.\d\s+(\d+)(.*)/", $headers[0], $out)) {
                            $message->status = intval($out[1]);
                        }
                        $message->ok = $message->status < 400;
                        if (!$message->ok) {
                            if ((count($out) >= 2) && !empty(trim($out[2]))) {
                                $message->error = trim($out[2]);
                            } else {
                                $message->error = trim($headers[0]);
                            }
                        }
                    }
                    return $message->ok;
                }
            }
        } catch (\Exception $e) {
            $message->error = $e->getMessage();
            $message->ok = false;
            return false;
        }
        $message->error = error_get_last()["message"];
        $message->ok = false;
        return false;
    }

}
