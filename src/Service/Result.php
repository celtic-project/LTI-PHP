<?php

namespace ceLTIc\LTI\Service;

use ceLTIc\LTI\User;
use ceLTIc\LTI\Outcome;

/**
 * Class to implement the Result service
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class Result extends AssignmentGrade
{

    /**
     * Media type.
     */
    const RESULT_CONTAINER_MEDIA_TYPE = 'application/vnd.ims.lis.v2.resultcontainer+json';

    /**
     * Limit on size of container to be returned from requests.
     *
     * @var int|null  $limit
     */
    private $limit;

    /**
     * Class constructor.
     *
     * @param ToolConsumer $consumer   Tool consumer object for this service request
     * @param string       $endpoint   Service endpoint
     * @param int|null     $limit      Limit of results to be returned per request, null for all
     */
    public function __construct($consumer, $endpoint, $limit = null)
    {
        $this->limit = $limit;
        parent::__construct($consumer, $endpoint, self::RESULT_CONTAINER_MEDIA_TYPE, '/results');
    }

    /**
     * Retrieve all user outcome for a lineitem.
     *
     * @param int|null     $limit      Limit of results to be returned, null for service default
     *
     * @return Outcome[]|bool  Array of Outcome objects or false on error
     */
    public function getAll($limit = null)
    {
        $params = array();
        if (!empty($limit)) {
            $params['limit'] = $limit;
        } elseif (!empty($this->limit)) {
            $params['limit'] = $this->limit;
        }
        $outcomes = array();
        do {
            $http = $this->send('GET', $params);
            $url = '';
            if ($http->ok) {
                if (!empty($http->responseJson)) {
                    foreach ($http->responseJson as $outcome) {
                        $outcomes[] = self::getOutcome($outcome);
                    }
                }
                if (!empty($limit) && preg_match('/\<([^\>]+)\>; *rel=[\"next\"|next]/', $http->responseHeaders, $matches)) {
                    $url = $matches[1];
                    $this->endpoint = $url;
                }
            } else {
                $outcomes = false;
            }
        } while ($url);

        return $outcomes;
    }

    /**
     * Retrieve an outcome for a user.
     *
     * @param User        $user         User object
     *
     * @return Outcome|null|bool  Outcome object, or null if none, or false on error
     */
    public function get($user)
    {
        $params = array('user_id' => $user->ltiUserId);
        $http = $this->send('GET', $params);
        if ($http->ok) {
            if (!empty($http->responseJson)) {
                $outcome = self::getOutcome(reset($http->responseJson));
            } else {
                $outcome = null;
            }
            return $outcome;
        } else {
            return false;
        }
    }

    private static function getOutcome($json)
    {
        $outcome = new Outcome();
        $outcome->ltiUserId = $json->userId;
        if (isset($json->resultScore)) {
            $outcome->setValue($json->resultScore);
        }
        if (isset($json->resultMaximum)) {
            $outcome->setPointsPossible($json->resultMaximum);
        }
        if (isset($json->comment)) {
            $outcome->comment = $json->comment;
        }

        return $outcome;
    }

}
