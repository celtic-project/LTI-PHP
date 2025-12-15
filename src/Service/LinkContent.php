<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Service;

use ceLTIc\LTI\ContentItem\ContentItem;
use ceLTIc\LTI\Platform;
use ceLTIc\LTI\Util;

/**
 * Class to implement the Link and Content service
 *
 * This service can be used as either the context-level or item-level service.
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class LinkContent extends Service
{

    /**
     * Content item media type.
     */
    public const MEDIA_TYPE_CONTENT_ITEM = 'application/json';

    /**
     * Content item container media type.
     */
    public const MEDIA_TYPE_CONTENT_ITEMS = 'application/json';

    /**
     * Read access scope.
     */
    public static string $SCOPE_READ = 'https://purl.imsglobal.org/spec/lti/scope/contentitem.read';

    /**
     * Read access scope.
     */
    public static string $SCOPE_CREATE = 'https://purl.imsglobal.org/spec/lti/scope/contentitem.create';

    /**
     * Update access scope.
     */
    public static string $SCOPE_UPDATE = 'https://purl.imsglobal.org/spec/lti/scope/contentitem.update';

    /**
     * Delete access scope.
     */
    public static string $SCOPE_DELETE = 'https://purl.imsglobal.org/spec/lti/scope/contentitem.delete';

    /**
     * Default limit on size of container to be returned from requests.
     */
    public static ?int $defaultLimit = null;

    /**
     * Limit on size of container to be returned from requests.
     *
     * A limit of null (or zero) will disable paging of requests
     *
     * @var int|null $limit
     */
    private ?int $limit;

    /**
     * Whether requests should be made one page at a time when a limit is set.
     *
     * When false, all objects will be requested, even if this requires several requests based on the limit set.
     *
     * @var bool $pagingMode
     */
    private bool $pagingMode;

    /**
     * Class constructor.
     *
     * @param Platform $platform  Platform object for this service request
     * @param string $endpoint    Service endpoint
     * @param int|null $limit     Limit of content items to be returned in each request, null for all
     * @param bool $pagingMode    True if only a single page should be requested when a limit is set
     */
    public function __construct(Platform $platform, string $endpoint, ?int $limit = null, bool $pagingMode = false)
    {
        parent::__construct($platform, $endpoint);
        $this->limit = $limit;
        $this->pagingMode = $pagingMode;
        $this->scope = self::$SCOPE_READ;
    }

    /**
     * Retrieve all content items.
     *
     * The returned items can be filtered by a resource link ID.  Requests can also be limited to a number of items
     * which may mean that multiple requests will be made to retrieve the full list.
     *
     * @param string|null $ltiResourceLinkId  LTI resource link ID (optional)
     * @param int|null $limit                 Limit of content items to be returned in each request, null for service default (optional)
     *
     * @return ContentItem[]|bool  Array of ContentItem objects or false on error
     */
    public function getAll(?string $ltiResourceLinkId = null, ?int $limit = null): array|bool
    {
        $this->scope = self::$SCOPE_READ;
        $this->mediaType = self::MEDIA_TYPE_CONTENT_ITEMS;
        $params = [];
        if (!empty($ltiResourceLinkId)) {
            $params['resource_link_id'] = $ltiResourceLinkId;
        }
        if (is_null($limit)) {
            $limit = $this->limit;
        }
        if (is_null($limit)) {
            $limit = self::$defaultLimit;
        }
        if (!empty($limit)) {
            $params['limit'] = $limit;
        }
        $contentItems = [];
        $endpoint = $this->endpoint;
        do {
            $this->mediaType = self::MEDIA_TYPE_CONTENT_ITEMS;
            $http = $this->send('GET', $params);
            $ok = $http->ok && !is_null($http->responseJson);
            $url = '';
            if ($ok) {
                $items = Util::checkArray($http->responseJson, 'items', true);
                foreach ($items as $contentItemJson) {
                    if (!is_object($contentItemJson)) {
                        Util::setMessage(true,
                            'The items array must comprise an array of objects (' . gettype($contentItemJson) . ' found)');
                    } else {
                        $contentItem = ContentItem::fromJsonItem($contentItemJson);
                        if ($contentItem) {
                            $contentItems[] = $contentItem;
                        }
                    }
                }
                if (!$this->pagingMode && $http->hasRelativeLink('next')) {
                    $url = $http->getRelativeLink('next');
                    $this->endpoint = $url;
                    $params = [];
                }
            } else {
                $contentItems = false;
            }
        } while ($url);
        $this->endpoint = $endpoint;

        return $contentItems;
    }

    /**
     * Create a new content item.
     *
     * @param ContentItem $contentItem  Content item object
     *
     * @return bool  True if successful
     */
    public function createContentItem(ContentItem &$contentItem): bool
    {
        $this->scope = self::$SCOPE_CREATE;
        $this->mediaType = self::MEDIA_TYPE_CONTENT_ITEM;
        $http = $this->send('POST', null, $contentItem->toJson());
        $ok = $http->ok && !empty($http->responseJson);
        if ($ok) {
            $newContentItem = ContentItem::fromJsonItem($http->responseJson);
            if ($newContentItem) {
                $contentItem = $newContentItem;
            }
        }

        return $ok;
    }

    /**
     * Save a content item.
     *
     * @param ContentItem $contentItem  Content item object
     *
     * @return bool  True if successful
     */
    public function saveContentItem(ContentItem &$contentItem): bool
    {
        $this->scope = self::$SCOPE_UPDATE;
        $this->mediaType = self::MEDIA_TYPE_CONTENT_ITEM;
        $http = $this->send('PUT', null, $contentItem->toJson());
        $ok = $http->ok;
        if ($ok && !empty($http->responseJson)) {
            $savedContentItem = ContentItem::fromJsonItem($http->responseJson);
            if ($savedContentItem) {
                foreach (get_object_vars($savedContentItem) as $key => $value) {
                    $contentItem->$key = $value;
                }
            }
        }

        return $ok;
    }

    /**
     * Delete a content item.
     *
     * @return bool  True if successful
     */
    public function deleteContentItem(): bool
    {
        $this->scope = self::$SCOPE_DELETE;
        $this->mediaType = self::MEDIA_TYPE_CONTENT_ITEM;
        $http = $this->send('DELETE');

        return $http->ok;
    }

    /**
     * Retrieve a content item.
     *
     * @return ContentItem|bool  ContentItem object, or false on error
     */
    public function get(): ContentItem|bool
    {
        $contentItem = false;

        $this->scope = self::$SCOPE_READ;
        $this->mediaType = self::MEDIA_TYPE_CONTENT_ITEM;
        $http = $this->send('GET');
        if ($http->ok && !empty($http->responseJson)) {
            if (!is_object($http->responseJson)) {
                Util::setMessage(true, 'The response must be an object (' . gettype($http->responseJson) . ' found)');
            } else {
                $contentItem = ContentItem::fromJsonItem($http->responseJson);
                if (empty($contentItem)) {
                    $contentItem = false;
                }
            }
        }

        return $contentItem;
    }

}
