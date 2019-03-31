<?php

namespace ceLTIc\LTI;

/**
 * Class to represent an outcome
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class Outcome
{

    /**
     * Language value.
     *
     * @var string|null $language
     */
    public $language = null;

    /**
     * Outcome status value.
     *
     * @var string|null $status
     */
    public $status = null;

    /**
     * Outcome date value.
     *
     * @var string|null $date
     */
    public $date = null;

    /**
     * Outcome type value.
     *
     * @var string|null $type
     */
    public $type = null;

    /**
     * Outcome data source value.
     *
     * @var string|null $dataSource
     */
    public $dataSource = null;

    /**
     * Outcome value.
     *
     * @var string|null $value
     */
    private $value = null;

    /**
     * Class constructor.
     *
     * @param string $value     Outcome value (optional, default is none)
     */
    public function __construct($value = null)
    {
        $this->value = $value;
        $this->language = 'en-US';
        $this->date = gmdate('Y-m-d\TH:i:s\Z', time());
        $this->type = 'decimal';
    }

    /**
     * Get the outcome value.
     *
     * @return string Outcome value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the outcome value.
     *
     * @param string $value  Outcome value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

}
