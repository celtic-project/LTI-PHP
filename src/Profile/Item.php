<?php

namespace ceLTIc\LTI\Profile;

/**
 * Class to represent a generic item object
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class Item
{

    /**
     * ID of item.
     *
     * @var string|null $id
     */
    public $id = null;

    /**
     * Name of item.
     *
     * @var string|null $name
     */
    public $name = null;

    /**
     * Description of item.
     *
     * @var string|null $description
     */
    public $description = null;

    /**
     * URL of item.
     *
     * @var string|null $url
     */
    public $url = null;

    /**
     * Version of item.
     *
     * @var string|null $version
     */
    public $version = null;

    /**
     * Timestamp of item.
     *
     * @var int|null $timestamp
     */
    public $timestamp = null;

    /**
     * Class constructor.
     *
     * @param string $id                ID of item (optional)
     * @param string|null $name         Name of item (optional)
     * @param string|null $description  Description of item (optional)
     * @param string|null $url          URL of item (optional)
     * @param string|null $version      Version of item (optional)
     * @param int|null $timestamp       Timestamp of item (optional)
     */
    function __construct($id = null, $name = null, $description = null, $url = null, $version = null, $timestamp = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->url = $url;
        $this->version = $version;
        $this->timestamp = $timestamp;
    }

}
