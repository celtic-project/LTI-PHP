<?php

namespace ceLTIc\LTI\Http;

use ceLTIc\LTI\HTTPMessage;

/**
 * Class to implement the HTTP message interface using the Curl library
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @version  3.1.0
 * @license   GNU Lesser General Public License, version 3 (<http://www.gnu.org/licenses/lgpl.html>)
 */
class CurlClient implements ClientInterface
{

    /**
     * Send the request to the target URL.
     *
     * @param HTTPMessage $message
     *
     * @return bool True if the request was successful
     */
    public function send(HTTPMessage $message)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $message->getUrl());
        if (!empty($message->requestHeaders)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $message->requestHeaders);
        } else {
            curl_setopt($ch, CURLOPT_HEADER, 0);
        }
        if ($message->getMethod() === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $message->request);
        } elseif ($message->getMethod() !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $message->getMethod());
            if (!is_null($message->request)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $message->request);
            }
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $chResp = curl_exec($ch);
        $message->ok = $chResp !== false;
        if ($message->ok) {
            $chResp = str_replace("\r\n", "\n", $chResp);
            $chRespSplit = explode("\n\n", $chResp, 2);
            if ((count($chRespSplit) > 1) && (substr($chRespSplit[1], 0, 5) === 'HTTP/')) {
                $chRespSplit = explode("\n\n", $chRespSplit[1], 2);
            }
            $message->responseHeaders = $chRespSplit[0];
            $message->response = $chRespSplit[1];
            $message->status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $message->ok = $message->status < 400;
            if (!$message->ok) {
                $message->error = curl_error($ch);
            }
        }
        $message->requestHeaders = str_replace("\r\n", "\n", curl_getinfo($ch, CURLINFO_HEADER_OUT));
        curl_close($ch);

        return $message->ok;
    }

}
