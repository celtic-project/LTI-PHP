<?php

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
    private $copyAdvice = null;

    /**
     * Expiry date/time for content-item.
     *
     * @var int|null $expiresAt
     */
    private $expiresAt = null;

    /**
     * Class constructor.
     *
     * @param Placement[]|Placement $placementAdvices  Array of Placement objects (or single placement object) for item (optional)
     * @param string $id   URL of content-item (optional)
     */
    function __construct($placementAdvices = null, $id = null)
    {
        parent::__construct(Item::TYPE_FILE, $placementAdvices, $id);
    }

    /**
     * Set copy advice for the content-item.
     *
     * @param bool|null $copyAdvice  Copy advice value
     */
    public function setCopyAdvice($copyAdvice)
    {
        $this->copyAdvice = $copyAdvice;
    }

    /**
     * Set expiry date/time for the content-item.
     *
     * @param int|null $expiresAt  Expiry date/time
     */
    public function setExpiresAt($expiresAt)
    {
        $this->expiresAt = $expiresAt;
    }

    public function toJsonldObject()
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

    public function toJsonObject()
    {
        $item = parent::toJsonObject();
        if (!empty($this->expiresAt)) {
            $item->expiresAt = gmdate('Y-m-d\TH:i:s\Z', $this->expiresAt);
        }

        return $item;
    }

    protected function fromJsonObject($item)
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
