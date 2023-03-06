<?php

namespace ceLTIc\LTI\Service;

use ceLTIc\LTI\Platform;

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
     * @param Platform     $platform   Platform object for this service request
     * @param string       $endpoint   Service endpoint
     * @param string       $path       Path (optional)
     */
    public function __construct($platform, $endpoint, $path = '')
    {
        $endpoint = self::addPath($endpoint, $path);
        parent::__construct($platform, $endpoint);
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
