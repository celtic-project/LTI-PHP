<?php

namespace ceLTIc\LTI\Content;

/**
 * Class to represent a content-item placement object
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class Placement
{

    /**
     * Embed placement type.
     */
    const TYPE_EMBED = 'embed';

    /**
     * iFrame placement type.
     */
    const TYPE_IFRAME = 'iframe';

    /**
     * Frame placement type.
     */
    const TYPE_FRAME = 'frame';

    /**
     * Window placement type.
     */
    const TYPE_WINDOW = 'window';

    /**
     * Popup placement type.
     */
    const TYPE_POPUP = 'popup';

    /**
     * Overlay placement type.
     */
    const TYPE_OVERLAY = 'overlay';

    /**
     * Location to open content in.
     *
     * @var string|null $documentTarget
     */
    public $documentTarget = null;

    /**
     * Name of window target.
     *
     * @var string|null $windowTarget
     */
    private $windowTarget = null;

    /**
     * Comma-separated list of window features.
     *
     * @var string|null $windowFeatures
     */
    private $windowFeatures = null;

    /**
     * URL of iframe src.
     *
     * @var string|null $url
     */
    private $url = null;

    /**
     * Width of item location.
     *
     * @var int|null $displayWidth
     */
    private $displayWidth = null;

    /**
     * Height of item location.
     *
     * @var int|null $displayHeight
     */
    private $displayHeight = null;

    /**
     * Class constructor.
     *
     * @param string $documentTarget  Location to open content in
     * @param int $displayWidth       Width of item location (optional)
     * @param int $displayHeight      Height of item location (optional)
     * @param string $windowTarget    Name of window target (optional)
     * @param string $windowFeatures  List of window features (optional)
     * @param string $url             URL for iframe src (optional)
     */
    function __construct($documentTarget, $displayWidth = null, $displayHeight = null, $windowTarget = null, $windowFeatures = null,
        $url = null)
    {
        $this->documentTarget = $documentTarget;
        $this->displayWidth = $displayWidth;
        $this->displayHeight = $displayHeight;
        $this->windowTarget = $windowTarget;
        $this->windowFeatures = $windowFeatures;
        $this->url = $url;
    }

    /**
     * Generate the JSON-LD object representation of the placement.
     *
     * @return object
     */
    public function toJsonldObject()
    {
        if (!empty($this->documentTarget)) {
            $placement = new \stdClass();
            $placement->presentationDocumentTarget = $this->documentTarget;
            if (!is_null($this->displayHeight)) {
                $placement->displayHeight = $this->displayHeight;
            }
            if (!is_null($this->displayWidth)) {
                $placement->displayWidth = $this->displayWidth;
            }
            if (!empty($this->windowTarget)) {
                $placement->windowTarget = $this->windowTarget;
            }
        } else {
            $placement = null;
        }

        return $placement;
    }

    /**
     * Generate the JSON object representation of the placement.
     *
     * @return object
     */
    public function toJsonObject()
    {
        if (!empty($this->documentTarget)) {
            $placement = new \stdClass();
            switch ($this->documentTarget) {
                case self::TYPE_IFRAME:
                    $placement->src = $this->url;
                    if (!is_null($this->displayWidth)) {
                        $placement->width = $this->displayWidth;
                    }
                    if (!is_null($this->displayHeight)) {
                        $placement->height = $this->displayHeight;
                    }
                    break;
                case self::TYPE_WINDOW:
                    if (!is_null($this->displayWidth)) {
                        $placement->width = $this->displayWidth;
                    }
                    if (!is_null($this->displayHeight)) {
                        $placement->height = $this->displayHeight;
                    }
                    if (!is_null($this->windowTarget)) {
                        $placement->targetName = $this->windowTarget;
                    }
                    if (!is_null($this->windowFeatures)) {
                        $placement->windowFeatures = $this->windowFeatures;
                    }
                    break;
            }
        } else {
            $placement = null;
        }

        return $placement;
    }

    public static function fromJsonObject($item)
    {
        $obj = null;
        $documentTarget = null;
        $displayWidth = null;
        $displayHeight = null;
        $windowTarget = null;
        $windowFeatures = null;
        $url = null;
        foreach (get_object_vars($item) as $name => $value) {
            switch ($name) {
                case 'presentationDocumentTarget':
                case 'documentTarget':
                    $documentTarget = $value;
                    break;
                case 'displayWidth':
                case 'width':
                    $displayWidth = $value;
                    break;
                case 'displayHeight':
                case 'height':
                    $displayHeight = $value;
                    break;
                case 'windowTarget':
                case 'targetName':
                    $windowTarget = $value;
                    break;
                case 'windowFeatures':
                    $windowFeatures = $value;
                    break;
                case 'url':
                case 'src':
                    $url = $value;
                    break;
            }
        }
        if ($documentTarget) {
            $obj = new Placement($documentTarget, $displayWidth, $displayHeight, $windowTarget, $windowFeatures, $url);
        }

        return $obj;
    }

}
