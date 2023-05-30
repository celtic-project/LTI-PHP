<?php

namespace ceLTIc\LTI\Content;

use ceLTIc\LTI\Util;
use ceLTIc\LTI\Enum\LtiVersion;

/**
 * Class to represent a content-item object
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class Item
{

    /**
     * Type for link content-item.
     */
    public const TYPE_LINK = 'link';

    /**
     * Type for LTI link content-item.
     */
    public const TYPE_LTI_LINK = 'ltiResourceLink';

    /**
     * Type for LTI assignment content-item.
     */
    public const TYPE_LTI_ASSIGNMENT = 'ltiAssignment';

    /**
     * Type for file content-item.
     */
    public const TYPE_FILE = 'file';

    /**
     * Type for HTML content-item.
     */
    public const TYPE_HTML = 'html';

    /**
     * Type for image content-item.
     */
    public const TYPE_IMAGE = 'image';

    /**
     * Media type for LTI launch links.
     */
    public const LTI_LINK_MEDIA_TYPE = 'application/vnd.ims.lti.v1.ltilink';

    /**
     * Media type for LTI assignment links.
     */
    public const LTI_ASSIGNMENT_MEDIA_TYPE = 'application/vnd.ims.lti.v1.ltiassignment';

    /**
     * Type of content-item.
     *
     * @var string|null $type
     */
    private ?string $type = null;

    /**
     * ID of content-item.
     *
     * @var string|null $id
     */
    private ?string $id = null;

    /**
     * Array of placement objects for content-item.
     *
     * @var array $placements
     */
    private array $placements = [];

    /**
     * URL of content-item.
     *
     * @var string|null $url
     */
    private ?string $url = null;

    /**
     * Media type of content-item.
     *
     * @var string|null $mediaType
     */
    private ?string $mediaType = null;

    /**
     * Title of content-item.
     *
     * @var string|null $title
     */
    private ?string $title = null;

    /**
     * Description of content-item.
     *
     * @var string|null $text
     */
    private ?string $text = null;

    /**
     * HTML to be embedded.
     *
     * @var string|null $html
     */
    private ?string $html = null;

    /**
     * Icon image object for content-item.
     *
     * @var Image|null $icon
     */
    private ?Image $icon = null;

    /**
     * Thumbnail image object for content-item.
     *
     * @var Image|null $thumbnail
     */
    private ?Image $thumbnail = null;

    /**
     * Hide the item from learners by default?
     *
     * @var bool|null $hideOnCreate
     */
    private ?bool $hideOnCreate = null;

    /**
     * Class constructor.
     *
     * @param string                     $type              Class type of content-item
     * @param Placement[]|Placement|null $placementAdvices  Array of Placement objects (or single placement object) for item (optional)
     * @param string|null                $id                URL of content-item (optional)
     */
    function __construct(string $type, array|Placement|null $placementAdvices = null, ?string $id = null)
    {
        $this->type = $type;
        if (!empty($placementAdvices)) {
            if (!is_array($placementAdvices)) {
                $placementAdvices = [$placementAdvices];
            }
            foreach ($placementAdvices as $placementAdvice) {
                $this->placements[$placementAdvice->documentTarget] = $placementAdvice;
            }
        }
        $this->id = $id;
    }

    /**
     * Set a URL value for the content-item.
     *
     * @param string|null $url  URL value
     */
    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    /**
     * Set a media type value for the content-item.
     *
     * @param string|null $mediaType  Media type value
     */
    public function setMediaType(?string $mediaType): void
    {
        $this->mediaType = $mediaType;
    }

    /**
     * Set a title value for the content-item.
     *
     * @param string|null $title  Title value
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * Set a link text value for the content-item.
     *
     * @param string|null $text  Link text value
     */
    public function setText(?string $text): void
    {
        $this->text = $text;
    }

    /**
     * Set an HTML embed value for the content-item.
     *
     * @param string|null $html  HTML text value
     */
    public function setHtml(?string $html): void
    {
        $this->html = $html;
    }

    /**
     * Add a placement for the content-item.
     *
     * @param Placement|null $placementAdvice  Placement advice object
     */
    public function addPlacementAdvice(?Placement $placementAdvice): void
    {
        if (!empty($placementAdvice)) {
            $this->placements[$placementAdvice->documentTarget] = $placementAdvice;
        }
    }

    /**
     * Set an icon image for the content-item.
     *
     * @param Image|null $icon  Icon image object
     */
    public function setIcon(?Image $icon): void
    {
        $this->icon = $icon;
    }

    /**
     * Set a thumbnail image for the content-item.
     *
     * @param Image|null $thumbnail  Thumbnail image object
     */
    public function setThumbnail(?Image $thumbnail): void
    {
        $this->thumbnail = $thumbnail;
    }

    /**
     * Set whether the content-item should be hidden from learners by default.
     *
     * @param bool|null $hideOnCreate  True if the item should be hidden from learners
     */
    public function setHideOnCreate(?bool $hideOnCreate): void
    {
        $this->hideOnCreate = $hideOnCreate;
    }

    /**
     * Wrap the content items to form a complete application/vnd.ims.lti.v1.contentitems+json media type instance.
     *
     * @param Item[]|Item $items       An array of content items or a single item
     * @param LtiVersion  $ltiVersion  LTI version in use
     *
     * @return string
     */
    public static function toJson(array|Item $items, LtiVersion $ltiVersion = LtiVersion::V1): string
    {
        if (!is_array($items)) {
            $items = [$items];
        }
        if ($ltiVersion !== LtiVersion::V1P3) {
            $obj = new \stdClass();
            $obj->{'@context'} = 'http://purl.imsglobal.org/ctx/lti/v1/ContentItem';
            $obj->{'@graph'} = [];
            foreach ($items as $item) {
                $obj->{'@graph'}[] = $item->toJsonldObject();
            }
        } else {
            $obj = [];
            foreach ($items as $item) {
                $obj[] = $item->toJsonObject();
            }
        }

        return json_encode($obj);
    }

    /**
     * Generate an array of Item objects from their JSON representation.
     *
     * @param object $items  A JSON object representing Content-Items
     *
     * @return array Array of Item objects
     */
    public static function fromJson(object $items): array
    {
        $isJsonLd = isset($items->{'@graph'});
        if ($isJsonLd) {
            $items = $items->{'@graph'};
        }
        if (!is_array($items)) {
            $items = [$items];
        }
        $objs = [];
        foreach ($items as $item) {
            $obj = self::fromJsonItem($item);
            if (!empty($obj)) {
                $objs[] = $obj;
            }
        }

        return $objs;
    }

    /**
     * Wrap the content item to form an item complying with the application/vnd.ims.lti.v1.contentitems+json media type.
     *
     * @return object  JSON object
     */
    protected function toJsonldObject(): object
    {
        $item = new \stdClass();
        if (!empty($this->id)) {
            $item->{'@id'} = $this->id;
        }
        if (!empty($this->type)) {
            if (($this->type === self::TYPE_LTI_LINK) || ($this->type === self::TYPE_LTI_ASSIGNMENT)) {
                $item->{'@type'} = 'LtiLinkItem';
            } elseif ($this->type === self::TYPE_FILE) {
                $item->{'@type'} = 'FileItem';
            } else {
                $item->{'@type'} = 'ContentItem';
            }
        } else {
            $item->{'@type'} = 'ContentItem';
        }
        if (!empty($this->title)) {
            $item->title = $this->title;
        }
        if (!empty($this->text)) {
            $item->text = $this->text;
        } elseif (!empty($this->html)) {
            $item->text = $this->html;
        }
        if (!empty($this->url)) {
            $item->url = $this->url;
        }
        if (!empty($this->mediaType)) {
            $item->mediaType = $this->mediaType;
        }
        if (!empty($this->placements)) {
            $placementAdvice = new \stdClass();
            $placementAdvices = [];
            foreach ($this->placements as $placement) {
                $obj = $placement->toJsonldObject();
                if (!empty($obj)) {
                    if (!empty($placement->documentTarget)) {
                        $placementAdvices[] = $placement->documentTarget;
                    }
                    $placementAdvice = (object) array_merge((array) $placementAdvice, (array) $obj);
                }
            }
            if (!empty($placementAdvice)) {
                $item->placementAdvice = $placementAdvice;
                if (!empty($placementAdvices)) {
                    $item->placementAdvice->presentationDocumentTarget = implode(',', $placementAdvices);
                }
            }
        }
        if (!empty($this->icon)) {
            $item->icon = $this->icon->toJsonldObject();
        }
        if (!empty($this->thumbnail)) {
            $item->thumbnail = $this->thumbnail->toJsonldObject();
        }
        if (!is_null($this->hideOnCreate)) {
            $item->hideOnCreate = $this->hideOnCreate;
        }

        return $item;
    }

    /**
     * Wrap the content items to form a complete value for the https://purl.imsglobal.org/spec/lti-dl/claim/content_items claim.
     *
     * @return object  JSON object
     */
    protected function toJsonObject(): object
    {
        $item = new \stdClass();
        switch ($this->type) {
            case 'LtiLinkItem':
                $item->type = self::TYPE_LTI_LINK;
                break;
            case 'FileItem':
                $item->type = self::TYPE_FILE;
                break;
            case 'ContentItem':
                if (empty($this->url)) {
                    $item->type = self::TYPE_HTML;
                } elseif (!empty($this->mediaType) && str_starts_with($this->mediaType, 'image')) {
                    $item->type = self::TYPE_IMAGE;
                } else {
                    $item->type = self::TYPE_LINK;
                }
                break;
            default:
                $item->type = $this->type;
                break;
        }
        if (!empty($this->title)) {
            $item->title = $this->title;
        }
        if (!empty($this->text)) {
            $item->text = Util::stripHtml($this->text);
        }
        if (!empty($this->html)) {
            $item->html = $this->html;
        }
        if (!empty($this->url)) {
            $item->url = $this->url;
        }
        foreach ($this->placements as $type => $placement) {
            $obj = match ($type) {
                Placement::TYPE_EMBED,
                Placement::TYPE_IFRAME,
                Placement::TYPE_WINDOW,
                Placement::TYPE_FRAME => $placement->toJsonObject(),
                default => null
            };
            if (!empty($obj)) {
                $item->{$type} = $obj;
            }
        }
        if (!empty($this->icon)) {
            $item->icon = $this->icon->toJsonObject();
        }
        if (!empty($this->thumbnail)) {
            $item->thumbnail = $this->thumbnail->toJsonObject();
        }
        if (!is_null($this->hideOnCreate)) {
            $item->hideOnCreate = $this->hideOnCreate;
        }

        return $item;
    }

    /**
     * Generate an Item object from its JSON or JSON-LD representation.
     *
     * @param object $item  A JSON or JSON-LD object representing a content-item
     *
     * @return Item|LtiLinkItem|FileItem  The content-item object
     */
    public static function fromJsonItem(object $item): Item|LtiLinkItem|FileItem
    {
        $obj = null;
        $placement = null;
        if (isset($item->{'@type'})) {
            if (isset($item->presentationDocumentTarget)) {
                $placement = Placement::fromJsonObject($item, $item->presentationDocumentTarget);
            }
            $obj = match ($item->{'@type'}) {
                'ContentItem' => new Item('ContentItem', $placement),
                'LtiLinkItem' => new LtiLinkItem($placement),
                'FileItem' => new FileItem($placement)
            };
        } elseif (isset($item->type)) {
            $placements = [];
            $placement = Placement::fromJsonObject($item, 'embed');
            if (!empty($placement)) {
                $placements[] = $placement;
            }
            $placement = Placement::fromJsonObject($item, 'iframe');
            if (!empty($placement)) {
                $placements[] = $placement;
            }
            $placement = Placement::fromJsonObject($item, 'window');
            if (!empty($placement)) {
                $placements[] = $placement;
            }
            $obj = match ($item->type) {
                self::TYPE_LINK,
                self::TYPE_HTML,
                self::TYPE_IMAGE => new Item($item->type, $placements),
                self::TYPE_LTI_LINK => new LtiLinkItem($placements),
                self::TYPE_LTI_ASSIGNMENT => new LtiAssignmentItem($placements),
                self::TYPE_FILE => new FileItem($placements)
            };
        }
        if (!empty($obj)) {
            $obj->fromJsonObject($item);
        }

        return $obj;
    }

    /**
     * Extract content-item details from its JSON representation.
     *
     * @param object $item  A JSON object representing a content-item
     */
    protected function fromJsonObject(object $item): void
    {
        if (isset($item->{'@id'})) {
            $this->id = $item->{'@id'};
        }
        foreach (get_object_vars($item) as $name => $value) {
            switch ($name) {
                case 'title':
                case 'text':
                case 'html':
                case 'url':
                case 'mediaType':
                case 'hideOnCreate':
                    $this->{$name} = $item->{$name};
                    break;
                case 'placementAdvice':
                    $this->addPlacementAdvice(Placement::fromJsonObject($item));
                    break;
                case 'embed':
                case 'window':
                case 'iframe':
                    $this->addPlacementAdvice(Placement::fromJsonObject($item, $name));
                    break;
                case 'icon':
                case 'thumbnail':
                    $this->{$name} = Image::fromJsonObject($item->{$name});
                    break;
            }
        }
    }

}
