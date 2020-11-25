<?php

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
    private $custom = array();

    /**
     * Line-item object for content-item.
     *
     * @var LineItem|null $lineItem
     */
    private $lineItem = null;

    /**
     * Time period for availability.
     *
     * @var string|null $available
     */
    private $available = null;

    /**
     * Time period for submission.
     *
     * @var string|null $submission
     */
    private $submission = null;

    /**
     * Class constructor.
     *
     * @param Placement $placementAdvice  Placement object for item (optional)
     * @param string $id   URL of content-item (optional)
     */
    function __construct($placementAdvice = null, $id = null)
    {
        parent::__construct(Item::TYPE_LTI_LINK, $placementAdvice, $id);
    }

    /**
     * Add a custom parameter for the content-item.
     *
     * @param string $name   Name of parameter
     * @param string|null $value  Value of parameter
     */
    public function addCustom($name, $value = null)
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
     */
    public function setLineItem($lineItem)
    {
        $this->lineItem = $lineItem;
    }

    /**
     * Set an availability time period for the content-item.
     *
     * @param TimePeriod $available  Time period
     */
    public function setAvailable($available)
    {
        $this->available = $available;
    }

    /**
     * Set a submission time period for the content-item.
     *
     * @param TimePeriod $submission  Time period
     */
    public function setSubmission($submission)
    {
        $this->submission = $submission;
    }

    public function toJsonldObject()
    {
        $item = parent::toJsonldObject();
        if (!empty($this->lineItem)) {
            $item->lineItem = $this->lineItem->toJsonldObject();
        }
        if (!empty($this->custom)) {
            $item->custom = $this->custom;
        }

        return $item;
    }

    public function toJsonObject()
    {
        $item = parent::toJsonObject();
        if (!empty($this->lineItem)) {
            $item->lineItem = $this->lineItem->toJsonObject();
        }
        if (!empty($this->custom)) {
            $item->custom = $this->custom;
        }

        return $item;
    }

    protected function fromJsonObject($item)
    {
        parent::fromJsonObject($item);
        $url = null;
        $width = null;
        $height = null;
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
            }
        }
    }

}
