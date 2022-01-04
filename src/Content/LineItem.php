<?php

namespace ceLTIc\LTI\Content;

/**
 * Class to represent a line-item object
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class LineItem
{

    /**
     * Label of line-item.
     *
     * @var string|null $label
     */
    private $label = null;

    /**
     * Maximum score of line-item.
     *
     * @var int|null $scoreMaximum
     */
    private $scoreMaximum = null;

    /**
     * Resource ID associated with line-item.
     *
     * @var string|null $resourceId
     */
    private $resourceId = null;

    /**
     * Tag of line-item.
     *
     * @var string|null $tag
     */
    private $tag = null;

    /**
     * Class constructor.
     *
     * @param string  $label          Label
     * @param int     $scoreMaximum   Maximum score
     * @param string  $resourceId     Resource ID (optional)
     * @param string  $tag            Tag (optional)
     */
    function __construct($label, $scoreMaximum, $resourceId = null, $tag = null)
    {
        $this->label = $label;
        $this->scoreMaximum = $scoreMaximum;
        $this->resourceId = $resourceId;
        $this->tag = $tag;
    }

    /**
     * Generate the JSON-LD object representation of the line-item.
     *
     * @return object
     */
    public function toJsonldObject()
    {
        $lineItem = new \stdClass();

        $lineItem->{'@type'} = 'LineItem';
        $lineItem->label = $this->label;
        $lineItem->reportingMethod = 'http://purl.imsglobal.org/ctx/lis/v2p1/Result#normalScore';
        if (!empty($this->resourceId)) {
            $lineItem->assignedActivity = new \stdClass();
            $lineItem->assignedActivity->activityId = $this->resourceId;
        }
        $lineItem->scoreConstraints = new \stdClass();
        $lineItem->scoreConstraints->{'@type'} = 'NumericLimits';
        $lineItem->scoreConstraints->normalMaximum = $this->scoreMaximum;

        return $lineItem;
    }

    /**
     * Generate the JSON object representation of the line-item.
     *
     * @return object
     */
    public function toJsonObject()
    {
        $lineItem = new \stdClass();

        $lineItem->label = $this->label;
        $lineItem->scoreMaximum = $this->scoreMaximum;
        if (!empty($this->resourceId)) {
            $lineItem->resourceId = $this->resourceId;
        }
        if (!empty($this->tag)) {
            $lineItem->tag = $this->tag;
        }

        return $lineItem;
    }

    /**
     * Generate a LineItem object from its JSON or JSON-LD representation.
     *
     * @param object $item  A JSON or JSON-LD object representing a content-item
     *
     * @return LineItem|null  The LineItem object
     */
    public static function fromJsonObject($item)
    {
        $obj = null;
        $label = null;
        $reportingMethod = null;
        $scoreMaximum = null;
        $activityId = null;
        $tag = null;
        $available = null;
        $submission = null;
        foreach (get_object_vars($item) as $name => $value) {
            switch ($name) {
                case 'label':
                    $label = $item->label;
                    break;
                case 'reportingMethod':
                    $reportingMethod = $item->reportingMethod;
                    break;
                case 'scoreConstraints':
                    $scoreConstraints = $item->scoreConstraints;
                    break;
                case 'scoreMaximum':
                    $scoreMaximum = $item->scoreMaximum;
                    break;
                case 'assignedActivity':
                    if (isset($item->assignedActivity->activityId)) {
                        $activityId = $item->assignedActivity->activityId;
                    }
                    break;
                case 'resourceId':
                    $activityId = $item->resourceId;
                    break;
                case 'tag':
                    $tag = $item->tag;
                    break;
            }
        }
        if (is_null($scoreMaximum) && $label && $reportingMethod && $scoreConstraints) {
            foreach (get_object_vars($scoreConstraints) as $name => $value) {
                $method = str_replace('Maximum', 'Score', $name);
                if (substr($reportingMethod, -strlen($method)) === $method) {
                    $scoreMaximum = $value;
                    break;
                }
            }
        }
        if (!is_null($scoreMaximum)) {
            $obj = new LineItem($label, $scoreMaximum, $activityId, $tag);
        }

        return $obj;
    }

}
