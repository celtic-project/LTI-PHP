<?php

namespace ceLTIc\LTI\Service;

use ceLTIc\LTI;
use ceLTIc\LTI\Platform;

/**
 * Class to implement the Line Item service
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class LineItem extends AssignmentGrade
{

    /**
     * Access scope.
     */
    public static $SCOPE = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem';

    /**
     * Read-only access scope.
     */
    public static $SCOPE_READONLY = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly';

    /**
     * Limit on size of container to be returned from requests.
     *
     * @var int|null  $limit
     */
    private $limit;

    /**
     * Line item media type.
     */
    private static $MEDIA_TYPE_LINE_ITEM = 'application/vnd.ims.lis.v2.lineitem+json';

    /**
     * Line item container media type.
     */
    private static $MEDIA_TYPE_LINE_ITEMS = 'application/vnd.ims.lis.v2.lineitemcontainer+json';

    /**
     * Class constructor.
     *
     * @param Platform     $platform   Platform object for this service request
     * @param string       $endpoint   Service endpoint
     * @param int|null     $limit      Limit of lineitems to be returned per request, null for all
     */
    public function __construct($platform, $endpoint, $limit = null)
    {
        $this->limit = $limit;
        parent::__construct($platform, $endpoint);
        $this->scope = self::$SCOPE_READONLY;
    }

    /**
     * Retrieve all line items.
     *
     * The returned items can be filtered by a resource link ID, a resource ID and/or a tag.  Requests can
     * also be limited to a number of items which may mean that multiple requests will be made to retrieve the
     * full list.
     *
     * @param string|null  $ltiResourceLinkId  LTI resource link ID
     * @param string|null  $resourceId         Tool resource ID
     * @param string|null  $tag                Tag
     * @param int|null     $limit              Limit of line items to be returned, null for service default
     *
     * @return LineItem[]|bool  Array of LineItem objects or false on error
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
        if (!empty($limit)) {
            $params['limit'] = $limit;
        } elseif (!empty($this->limit)) {
            $params['limit'] = $this->limit;
        }
        $lineItems = array();
        do {
            $this->mediaType = self::$MEDIA_TYPE_LINE_ITEMS;
            $http = $this->send('GET', $params);
            $url = '';
            if ($http->ok) {
                if (!empty($http->responseJson)) {
                    foreach ($http->responseJson as $lineItem) {
                        $lineItems[] = self::toLineItem($this->getPlatform(), $lineItem);
                    }
                }
                if (!empty($limit) && preg_match('/\<([^\>]+)\>; *rel=[\"next\"|next]/', $http->responseHeaders, $matches)) {
                    $url = $matches[1];
                    $this->endpoint = $url;
                }
            } else {
                $lineItems = false;
            }
        } while ($url);

        return $lineItems;
    }

    /**
     * Create a new line item.
     *
     * @param LTI\LineItem        $lineItem         Line item object
     *
     * @return bool  True if successful
     */
    public function createLineItem($lineItem)
    {
        $lineItem->endpoint = null;
        $this->scope = self::$SCOPE;
        $this->mediaType = self::$MEDIA_TYPE_LINE_ITEM;
        $http = $this->send('POST', null, self::toJson($lineItem));
        $this->scope = self::$SCOPE_READONLY;
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
     * Save a line item.
     *
     * @param LTI\LineItem        $lineItem         Line item object
     *
     * @return bool  True if successful
     */
    public function saveLineItem($lineItem)
    {
        $this->mediaType = self::$MEDIA_TYPE_LINE_ITEM;
        $http = $this->send('PUT', null, self::toJson($lineItem));
        $ok = $http->ok;
        if ($ok && !empty($http->responseJson)) {
            $savedLineItem = self::toLineItem($this->getPlatform(), $http->responseJson);
            foreach (get_object_vars($savedLineItem) as $key => $value) {
                $lineitem->$key = $value;
            }
        }

        return $ok;
    }

    /**
     * Delete a line item.
     *
     * @param LTI\LineItem        $lineItem         Line item object
     *
     * @return bool  True if successful
     */
    public function deleteLineItem($lineItem)
    {
        $this->mediaType = self::$MEDIA_TYPE_LINE_ITEM;
        $http = $this->send('DELETE');

        return $http->ok;
    }

    /**
     * Retrieve a line item.
     *
     * @param Platform     $platform   Platform object for this service request
     * @param string       $endpoint   Line item endpoint
     *
     * @return LTI\\LineItem|bool  LineItem object, or false on error
     */
    public static function getLineItem($platform, $endpoint)
    {
        $service = new self($platform, $endpoint);
        $service->mediaType = self::$MEDIA_TYPE_LINE_ITEM;
        $http = $service->send('GET');
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
     * Create a line item from a JSON object.
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
        } else {
            $lineItem = null;
        }

        return $lineItem;
    }

    /**
     * Create a JSON string from a line item.
     *
     * @param LTI\\LineItem     $lineItem   Line item object
     *
     * @return string    JSON representation of line item
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

        return json_encode($json);
    }

}
