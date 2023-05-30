<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Content;

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
     * @var string|null $available
     */
    private ?string $available = null;

    /**
     * Time period for submission.
     *
     * @var string|null $submission
     */
    private ?string $submission = null;

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
     * @param string|null $id                          URL of content-item (optional)
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
        if (!empty($name)) {
            if (!empty($value)) {
                $this->custom[$name] = $value;
            } else {
                reset($this->custom[$name]);
            }
        }
    }

    /**
     * Set a line-item for the content-item.
     *
     * @param LineItem $lineItem  Line-item
     *
     * @return void
     */
    public function setLineItem(LineItem $lineItem): void
    {
        $this->lineItem = $lineItem;
    }

    /**
     * Set an availability time period for the content-item.
     *
     * @param TimePeriod $available  Time period
     *
     * @return void
     */
    public function setAvailable(TimePeriod $available): void
    {
        $this->available = $available;
    }

    /**
     * Set a submission time period for the content-item.
     *
     * @param TimePeriod $submission  Time period
     *
     * @return void
     */
    public function setSubmission(TimePeriod $submission): void
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
        if (!empty($this->lineItem)) {
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
        if (!empty($this->lineItem)) {
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
     * @return void
     */
    protected function fromJsonObject(object $item): void
    {
        parent::fromJsonObject($item);
        foreach (get_object_vars($item) as $name => $value) {
            switch ($name) {
                case 'custom':
                    foreach ($item->custom as $paramName => $paramValue) {
                        $this->addCustom($paramName, $paramValue);
                    }
                    break;
                case 'lineItem':
                    $this->setLineItem(LineItem::fromJsonObject($item->lineItem));
                    break;
                case 'available':
                    $this->setAvailable(TimePeriod::fromJsonObject($item->available));
                    break;
                case 'submission':
                    $this->setSubmission(TimePeriod::fromJsonObject($item->submission));
                    break;
                case 'noUpdate':
                    $this->noUpdate = $item->noUpdate;
                    break;
            }
        }
    }

}
