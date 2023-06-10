<?php

namespace ceLTIc\LTI\Service;

use ceLTIc\LTI;
use ceLTIc\LTI\Platform;

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
    const MEDIA_TYPE_LINE_ITEM = 'application/vnd.ims.lis.v2.lineitem+json';

    /**
     * Line-item container media type.
     */
    const MEDIA_TYPE_LINE_ITEMS = 'application/vnd.ims.lis.v2.lineitemcontainer+json';

    /**
     * Access scope.
     */
    public static $SCOPE = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem';

    /**
     * Read-only access scope.
     */
    public static $SCOPE_READONLY = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly';

    /**
     * Default limit on size of container to be returned from requests.
     */
    public static $defaultLimit = null;

    /**
     * Limit on size of container to be returned from requests.
     *
     * A limit of null (or zero) will disable paging of requests
     *
     * @var int|null  $limit
     */
    private $limit;

    /**
     * Whether requests should be made one page at a time when a limit is set.
     *
     * When false, all objects will be requested, even if this requires several requests based on the limit set.
     *
     * @var bool $pagingMode
     */
    private $pagingMode;

    /**
     * Class constructor.
     *
     * @param Platform     $platform   Platform object for this service request
     * @param string       $endpoint   Service endpoint
     * @param int|null     $limit      Limit of line-items to be returned in each request, null for all
     * @param bool         $pagingMode True if only a single page should be requested when a limit is set
     */
    public function __construct($platform, $endpoint, $limit = null, $pagingMode = false)
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
     * @param string|null  $ltiResourceLinkId  LTI resource link ID (optional)
     * @param string|null  $resourceId         Tool resource ID (optional)
     * @param string|null  $tag                Tag (optional)
     * @param int|null     $limit              Limit of line-items to be returned in each request, null for service default (optional)
     *
     * @return LTI\\LineItem[]|bool  Array of LineItem objects or false on error
     */
    public function getAll($ltiResourceLinkId = null, $resourceId = null, $tag = null, $limit = null)
    {
        $params = array();
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
        $lineItems = array();
        $endpoint = $this->endpoint;
        do {
            $this->scope = self::$SCOPE_READONLY;
            $this->mediaType = self::MEDIA_TYPE_LINE_ITEMS;
            $http = $this->send('GET', $params);
            $this->scope = self::$SCOPE;
            $url = '';
            if ($http->ok) {
                if (!empty($http->responseJson)) {
                    foreach ($http->responseJson as $lineItem) {
                        $lineItems[] = self::toLineItem($this->getPlatform(), $lineItem);
                    }
                }
                if (!$this->pagingMode && $http->hasRelativeLink('next')) {
                    $url = $http->getRelativeLink('next');
                    $this->endpoint = $url;
                    $params = array();
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
    public function createLineItem($lineItem)
    {
        $lineItem->endpoint = null;
        $this->mediaType = self::MEDIA_TYPE_LINE_ITEM;
        $http = $this->send('POST', null, self::toJson($lineItem));
        $ok = $http->ok && !empty($http->responseJson);
        if ($ok) {
            $newLineItem = self::toLineItem($this->getPlatform(), $http->responseJson);
            foreach (get_object_vars($newLineItem) as $key => $value) {
                $lineItem->$key = $value;
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
    public function saveLineItem($lineItem)
    {
        $this->mediaType = self::MEDIA_TYPE_LINE_ITEM;
        $http = $this->send('PUT', null, self::toJson($lineItem));
        $ok = $http->ok;
        if ($ok && !empty($http->responseJson)) {
            $savedLineItem = self::toLineItem($this->getPlatform(), $http->responseJson);
            foreach (get_object_vars($savedLineItem) as $key => $value) {
                $lineItem->$key = $value;
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
    public function deleteLineItem($lineItem)
    {
        $this->mediaType = self::MEDIA_TYPE_LINE_ITEM;
        $http = $this->send('DELETE');

        return $http->ok;
    }

    /**
     * Retrieve a line item.
     *
     * @param Platform     $platform   Platform object for this service request
     * @param string $endpoint    Line-item endpoint
     *
     * @return LTI\\LineItem|bool  LineItem object, or false on error
     */
    public static function getLineItem($platform, $endpoint)
    {
        $service = new self($platform, $endpoint);
        $service->scope = self::$SCOPE_READONLY;
        $service->mediaType = self::MEDIA_TYPE_LINE_ITEM;
        $http = $service->send('GET');
        $service->scope = self::$SCOPE;
        if ($http->ok && !empty($http->responseJson)) {
            $lineItem = self::toLineItem($platform, $http->responseJson);
        } else {
            $lineItem = false;
        }

        return $lineItem;
    }

###
###  PRIVATE METHODS
###

    /**
     * Create a line-item from a JSON object.
     *
     * @param Platform     $platform   Platform object for this service request
     * @param object       $json       JSON object to convert
     *
     * @return LTI\\LineItem|null  LineItem object, or null on error
     */
    private static function toLineItem($platform, $json)
    {
        if (!empty($json->id) && !empty($json->label) && !empty($json->scoreMaximum)) {
            $lineItem = new LTI\LineItem($platform, $json->label, $json->scoreMaximum);
            if (!empty($json->id)) {
                $lineItem->endpoint = $json->id;
            }
            if (!empty($json->resourceLinkId)) {
                $lineItem->ltiResourceLinkId = $json->resourceLinkId;
            }
            if (!empty($json->resourceId)) {
                $lineItem->resourceId = $json->resourceId;
            }
            if (!empty($json->tag)) {
                $lineItem->tag = $json->tag;
            }
            if (!empty($json->startDateTime)) {
                $lineItem->submitFrom = strtotime($json->startDateTime);
            }
            if (!empty($json->endDateTime)) {
                $lineItem->submitUntil = strtotime($json->endDateTime);
            }
            if (!empty($json->submissionReview)) {
                $lineItem->submissionReview = LTI\SubmissionReview::fromJsonObject($json->submissionReview);
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
    private static function toJson($lineItem)
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
