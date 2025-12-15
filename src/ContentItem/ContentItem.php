<?php
declare(strict_types=1);

namespace ceLTIc\LTI\ContentItem;

use ceLTIc\LTI\Platform;
use ceLTIc\LTI\Service;
use ceLTIc\LTI\Content;
use ceLTIc\LTI\Content\Item;
use ceLTIc\LTI\Content\Image;
use ceLTIc\LTI\Util;

/**
 * Class to represent a content item
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ContentItem
{

    /**
     * Readonly properties.
     *
     * @var array|null $readonly
     */
    public ?array $readonly = null;

    /**
     * Item object
     *
     * @var Item $item
     */
    protected Item $item;

    /**
     * Get content item.
     *
     * @return Item  Item object for this content item.
     */
    public function getItem(): Item
    {
        return $this->item;
    }

    /**
     * Save a content-item.
     *
     * @param Platform $platform  Platform object
     *
     * @return bool  True if successful
     */
    public function save(Platform $platform): bool
    {
        $ok = false;
        if (!empty($this->item)) {
            $id = $this->item->getId();
            if (!empty($id)) {
                $linkContentService = new Service\LinkContent($platform, $id);
                if (!empty($linkContentService)) {
                    $ok = $linkContentService->saveContentItem($this);
                }
            }
        }

        return $ok;
    }

    /**
     * Delete a content-item.
     *
     * @param Platform $platform  Platform object
     *
     * @return bool  True if successful
     */
    public function delete(Platform $platform): bool
    {
        $ok = false;
        if (!empty($this->item)) {
            $id = $this->item->getId();
            if (!empty($id)) {
                $linkContentService = new Service\LinkContent($platform, $id);
                if (!empty($linkContentService)) {
                    $ok = $linkContentService->deleteContentItem();
                }
            }
        }

        return $ok;
    }

    /**
     * Wrap the content item to form a JSON string.
     *
     * @return string
     */
    public function toJson(): string
    {
        $obj = $this->toJsonObject();

        return json_encode($obj, JSON_UNESCAPED_SLASHES);
    }

    /**
     * Wrap the content items to form a JSON object of the value.
     *
     * @return object  JSON object
     */
    public function toJsonObject(): object
    {
        $item = $this->item->toJsonObject();
        $id = $this->item->getId();
        if (!empty($id)) {
            $item->id = $id;
        }
        if (!is_null($this->readonly)) {
            $item->readonly = $this->readonly;
        }

        return $item;
    }

    /**
     * Generate a Content Item object from its type.
     *
     * @param string $type     Type of content item
     * @param string|null $id  Endpoint of content item
     *
     * @return ContentItem|LtiLinkContentItem|null  The content item object
     */
    public static function fromType(string $type, ?string $id = null): ContentItem|LtiLinkContentItem|null
    {
        $contentItem = match ($type) {
            Item::TYPE_LTI_LINK => new LtiLinkContentItem($id),
            default => new ContentItem(),
        };
        $contentItem->item = match ($type) {
            Item::TYPE_LINK,
            Item::TYPE_HTML,
            Item::TYPE_IMAGE => new Item($type, null, $id),
            Item::TYPE_LTI_LINK => new Content\LtiLinkItem(null, $id),
            Item::TYPE_LTI_ASSIGNMENT => new Content\LtiAssignmentItem(null, $id),
            Item::TYPE_FILE => new Content\FileItem(null, $id),
            default => new Item($type, null, $id),
        };

        return $contentItem;
    }

    /**
     * Generate a Content Item object from its JSON representation.
     *
     * @param object $item  A JSON object representing a content item
     *
     * @return ContentItem|LtiLinkContentItem|null  The content item object
     */
    public static function fromJsonItem(object $item): ContentItem|LtiLinkContentItem|null
    {
        $obj = null;
        $id = Util::checkString($item, 'id', false, true, '', false, null);
        $type = Util::checkString($item, 'type', true, true, '', false, null);
        if (!is_null($type)) {
            if (!in_array($type, [Item::TYPE_LINK, Item::TYPE_LTI_LINK, Item::TYPE_FILE, Item::TYPE_HTML, Item::TYPE_IMAGE])) {
                Util::setMessage(false, "Value of the 'Item/type' element not recognised ('{$type}' found)");
            }
            $obj = self::fromType($type, $id);
            $obj->fromJsonObject($item);
        }

        return $obj;
    }

    /**
     * Retrieve a content item definition.
     *
     * @param Platform $platform  Platform object
     * @param string $endpoint    ID value
     *
     * @return ContentItem|bool  ContentItem object or false on error
     */
    public static function fromEndpoint(Platform $platform, string $endpoint): ContentItem|bool
    {
        $linkContentService = new Service\LinkContent($platform, $endpoint);

        return $linkContentService->get();
    }

    /**
     * Extract content item details from its JSON representation.
     *
     * @param object $obj  A JSON object representing a content item
     *
     * @return bool  True if the item is valid
     */
    protected function fromJsonObject(object $obj): bool
    {
        $id = Util::checkString($obj, 'id', false, true, '', false, $this->item->getId());
        $ok = $this->item->getId() === $id;
        if ($ok) {
            foreach (get_object_vars($obj) as $name => $value) {
                switch ($name) {
                    case 'url':
                        $url = Util::checkUrl($obj, $name, true, true);
                        if (!is_null($url)) {
                            $this->item->setUrl($url);
                        } else {
                            $ok = false;
                        }
                        break;
                    case 'title':
                        $title = Util::checkString($obj, $name, false, null, '', false, null);
                        if (!is_null($title)) {
                            $this->item->setTitle($title);
                        } else {
                            $ok = false;
                        }
                        break;
                    case 'text':
                        $text = Util::checkString($obj, $name, false, null, '', false, null);
                        if (!is_null($text)) {
                            $this->item->setText($text);
                        } else {
                            $ok = false;
                        }
                        break;
                    case 'hideOnCreate':
                        $hideOnCreate = Util::checkBoolean($obj, 'hideOnCreate', false);
                        if (!is_null($hideOnCreate) || !Util::$strictMode) {
                            $this->item->setHideOnCreate($hideOnCreate);
                        } else {
                            $ok = false;
                        }
                        break;
                    case 'icon':
                        if (is_object($value) || is_string($value)) {
                            $icon = Image::fromJsonObject($value);
                            if (!is_null($icon)) {
                                $this->item->setIcon($icon);
                            } else {
                                $ok = false;
                            }
                        } else {
                            $ok = false;
                            Util::setMessage(true, "The {$name} element must be a simple object or string");
                        }
                        break;
                    case 'thumbnail':
                        if (is_object($value) || is_string($value)) {
                            $thumbnail = Image::fromJsonObject($value);
                            if (!is_null($thumbnail)) {
                                $this->item->setThumbnail($thumbnail);
                            } else {
                                $ok = false;
                            }
                        } else {
                            $ok = false;
                            Util::setMessage(true, "The {$name} element must be a simple object or string");
                        }
                        break;
                }
            }
        }

        return $ok;
    }

}
