<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Service;

use ceLTIc\LTI;
use ceLTIc\LTI\Platform;
use ceLTIc\LTI\SubmissionReview;
use ceLTIc\LTI\Util;

/**
 * Class to implement the Line-item service
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class LineItem extends AssignmentGrade
{

    /**
     * Line-item media type.
     */
    public const MEDIA_TYPE_LINE_ITEM = 'application/vnd.ims.lis.v2.lineitem+json';

    /**
     * Line-item container media type.
     */
    public const MEDIA_TYPE_LINE_ITEMS = 'application/vnd.ims.lis.v2.lineitemcontainer+json';

    /**
     * Access scope.
     */
    public static string $SCOPE = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem';

    /**
     * Read-only access scope.
     */
    public static string $SCOPE_READONLY = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly';

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
     * @param int|null $limit     Limit of line-items to be returned in each request, null for all
     * @param bool $pagingMode    True if only a single page should be requested when a limit is set
     */
    public function __construct(Platform $platform, string $endpoint, ?int $limit = null, bool $pagingMode = false)
    {
        parent::__construct($platform, $endpoint);
        $this->limit = $limit;
        $this->pagingMode = $pagingMode;
        $this->scope = self::$SCOPE;
    }

    /**
     * Retrieve all line-items.
     *
     * The returned items can be filtered by a resource link ID, a resource ID and/or a tag.  Requests can
     * also be limited to a number of items which may mean that multiple requests will be made to retrieve the
     * full list.
     *
     * @param string|null $ltiResourceLinkId  LTI resource link ID (optional)
     * @param string|null $resourceId         Tool resource ID (optional)
     * @param string|null $tag                Tag (optional)
     * @param int|null $limit                 Limit of line-items to be returned in each request, null for service default (optional)
     *
     * @return LTI\\LineItem[]|bool  Array of LineItem objects or false on error
     */
    public function getAll(?string $ltiResourceLinkId = null, ?string $resourceId = null, ?string $tag = null, ?int $limit = null): array|bool
    {
        $params = [];
        if (!empty($ltiResourceLinkId)) {
            $params['resource_link_id'] = $ltiResourceLinkId;
        }
        if (!empty($resourceId)) {
            $params['resource_id'] = $resourceId;
        }
        if (!empty($tag)) {
            $params['tag'] = $tag;
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
        $lineItems = [];
        $endpoint = $this->endpoint;
        do {
            $this->scope = self::$SCOPE_READONLY;
            $this->mediaType = self::MEDIA_TYPE_LINE_ITEMS;
            $http = $this->send('GET', $params);
            $this->scope = self::$SCOPE;
            $ok = $http->ok && !empty($http->responseJson);
            $url = '';
            if ($ok) {
                $items = Util::checkArray($http, 'responseJson');
                foreach ($items as $lineItemJson) {
                    if (!is_object($lineItemJson)) {
                        Util::setMessage(true, 'The array must comprise an array of objects (' . gettype($lineItemJson) . ' found)');
                    } else {
                        $lineItem = $this->toLineItem($this->getPlatform(), $lineItemJson);
                        if ($lineItem) {
                            $lineItems[] = $lineItem;
                        }
                    }
                }
                if (!$this->pagingMode && $http->hasRelativeLink('next')) {
                    $url = $http->getRelativeLink('next');
                    $this->endpoint = $url;
                    $params = [];
                }
            } else {
                $lineItems = false;
            }
        } while ($url);
        $this->endpoint = $endpoint;

        return $lineItems;
    }

    /**
     * Create a new line-item.
     *
     * @param LTI\LineItem $lineItem  Line-item object
     *
     * @return bool  True if successful
     */
    public function createLineItem(LTI\LineItem $lineItem): bool
    {
        $lineItem->endpoint = null;
        $this->mediaType = self::MEDIA_TYPE_LINE_ITEM;
        $http = $this->send('POST', null, self::toJson($lineItem));
        $ok = $http->ok && !empty($http->responseJson);
        if ($ok) {
            $newLineItem = $this->toLineItem($this->getPlatform(), $http->responseJson);
            if ($newLineItem) {
                foreach (get_object_vars($newLineItem) as $key => $value) {
                    $lineItem->$key = $value;
                }
            }
        }

        return $ok;
    }

    /**
     * Save a line-item.
     *
     * @param LTI\LineItem $lineItem  Line-item object
     *
     * @return bool  True if successful
     */
    public function saveLineItem(LTI\LineItem $lineItem): bool
    {
        $this->mediaType = self::MEDIA_TYPE_LINE_ITEM;
        $http = $this->send('PUT', null, self::toJson($lineItem));
        $ok = $http->ok;
        if ($ok && !empty($http->responseJson)) {
            $savedLineItem = $this->toLineItem($this->getPlatform(), $http->responseJson);
            if ($savedLineItem) {
            foreach (get_object_vars($savedLineItem) as $key => $value) {
                $lineItem->$key = $value;
            }
        }
        }

        return $ok;
    }

    /**
     * Delete a line-item.
     *
     * @param LTI\LineItem $lineItem  Line-item object
     *
     * @return bool  True if successful
     */
    public function deleteLineItem(LTI\LineItem $lineItem): bool
    {
        $this->mediaType = self::MEDIA_TYPE_LINE_ITEM;
        $http = $this->send('DELETE');

        return $http->ok;
    }

    /**
     * Retrieve a line-item.
     *
     * @return LTI\\LineItem|bool  LineItem object, or false on error
     */
    public function get(): LTI\LineItem|bool
    {
        $this->scope = self::$SCOPE_READONLY;
        $this->mediaType = self::MEDIA_TYPE_LINE_ITEM;
        $http = $this->send('GET');
        if ($http->ok && !empty($http->responseJson)) {
            if (!is_object($http->responseJson)) {
                Util::setMessage(true, 'The response must be an object (' . gettype($http->responseJson) . ' found)');
            } else {
                $lineItem = $this->toLineItem($this->getPlatform(), $http->responseJson);
                if (empty($lineItem)) {
                    $lineItem = false;
                }
            }
        } else {
            $lineItem = false;
        }

        return $lineItem;
    }

    /**
     * Retrieve a line-item.
     *
     * @deprecated Use LineItem::fromEndpoint() or get() instead
     *
     * @param Platform $platform  Platform object for this service request
     * @param string $endpoint    Line-item endpoint
     *
     * @return LTI\\LineItem|bool  LineItem object, or false on error
     */
    public static function getLineItem(Platform $platform, string $endpoint): LTI\LineItem|bool
    {
        Util::logDebug('Method ceLTIc\LTI\Service\LineItem::getLineItem has been deprecated; please use ceLTIc\LTI\LineItem::fromEndpoint or ceLTIc\LTI\Service\LineItem->get instead.',
            true);
        return LTI\LineItem::fromEndpoint($platform, $endpoint);
    }

###
###  PRIVATE METHODS
###

    /**
     * Create a line-item from a JSON object.
     *
     * @param Platform $platform  Platform object for this service request
     * @param object $json        JSON object to convert
     *
     * @return LTI\\LineItem|null  LineItem object, or null on error
     */
    private function toLineItem(Platform $platform, object $json): ?LTI\LineItem
    {
        $id = Util::checkString($json, 'id', true, true);
        $scoreMaximum = Util::checkNumber($json, 'scoreMaximum', true, 0, true);
        $label = Util::checkString($json, 'label', true, true);
        $resourceId = Util::checkString($json, 'resourceId');
        $tag = Util::checkString($json, 'tag');
        $startDateTime = Util::checkDateTime($json, 'startDateTime');
        $endDateTime = Util::checkDateTIme($json, 'endDateTime');
        $resourceLinkId = Util::checkString($json, 'resourceLinkId');
        if (!empty($id) && !empty($label) &&
            (!is_null($scoreMaximum) || (!empty($json->gradingScheme) && is_object($json->gradingScheme)))) {
            $lineItem = new LTI\LineItem($platform, $label, $scoreMaximum);
            if (!empty($json->gradingScheme)) {
                $lineItem->gradingScheme = GradingScheme::fromJsonObject($json->gradingScheme);
            }
                $lineItem->endpoint = $json->id;
            if (!empty($resourceLinkId)) {
                $lineItem->ltiResourceLinkId = $resourceLinkId;
            }
            if (!empty($resourceId)) {
                $lineItem->resourceId = $resourceId;
            }
            if (!empty($tag)) {
                $lineItem->tag = $tag;
            }
            if (!empty($startDateTime)) {
                $lineItem->submitFrom = $startDateTime;
            }
            if (!empty($endDateTime)) {
                $lineItem->submitUntil = $endDateTime;
            }
            if (!empty($json->submissionReview)) {
                if (is_object($json->submissionReview)) {
                $lineItem->submissionReview = SubmissionReview::fromJsonObject($json->submissionReview);
                } else {
                    Util::setMessage(true,
                        'The \'submissionReview\' element must be an object (' . gettype($json->submissionReviewJson) . ' found)');
                }
            }
        } else {
            $lineItem = null;
        }

        return $lineItem;
    }

    /**
     * Create a JSON string from a line-item.
     *
     * @param LTI\LineItem $lineItem  Line-item object
     *
     * @return string  JSON representation of line-item
     */
    private static function toJson(LTI\LineItem $lineItem): string
    {
        $json = new \stdClass();
        if (!empty($lineItem->endpoint)) {
            $json->id = $lineItem->endpoint;
        }
        if (!empty($lineItem->label)) {
            $json->label = $lineItem->label;
        }
        if (!empty($lineItem->pointsPossible)) {
            $json->scoreMaximum = $lineItem->pointsPossible;
        }
        if (!empty($lineItem->ltiResourceLinkId)) {
            $json->resourceLinkId = $lineItem->ltiResourceLinkId;
        }
        if (!empty($lineItem->resourceId)) {
            $json->resourceId = $lineItem->resourceId;
        }
        if (!empty($lineItem->tag)) {
            $json->tag = $lineItem->tag;
        }
        if (!empty($lineItem->submitFrom)) {
            $json->startDateTime = date('Y-m-d\TH:i:sP', $lineItem->submitFrom);
        }
        if (!empty($lineItem->submitUntil)) {
            $json->endDateTime = date('Y-m-d\TH:i:sP', $lineItem->submitUntil);
        }
        if (!empty($lineItem->submissionReview)) {
            $json->submissionReview = $lineItem->submissionReview->toJsonObject();
        }

        return json_encode($json);
    }

}
