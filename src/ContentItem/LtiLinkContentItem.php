<?php
declare(strict_types=1);

namespace ceLTIc\LTI\ContentItem;

use ceLTIc\LTI\Content\TimePeriod;
use ceLTIc\LTI\Content;
use ceLTIc\LTI\Util;

/**
 * Class to represent an LTI link content item
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class LtiLinkContentItem extends ContentItem
{

    /**
     * Resourcelink ID value.
     *
     * @var string|null $resourceLinkId
     */
    public ?string $resourceLinkId = null;

    /**
     * IDs for associated line-items.
     *
     * @var array|null $lineItemIds
     */
    public ?array $lineItemIds = null;

    /**
     * Class constructor.
     *
     * @param string|null $id          Endpoint of content item
     */
    public function __construct(?string $id = null)
    {
        $this->item = new Content\LtiLinkItem(null, $id);
    }

    /**
     * Wrap the content items to form a complete value for the https://purl.imsglobal.org/spec/lti-dl/claim/content_items claim.
     *
     * @return object  JSON object
     */
    public function toJsonObject(): object
    {
        $item = parent::toJsonObject();
        if (!isset($item->title)) {
            $item->title = 'Untitled';
        }
        if (!isset($item->url)) {
            $item->url = '';
        }
        if (!is_null($this->resourceLinkId)) {
            $item->resourceLinkId = $this->resourceLinkId;
        }
        if (!is_null($this->lineItemIds)) {
            $item->lineItemIds = $this->lineItemIds;
        }

        return $item;
    }

    /**
     * Extract content item details from its JSON representation.
     *
     * @param object $obj         A JSON object representing an LTI link content item
     *
     * @return bool  True if the item is valid
     */
    protected function fromJsonObject(object $obj): bool
    {
        $ok = parent::fromJsonObject($obj);
        foreach (get_object_vars($obj) as $name => $value) {
            switch ($name) {
                case 'resourceLinkId':
                    $resourceLinkId = Util::checkString($obj, 'resourceLinkId', false, true);
                    if (!is_null($resourceLinkId)) {
                        $this->resourceLinkId = $resourceLinkId;
                    } else {
                        $ok = false;
                    }
                    break;
                case 'custom':
                    $custom = Util::checkObject($obj, 'custom', false, true);
                    if (!is_null($custom)) {
                        foreach (get_object_vars($custom) as $elementName => $elementValue) {
                            $this->item->addCustom($elementName, $elementValue);
                        }
                    } else {
                        $ok = false;
                    }
                    break;
                case 'lineItemIds':
                    $lineItemIds = Util::checkArray($obj, 'lineItemIds', false, false, null);
                    if (!is_null($lineItemIds)) {
                        $this->lineItemIds = $lineItemIds;
                    } else {
                        $ok = false;
                    }
                    break;
                case 'available':
                    if (is_object($obj->available)) {
                        $this->item->setAvailable(TimePeriod::fromJsonObject($obj->available));
                    } elseif (isset($obj->available)) {
                        $ok = false;
                        Util::setMessage(true, 'The available element must be a simple object');
                    }
                    break;
                case 'submission':
                    if (is_object($obj->submission)) {
                        $this->item->setSubmission(TimePeriod::fromJsonObject($obj->submission));
                    } elseif (isset($obj->submission)) {
                        $ok = false;
                        Util::setMessage(true, 'The submission element must be a simple object');
                    }
                    break;
            }
        }

        return $ok;
    }

}
