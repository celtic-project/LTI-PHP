<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Content;

use ceLTIc\LTI\Util;

/**
 * Class to represent an LTI link content-item object
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class LtiLinkItem extends Item
{

    /**
     * Custom parameters for content-item.
     *
     * @var array $custom
     */
    private array $custom = [];

    /**
     * Line-item object for content-item.
     *
     * @var LineItem|null $lineItem
     */
    private ?LineItem $lineItem = null;

    /**
     * Time period for availability.
     *
     * @var TimePeriod|null $available
     */
    private ?TimePeriod $available = null;

    /**
     * Time period for submission.
     *
     * @var TimePeriod|null $submission
     */
    private ?TimePeriod $submission = null;

    /**
     * Do not allow the item to be updated?
     *
     * @var bool|null $noUpdate
     */
    private ?bool $noUpdate = null;

    /**
     * Class constructor.
     *
     * @param Placement[]|Placement|null $placementAdvices  Array of Placement objects (or single placement object) for item (optional)
     * @param string|null $id                               URL of content-item (optional)
     */
    function __construct(array|Placement|null $placementAdvices = null, ?string $id = null)
    {
        parent::__construct(Item::TYPE_LTI_LINK, $placementAdvices, $id);
        $this->setMediaType(Item::LTI_LINK_MEDIA_TYPE);
    }

    /**
     * Add a custom parameter for the content-item.
     *
     * @param string $name        Name of parameter
     * @param string|null $value  Value of parameter
     *
     * @return void
     */
    public function addCustom(string $name, ?string $value = null): void
    {
        if (!is_null($name) && (strlen($name) > 0)) {
            if (is_string($value)) {
                $this->custom[$name] = $value;
            } else {
                unset($this->custom[$name]);
            }
        }
    }

    /**
     * Set a line-item for the content-item.
     *
     * @param LineItem|null $lineItem  Line-item
     *
     * @return void
     */
    public function setLineItem(?LineItem $lineItem): void
    {
        $this->lineItem = $lineItem;
    }

    /**
     * Set an availability time period for the content-item.
     *
     * @param TimePeriod|null $available  Time period
     *
     * @return void
     */
    public function setAvailable(?TimePeriod $available): void
    {
        $this->available = $available;
    }

    /**
     * Set a submission time period for the content-item.
     *
     * @param TimePeriod|null $submission  Time period
     *
     * @return void
     */
    public function setSubmission(?TimePeriod $submission): void
    {
        $this->submission = $submission;
    }

    /**
     * Set whether the content-item should not be allowed to be updated.
     *
     * @param bool|null $noUpdate  True if the item should not be updatable
     *
     * @return void
     */
    public function setNoUpdate(?bool $noUpdate): void
    {
        $this->noUpdate = $noUpdate;
    }

    /**
     * Wrap the content item to form an item complying with the application/vnd.ims.lti.v1.contentitems+json media type.
     *
     * @return object  JSON object
     */
    public function toJsonldObject(): object
    {
        $item = parent::toJsonldObject();
        if (!is_null($this->lineItem)) {
            $item->lineItem = $this->lineItem->toJsonldObject();
        }
        if (!is_null($this->noUpdate)) {
            $item->noUpdate = $this->noUpdate;
        }
        if (!is_null($this->available)) {
            $item->available = $this->available->toJsonldObject();
        }
        if (!is_null($this->submission)) {
            $item->submission = $this->submission->toJsonldObject();
        }
        if (!empty($this->custom)) {
            $item->custom = $this->custom;
        }

        return $item;
    }

    /**
     * Wrap the content items to form a complete value for the https://purl.imsglobal.org/spec/lti-dl/claim/content_items claim.
     *
     * @return object  JSON object
     */
    public function toJsonObject(): object
    {
        $item = parent::toJsonObject();
        if (!is_null($this->lineItem)) {
            $item->lineItem = $this->lineItem->toJsonObject();
        }
        if (!is_null($this->noUpdate)) {
            $item->noUpdate = $this->noUpdate;
        }
        if (!is_null($this->available)) {
            $item->available = $this->available->toJsonObject();
        }
        if (!is_null($this->submission)) {
            $item->submission = $this->submission->toJsonObject();
        }
        if (!empty($this->custom)) {
            $item->custom = $this->custom;
        }

        return $item;
    }

    /**
     * Extract content-item details from its JSON representation.
     *
     * @param object $item  A JSON object representing an LTI link content-item
     *
     * @return bool  True if the item is valid
     */
    protected function fromJsonObject(object $item): bool
    {
        $ok = parent::fromJsonObject($item);
        foreach (get_object_vars($item) as $name => $value) {
            switch ($name) {
                case 'custom':
                    $obj = Util::checkObject($item, 'LtiLink/custom', false, true);
                    if (!is_null($obj)) {
                        foreach (\get_object_vars($obj) as $elementName => $elementValue) {
                            $this->addCustom($elementName, $elementValue);
                        }
                    } else {
                        $ok = false;
                    }
                    break;
                case 'lineItem':
                    if (is_object($item->lineItem)) {
                        $lineItem = LineItem::fromJsonObject($item->lineItem);
                        if (!is_null($lineItem)) {
                            $this->setLineItem($lineItem);
                        } else {
                            $ok = false;
                        }
                    } elseif (isset($item->lineItem)) {
                        $ok = false;
                        Util::setMessage(true, 'The lineItem element must be a simple object');
                    }
                    break;
                case 'available':
                    if (is_object($item->available)) {
                        $this->setAvailable(TimePeriod::fromJsonObject($item->available));
                    } elseif (isset($item->available)) {
                        $ok = false;
                        Util::setMessage(true, 'The available element must be a simple object');
                    }
                    break;
                case 'submission':
                    if (is_object($item->submission)) {
                        $this->setSubmission(TimePeriod::fromJsonObject($item->submission));
                    } elseif (isset($item->submission)) {
                        $ok = false;
                        Util::setMessage(true, 'The submission element must be a simple object');
                    }
                    break;
                case 'noUpdate':
                    $this->noUpdate = Util::checkBoolean($item, 'LtiLink/noUpdate');
                    if (is_null($this->noUpdate) && isset($this->noUpdate)) {
                        $ok = false;
                    }
                    break;
            }
        }

        return $ok;
    }

}
