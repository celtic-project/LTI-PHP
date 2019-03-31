<?php

namespace ceLTIc\LTI\Http;

use ceLTIc\LTI\Http\HttpMessage;

/**
 * Class to implement the HTTP message interface using a file stream
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @version  3.1.0
 * @license   GNU Lesser General Public License, version 3 (<http://www.gnu.org/licenses/lgpl.html>)
 */
class StreamClient implements ClientInterface
{

    /**
     * Send the request to the target URL.
     *
     * @param HttpMessage $message
     *
     * @return bool True if the request was successful
     */
    public function send(HttpMessage $message)
    {
        if (empty($message->requestHeaders)) {
            $message->requestHeaders = ["Accept: */*"];
        } elseif (count(preg_grep("/^Accept:/", $message->requestHeaders)) == 0) {
            $message->requestHeaders[] = "Accept: */*";
        }
        $opts = [
            'method' => $message->getMethod(),
            'content' => $message->request,
            'header' => $message->requestHeaders,
            'ignore_errors' => true,
        ];

        $message->requestHeaders = implode("\n", $message->requestHeaders);
        try {
            $ctx = stream_context_create(['http' => $opts]);
            $fp = @fopen($message->getUrl(), 'rb', false, $ctx);
            if ($fp) {
                $resp = @stream_get_contents($fp);
                $message->ok = $resp !== false;
                if ($message->ok) {
                    $message->response = $resp;
                    // see http://php.net/manual/en/reserved.variables.httpresponseheader.php
                    if (isset($http_response_header[0])) {
                        $message->responseHeaders = implode("\n", $http_response_header);
                        if (preg_match("/HTTP\/\d.\d\s+(\d+)/", $http_response_header[0], $out)) {
                            $message->status = $out[1];
                        }
                        $message->ok = $message->status < 400;
                        if (!$message->ok) {
                            $message->error = $http_response_header[0];
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
