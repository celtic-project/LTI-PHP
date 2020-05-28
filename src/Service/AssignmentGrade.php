<?php

namespace ceLTIc\LTI\Service;

/**
 * Class to implement the Assignment and Grade services
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class AssignmentGrade extends Service
{

    /**
     * Class constructor.
     *
     * @param ToolConsumer $consumer   Tool consumer object for this service request
     * @param string       $endpoint   Service endpoint
     * @param string       $mediaType  Media type of message body
     * @param string       $path       Path (optional)
     */
    public function __construct($consumer, $endpoint, $mediaType, $path = '')
    {
        $endpoint = self::addPath($endpoint, $path);
        parent::__construct($consumer, $endpoint, $mediaType);
    }

    /**
     * Add path to a URL.
     *
     * @param string       $endpoint   Service endpoint
     * @param string       $path       Path
     *
     * @return string The endpoint with the path added
     */
    private static function addPath($endpoint, $path)
    {
        if (strpos($endpoint, '?') === false) {
            if (substr($endpoint, -strlen($path)) !== $path) {
                $endpoint .= $path;
            }
        } elseif (strpos($endpoint, "{$path}?") === false) {
            $endpoint = str_replace('?', "{$path}?", $endpoint);
        }

        return $endpoint;
    }

}
