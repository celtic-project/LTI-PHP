<?php
declare(strict_types=1);

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
    private ?string $label = null;

    /**
     * Endpoint.
     *
     * @var string|null $endpoint
     */
    private ?string $endpoint = null;

    /**
     * Custom parameters.
     *
     * @var array|null $custom
     */
    private ?array $custom = null;

    /**
     * Class constructor.
     *
     * @param string|null $label     Label (optional)
     * @param string|null $endpoint  Endpoint (optional)
     * @param array|null $custom     Custom parameters (optional)
     */
    public function __construct(?string $label = null, ?string $endpoint = null, ?array $custom = null)
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
    public static function fromJsonObject(object $json): SubmissionReview
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
    public function toJsonObject(): object
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
