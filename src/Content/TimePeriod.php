<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Content;

/**
 * Class to represent a time period object
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class TimePeriod
{

    /**
     * Start date/time.
     *
     * @var int|null $startDateTime
     */
    private ?int $startDateTime = null;

    /**
     * End date/time.
     *
     * @var int|null $endDateTime
     */
    private ?int $endDateTime = null;

    /**
     * Class constructor.
     *
     * @param int|null    $startDateTime  Start date/time
     * @param int|null    $endDateTime    End date/time
     */
    function __construct(?int $startDateTime, ?int $endDateTime)
    {
        if (is_int($startDateTime)) {
            $this->startDateTime = $startDateTime;
        }
        if (is_int($endDateTime)) {
            $this->endDateTime = $endDateTime;
        }
    }

    /**
     * Generate the JSON-LD object representation of the time period.
     *
     * @return object  JSON object
     */
    public function toJsonldObject(): object
    {
        return $this->toJsonObject();
    }

    /**
     * Generate the JSON object representation of the image.
     *
     * @return object  JSON object
     */
    public function toJsonObject(): object
    {
        $timePeriod = new \stdClass();
        if (!is_null($this->startDateTime)) {
            $timePeriod->startDateTime = gmdate('Y-m-d\TH:i:s\Z', $this->startDateTime);
        } else {
            $timePeriod->startDateTime = null;
        }
        if (!is_null($this->endDateTime)) {
            $timePeriod->endDateTime = gmdate('Y-m-d\TH:i:s\Z', $this->endDateTime);
        } else {
            $timePeriod->endDateTime = null;
        }

        return $timePeriod;
    }

    /**
     * Generate a LineItem object from its JSON or JSON-LD representation.
     *
     * @param object $item  A JSON or JSON-LD object representing a content-item
     *
     * @return TimePeriod|null  The TimePeriod object
     */
    public static function fromJsonObject(object $item): ?TimePeriod
    {
        $obj = null;
        $startDateTime = null;
        $endDateTime = null;
        if (is_object($item)) {
            $url = null;
            foreach (get_object_vars($item) as $name => $value) {
                switch ($name) {
                    case 'startDateTime':
                        $startDateTime = strtotime($item->startDateTime);
                        break;
                    case 'endDateTime':
                        $endDateTime = strtotime($item->endDateTime);
                        break;
                }
            }
        } else {
            $url = $item;
        }
        if ($startDateTime || $endDateTime) {
            $obj = new TimePeriod($startDateTime, $endDateTime);
        }

        return $obj;
    }

}
