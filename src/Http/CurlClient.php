<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Http;

/**
 * Class to implement the HTTP message interface using the Curl library
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license   GNU Lesser General Public License, version 3 (<http://www.gnu.org/licenses/lgpl.html>)
 */
class CurlClient implements ClientInterface
{

    /**
     * The HTTP version to be used.
     *
     * @var int|null $httpVersion
     */
    public static ?int $httpVersion = null;

    /**
     * Send the request to the target URL.
     *
     * @param HttpMessage $message
     *
     * @return bool  True if the request was successful
     */
    public function send(HttpMessage $message): bool
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_URL, $message->getUrl());
        curl_setopt($ch, CURLOPT_HTTPHEADER, $message->requestHeaders);
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
        if (!empty(self::$httpVersion)) {
            curl_setopt($ch, CURLOPT_HTTP_VERSION, self::$httpVersion);
        }
        $chResp = curl_exec($ch);
        $message->ok = $chResp !== false;
        if ($message->ok) {
            $message->requestHeaders = explode("\n", trim(str_replace("\r\n", "\n", curl_getinfo($ch, CURLINFO_HEADER_OUT))));
            $chResp = str_replace("\r\n", "\n", $chResp);
            $chRespSplit = explode("\n\n", $chResp, 2);
            if ((count($chRespSplit) > 1) && str_starts_with($chRespSplit[1], 'HTTP/')) {
                $chRespSplit = explode("\n\n", $chRespSplit[1], 2);
            }
            $message->responseHeaders = explode("\n", trim($chRespSplit[0]));
            if (count($chRespSplit) > 1) {
                $message->response = $chRespSplit[1];
            } else {
                $message->response = '';
            }
            $message->status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $message->ok = ($message->status >= 100) && ($message->status < 400);
            if (!$message->ok) {
                $message->error = curl_error($ch);
            }
        }
        curl_close($ch);

        return $message->ok;
    }

}
