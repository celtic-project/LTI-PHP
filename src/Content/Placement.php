<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Content;

use ceLTIc\LTI\Util;

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
    public const TYPE_EMBED = 'embed';

    /**
     * iFrame placement type.
     */
    public const TYPE_IFRAME = 'iframe';

    /**
     * Frame placement type.
     */
    public const TYPE_FRAME = 'frame';

    /**
     * Window placement type.
     */
    public const TYPE_WINDOW = 'window';

    /**
     * Popup placement type.
     */
    public const TYPE_POPUP = 'popup';

    /**
     * Overlay placement type.
     */
    public const TYPE_OVERLAY = 'overlay';

    /**
     * Location to open content in.
     *
     * @var string|null $documentTarget
     */
    public ?string $documentTarget = null;

    /**
     * Name of window target.
     *
     * @var string|null $windowTarget
     */
    private ?string $windowTarget = null;

    /**
     * Comma-separated list of window features.
     *
     * @var string|null $windowFeatures
     */
    private ?string $windowFeatures = null;

    /**
     * URL of iframe src.
     *
     * @var string|null $url
     */
    private ?string $url = null;

    /**
     * Width of item location.
     *
     * @var int|null $displayWidth
     */
    private ?int $displayWidth = null;

    /**
     * Height of item location.
     *
     * @var int|null $displayHeight
     */
    private ?int $displayHeight = null;

    /**
     * HTML to be embedded.
     *
     * @var string|null $html
     */
    private ?string $html = null;

    /**
     * Class constructor.
     *
     * @param string $documentTarget       Location to open content in
     * @param int|null $displayWidth       Width of item location (optional)
     * @param int|null $displayHeight      Height of item location (optional)
     * @param string|null $windowTarget    Name of window target (optional)
     * @param string|null $windowFeatures  List of window features (optional)
     * @param string|null $url             URL for iframe src (optional)
     * @param string|null $html            HTML to be embedded (optional)
     */
    function __construct(string $documentTarget, ?int $displayWidth = null, ?int $displayHeight = null,
        ?string $windowTarget = null, ?string $windowFeatures = null, ?string $url = null, ?string $html = null)
    {
        $this->documentTarget = $documentTarget;
        $this->displayWidth = $displayWidth;
        $this->displayHeight = $displayHeight;
        $this->windowTarget = $windowTarget;
        $this->windowFeatures = $windowFeatures;
        $this->url = $url;
        $this->html = $html;
    }

    /**
     * Generate the JSON-LD object representation of the placement.
     *
     * @return object|null  JSON object
     */
    public function toJsonldObject(): ?object
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
     * @return object|null  JSON object
     */
    public function toJsonObject(): ?object
    {
        if (!empty($this->documentTarget)) {
            $placement = new \stdClass();
            switch ($this->documentTarget) {
                case self::TYPE_IFRAME:
                    if (!empty($this->url)) {
                        $placement->src = $this->url;
                    }
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
                case self::TYPE_EMBED:
                    if (!empty($this->html)) {
                        $placement->html = $this->html;
                    }
                    break;
            }
        } else {
            $placement = null;
        }

        return $placement;
    }

    /**
     * Generate the Placement object from an item.
     *
     * @param object $item                 JSON object of item
     * @param string|null $documentTarget  Destination of placement to be generated (optional)
     *
     * @return Placement|null  The Placement object
     */
    public static function fromJsonObject(object $item, ?string $documentTarget = null): ?Placement
    {
        $ok = true;
        $obj = null;
        $displayWidth = null;
        $displayHeight = null;
        $windowTarget = null;
        $windowFeatures = null;
        $url = null;
        $html = null;
        if (isset($item->{'@type'})) {  // Version 1
            if (empty($documentTarget) && isset($item->placementAdvice)) {
                $documentTarget = Util::checkString($item->placementAdvice, 'Item/placementAdvice/presentationDocumentTarget',
                        false, true, ['embed', 'frame', 'iframe', 'none', 'overlay', 'popup', 'window'], false, null);
                $ok = $ok && (!is_null($documentTarget) || isset($item->placementAdvice->presentationDocumentTarget));
            }
            if (!empty($documentTarget) && isset($item->placementAdvice) && is_object($item->placementAdvice)) {
                $displayWidth = Util::checkInteger($item->placementAdvice, 'Item/placementAdvice/displayWidth', false, 0, true);
                $ok = $ok && (!is_null($displayWidth) || !isset($item->placementAdvice->displayWidth));
                $displayHeight = Util::checkInteger($item->placementAdvice, 'Item/placementAdvice/displayHeight', false, 0, true);
                $ok = $ok && (!is_null($displayHeight) || !isset($item->placementAdvice->displayHeight));
                $windowTarget = Util::checkString($item->placementAdvice, 'Item/placementAdvice/windowTarget', false, true, '',
                        false, null);
                $ok = $ok && (!is_null($windowTarget) || !isset($item->placementAdvice->windowTarget));
            }
            if (isset($item->url)) {
                $url = Util::checkString($item, 'url', false, true, '', false, null);
                $ok = $ok && !is_null($url);
            }
        } else {  // Version 2
            if (empty($documentTarget)) {
                if (isset($item->embed)) {
                    $documentTarget = 'embed';
                } elseif (isset($item->iframe)) {
                    $documentTarget = 'iframe';
                } elseif (isset($item->window)) {
                    $documentTarget = 'window';
                }
            } elseif (!isset($item->{$documentTarget})) {
                $documentTarget = null;
            }
            if (!empty($documentTarget)) {
                $displayWidth = Util::checkInteger($item->{$documentTarget}, "Item/{$documentTarget}/width", false, 0, true);
                $ok = $ok && (!is_null($displayWidth) || !isset($item->{$documentTarget}->width));
                $displayHeight = Util::checkInteger($item->{$documentTarget}, "Item/{$documentTarget}/height", false, 0, true);
                $ok = $ok && (!is_null($displayHeight) || !isset($item->{$documentTarget}->height));
                $windowTarget = Util::checkString($item->{$documentTarget}, "Item/{$documentTarget}/targetName", false, true, '',
                        false, null);
                $ok = $ok && (!is_null($windowTarget) || !isset($item->{$documentTarget}->targetName));
                $windowFeatures = Util::checkString($item->{$documentTarget}, "Item/{$documentTarget}/windowFeatures", false, true,
                        '', false, null);
                $ok = $ok && (!is_null($windowFeatures) || !isset($item->{$documentTarget}->windowFeatures));
                $url = Util::checkString($item->{$documentTarget}, "Item/{$documentTarget}/src", false, true, '', false, $url);
                $ok = $ok && (!is_null($url) || !isset($item->{$documentTarget}->src));
                $html = Util::checkString($item->{$documentTarget}, "Item/{$documentTarget}/html", false, true, '', false, $html);
                $ok = $ok && (!is_null($html) || !isset($item->{$documentTarget}->html));
            }
        }
        if ($ok && !empty($documentTarget)) {
            $obj = new Placement($documentTarget, $displayWidth, $displayHeight, $windowTarget, $windowFeatures, $url, $html);
        }

        return $obj;
    }

}
