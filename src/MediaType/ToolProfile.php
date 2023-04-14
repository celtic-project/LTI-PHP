<?php
declare(strict_types=1);

namespace ceLTIc\LTI\MediaType;

use ceLTIc\LTI\Tool;

/**
 * Class to represent an LTI Tool Profile
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ToolProfile
{

    /**
     * LTI version.
     *
     * @var string $lti_version
     */
    public string $lti_version;

    /**
     * Product instance object.
     *
     * @var object $product_instance
     */
    public object $product_instance;

    /**
     * Resource handlers.
     *
     * @var array $resource_handler
     */
    public array $resource_handler;

    /**
     * Base URLs.
     *
     * @var array $base_url_choice
     */
    public array $base_url_choice;

    /**
     * Class constructor.
     *
     * @param Tool $tool  Tool object
     */
    function __construct(Tool $tool)
    {
        $this->lti_version = 'LTI-2p0';

        if (!empty($tool->product)) {
            $this->product_instance = new \stdClass;
        }
        if (!empty($tool->product->id)) {
            $this->product_instance->guid = $tool->product->id;
        }
        if (!empty($tool->product->name)) {
            $this->product_instance->product_info = (object) [
                    'product_name' => (object) [
                        'default_value' => $tool->product->name,
                        'key' => 'tool.name']
            ];
        }
        if (!empty($tool->product->description)) {
            $this->product_instance->product_info->description = (object) [
                    'default_value' => $tool->product->description,
                    'key' => 'tool.description'
            ];
        }
        if (!empty($tool->product->url)) {
            $this->product_instance->guid = $tool->product->url;
        }
        if (!empty($tool->product->version)) {
            $this->product_instance->product_info->product_version = $tool->product->version;
        }
        if (!empty($tool->vendor)) {
            $this->product_instance->product_info->product_family = new \stdClass;
            $this->product_instance->product_info->product_family->vendor = new \stdClass;
            if (!empty($tool->product->id)) {
                $this->product_instance->product_info->product_family->code = $tool->product->id;
            }
        }
        if (!empty($tool->vendor->id)) {
            $this->product_instance->product_info->product_family->vendor->code = $tool->vendor->id;
        }
        if (!empty($tool->vendor->name)) {
            $this->product_instance->product_info->product_family->vendor->vendor_name = (object) [
                    'default_value' => $tool->vendor->name,
                    'key' => 'tool.vendor.name'
            ];
        }
        if (!empty($tool->vendor->description)) {
            $this->product_instance->product_info->product_family->vendor->description = (object) [
                    'default_value' => $tool->vendor->description,
                    'key' => 'tool.vendor.description'
            ];
        }
        if (!empty($tool->vendor->url)) {
            $this->product_instance->product_info->product_family->vendor->website = $tool->vendor->url;
        }
        if (!empty($tool->vendor->timestamp)) {
            $this->product_instance->product_info->product_family->vendor->timestamp = date('Y-m-d\TH:i:sP',
                $tool->vendor->timestamp);
        }

        $this->resource_handler = [];
        foreach ($tool->resourceHandlers as $resourceHandler) {
            $this->resource_handler[] = new ResourceHandler($tool, $resourceHandler);
        }
        if (!empty($tool->baseUrl)) {
            $this->base_url_choice = [];
            $this->base_url_choice[] = (object) ['default_base_url' => $tool->baseUrl];
        }
    }

}
