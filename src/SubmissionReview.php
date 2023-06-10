<?php

namespace ceLTIc\LTI;

/**
 * Class to represent a submission review
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class SubmissionReview
{

    /**
     * Label value.
     *
     * @var string|null $label
     */
    private $label = null;

    /**
     * Endpoint.
     *
     * @var string|null $endpoint
     */
    private $endpoint = null;

    /**
     * Custom parameters.
     *
     * @var array|null $custom
     */
    private $custom = null;

    /**
     * Class constructor.
     *
     * @param string|null $label     Label (optional)
     * @param string|null $endpoint  Endpoint (optional)
     * @param array|null $custom     Custom parameters (optional)
     */
    public function __construct($label = null, $endpoint = null, $custom = null)
    {
        $this->label = $label;
        $this->endpoint = $endpoint;
        $this->custom = $custom;
    }

    /**
     * Generate a SubmissionReview object from its JSON representation.
     *
     * @param object $json  A JSON object representing a submission review
     *
     * @return SubmissionReview  The SubmissionReview object
     */
    public static function fromJsonObject($json)
    {
        if (!empty($json->label)) {
            $label = $json->label;
        } else {
            $label = null;
        }
        if (!empty($json->url)) {
            $endpoint = $json->url;
        } else {
            $endpoint = null;
        }
        if (!empty($json->custom)) {
            $custom = $json->custom;
        } else {
            $custom = null;
        }

        return new SubmissionReview($label, $endpoint, $custom);
    }

    /**
     * Generate the JSON object representation of the submission review.
     *
     * @return object
     */
    public function toJsonObject()
    {
        $obj = new \stdClass();
        if (!empty($this->label)) {
            $obj->label = $this->label;
        }
        if (!empty($this->endpoint)) {
            $obj->url = $this->endpoint;
        }
        if (!empty($this->custom)) {
            $obj->custom = $this->custom;
        }

        return $obj;
    }

}
