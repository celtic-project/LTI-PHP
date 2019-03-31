<?php

namespace ceLTIc\LTI;

use ceLTIc\LTI\Http;

/**
 * Class to represent an HTTP message request
 *
 * @deprecated Use Http\HttpMessage instead
 * @see Http\HttpMessage
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @version  3.1.0
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class HTTPMessage extends Http\HttpMessage
{

    /**
     * Class constructor.
     *
     * @param string $url     URL to send request to
     * @param string $method  Request method to use (optional, default is GET)
     * @param mixed  $params  Associative array of parameter values to be passed or message body (optional, default is none)
     * @param string $header  Values to include in the request header (optional, default is none)
     */
    function __construct($url, $method = 'GET', $params = null, $header = null)
    {
        parent::__construct($url, $method, $params, $header);
        Util::log('Class ceLTIc\LTI\HTTPMessage has been deprecated; please use ceLTIc\LTI\Http\HttpMessage instead.', true);
    }

    /**
     * Get HTTPMessage object for last request.
     *
     * @deprecated Use getHttpMessage instead
     * @see Http\HttpMessage::getHttpMessage()
     *
     * @return HTTPMessage HTTP object containing request and response details
     */
    public function getHTTPMessage()
    {
        return $this->getHttpMessage();
    }

}
