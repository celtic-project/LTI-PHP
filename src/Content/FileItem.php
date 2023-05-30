<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Content;

/**
 * Class to represent a file content-item object
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class FileItem extends Item
{

    /**
     * Copy advice for content-item.
     *
     * @var bool|null $copyAdvice
     */
    private ?bool $copyAdvice = null;

    /**
     * Expiry date/time for content-item.
     *
     * @var int|null $expiresAt
     */
    private ?int $expiresAt = null;

    /**
     * Class constructor.
     *
     * @param Placement[]|Placement|null $placementAdvices  Array of Placement objects (or single placement object) for item (optional)
     * @param string|null                $id                URL of content-item (optional)
     */
    function __construct(array|Placement|null $placementAdvices = null, ?string $id = null)
    {
        parent::__construct(Item::TYPE_FILE, $placementAdvices, $id);
    }

    /**
     * Set copy advice for the content-item.
     *
     * @param bool|null $copyAdvice  Copy advice value
     *
     * @return void
     */
    public function setCopyAdvice(?bool $copyAdvice): void
    {
        $this->copyAdvice = $copyAdvice;
    }

    /**
     * Set expiry date/time for the content-item.
     *
     * @param int|null $expiresAt  Expiry date/time
     *
     * @return void
     */
    public function setExpiresAt(?int $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    /**
     * Wrap the content item to form an item complying with the application/vnd.ims.lti.v1.contentitems+json media type.
     *
     * @return object  JSON object
     */
    public function toJsonldObject(): object
    {
        $item = parent::toJsonldObject();
        if (!is_null($this->copyAdvice)) {
            $item->copyAdvice = $this->copyAdvice;
        }
        if (!empty($this->expiresAt)) {
            $item->expiresAt = gmdate('Y-m-d\TH:i:s\Z', $this->expiresAt);
        }

        return $item;
    }

    /**
     * Wrap the content items to form a complete value for the https://purl.imsglobal.org/spec/lti-dl/claim/content_items claim.
     *
     * @return object  JSON object
     */
    public function toJsonObject(): object
    {
        $item = parent::toJsonObject();
        if (!empty($this->expiresAt)) {
            $item->expiresAt = gmdate('Y-m-d\TH:i:s\Z', $this->expiresAt);
        }

        return $item;
    }

    /**
     * Extract content-item details from its JSON representation.
     *
     * @param object $item  A JSON object representing a file content-item
     *
     * @return void
     */
    protected function fromJsonObject(object $item): void
    {
        parent::fromJsonObject($item);
        foreach (get_object_vars($item) as $name => $value) {
            switch ($name) {
                case 'copyAdvice':
                case 'expiresAt':
                    $this->{$name} = $item->{$name};
                    break;
            }
        }
    }

}
