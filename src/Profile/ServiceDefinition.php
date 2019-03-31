<?php

namespace ceLTIc\LTI\Profile;

/**
 * Class to represent an LTI service object
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ServiceDefinition
{

    /**
     * Media types supported by service.
     *
     * @var array|null $formats
     */
    public $formats = null;

    /**
     * HTTP actions accepted by service.
     *
     * @var array|null $actions
     */
    public $actions = null;

    /**
     * ID of service.
     *
     * @var string|null $id
     */
    public $id = null;

    /**
     * URL for service requests.
     *
     * @var string|null $endpoint
     */
    public $endpoint = null;

    /**
     * Class constructor.
     *
     * @param array  $formats   Array of media types supported by service
     * @param array  $actions   Array of HTTP actions accepted by service
     * @param string $id        ID of service (optional)
     * @param string $endpoint  URL for service requests (optional)
     */
    function __construct($formats, $actions, $id = null, $endpoint = null)
    {
        $this->formats = $formats;
        $this->actions = $actions;
        $this->id = $id;
        $this->endpoint = $endpoint;
    }

    function setId($id)
    {
        $this->id = $id;
    }

}
