<?php

namespace ceLTIc\LTI\Service;

use ceLTIc\LTI;
use ceLTIc\LTI\Context;

/**
 * Class to implement the Course Groups service
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class Groups extends Service
{

    /**
     * Media type for course group sets service.
     */
    const MEDIA_TYPE_COURSE_GROUP_SETS = 'application/vnd.ims.lti-gs.v1.contextgroupsetcontainer+json';

    /**
     * Media type for course groups service.
     */
    const MEDIA_TYPE_COURSE_GROUPS = 'application/vnd.ims.lti-gs.v1.contextgroupcontainer+json';

    /**
     * Access scope.
     */
    public static $SCOPE = 'https://purl.imsglobal.org/spec/lti-gs/scope/contextgroup.readonly';

    /**
     * The context to which the course groups apply.
     *
     * @var Context $context
     */
    private $context = null;

    /**
     * The endpoint for course group requests.
     *
     * @var string $groupsEndpoint
     */
    private $groupsEndpoint = null;

    /**
     * The endpoint for course groupset requests.
     *
     * @var string $groupSetsEndpoint
     */
    private $groupSetsEndpoint = null;

    /**
     * Limit on size of container to be returned from requests.
     *
     * @var int|null  $limit
     */
    private $limit;

    /**
     * Class constructor.
     *
     * @param object       $context             The context to which the course groups apply
     * @param string       $groupsEndpoint      Service endpoint for course groups
     * @param string       $groupSetsEndpoint   Service endpoint for course group sets (optional)
     * @param int|null     $limit               Limit of objects to be returned in each request, null for all
     */
    public function __construct($context, $groupsEndpoint, $groupSetsEndpoint = null, $limit = null)
    {
        $platform = $context->getPlatform();
        parent::__construct($platform, $groupsEndpoint);
        $this->scope = self::$SCOPE;
        $this->mediaType = self::MEDIA_TYPE_COURSE_GROUPS;
        $this->context = $context;
        $this->groupsEndpoint = $groupsEndpoint;
        $this->groupSetsEndpoint = $groupSetsEndpoint;
        $this->limit = $limit;
    }

    /**
     * Get the course group sets and groups.
     *
     * @param bool      $allowNonSets  Include groups which are not part of a set (optional)
     * @param int|null  $limit         Limit on the number of objects to be returned in each request, null for service default (optional)
     * @param string    $userId        Limit response to groups for specified user (optional)
     *
     * @return bool     True if the operation was successful
     */
    public function get($allowNonSets = false, $limit = null, $userId = null)
    {
        $ok = $this->getGroupSets($limit);
        if ($ok) {
            $ok = $this->getGroups($allowNonSets, $limit, $userId);
        }
        if (!$ok) {
            $this->context->groupSets = null;
            $this->context->groups = null;
        } elseif (!empty($userId)) {  // Remove any group sets which do not apply to the specified user
            $groupSets = array();
            foreach ($this->context->groups as $group) {
                if (!empty($group['set'])) {
                    $groupSets[$group['set']] = $this->context->groupSets[$group['set']];
                }
            }
            $this->context->groupSets = $groupSets;
        }

        return $ok;
    }

    /**
     * Get the course group sets.
     *
     * @param int|null  $limit  Limit on the number of course group sets to be returned in each request, null for service default (optional)
     *
     * @return bool     True if the operation was successful
     */
    public function getGroupSets($limit = null)
    {
        $this->endpoint = $this->groupSetsEndpoint;
        $ok = !empty($this->endpoint);
        if ($ok) {
            $this->mediaType = self::MEDIA_TYPE_COURSE_GROUP_SETS;
            $parameters = array();
            if (is_null($limit)) {
                $limit = $this->limit;
            }
            if (!empty($limit)) {
                $parameters['limit'] = strval($limit);
            }
            $this->context->groupSets = array();
            $groupSets = array();
            $endpoint = $this->endpoint;
            do {
                $http = $this->send('GET', $parameters);
                $ok = !empty($http) && $http->ok;
                $url = '';
                if ($ok) {
                    if (isset($http->responseJson->sets)) {
                        foreach ($http->responseJson->sets as $set) {
                            $groupSets[$set->id] = array('title' => $set->name, 'groups' => array(),
                                'num_members' => 0, 'num_staff' => 0, 'num_learners' => 0);
                        }
                    }
                    if (preg_match('/\<([^\>]+)\>; *rel=(\"next\"|next)/', $http->responseHeaders, $matches)) {
                        $url = $matches[1];
                        $this->endpoint = $url;
                        $parameters = array();
                    }
                }
            } while ($url);
            $this->endpoint = $endpoint;
            if ($ok) {
                $this->context->groupSets = $groupSets;
            }
        }

        return $ok;
    }

    /**
     * Get the course groups.
     *
     * @param bool      $allowNonSets  Include groups which are not part of a set (optional)
     * @param int|null  $limit         Limit on the number of course groups to be returned in each request, null for service default (optional)
     * @param string    $userId        Limit response to groups for specified user (optional)
     *
     * @return bool     True if the operation was successful
     */
    public function getGroups($allowNonSets = false, $limit = null, $userId = null)
    {
        $this->endpoint = $this->groupsEndpoint;
        $ok = !empty($this->endpoint);
        if ($ok) {
            $this->mediaType = self::MEDIA_TYPE_COURSE_GROUPS;
            $parameters = array();
            if (is_null($limit)) {
                $limit = $this->limit;
            }
            if (!empty($limit)) {
                $parameters['limit'] = strval($limit);
            }
            if (!empty($userId)) {
                $parameters['user_id'] = $userId;
            }
            if (is_null($this->context->groupSets)) {
                $groupSets = array();
            } else {
                $groupSets = $this->context->groupSets;
            }
            $groups = array();
            $endpoint = $this->endpoint;
            do {
                $http = $this->send('GET', $parameters);
                $ok = !empty($http) && $http->ok;
                $url = '';
                if ($ok) {
                    if (isset($http->responseJson->groups)) {
                        foreach ($http->responseJson->groups as $agroup) {
                            if (!$allowNonSets && empty($agroup->set_id)) {
                                continue;
                            }
                            $group = array('title' => $agroup->name);
                            if (!empty($agroup->set_id)) {
                                if (!array_key_exists($agroup->set_id, $groupSets)) {
                                    $groupSets[$agroup->set_id] = array('title' => "Set {$agroup->set_id}", 'groups' => array(),
                                        'num_members' => 0, 'num_staff' => 0, 'num_learners' => 0);
                                }
                                $groupSets[$agroup->set_id]['groups'][] = $agroup->id;
                                $group['set'] = $agroup->set_id;
                            }
                            if (!empty($agroup->tag)) {
                                $group['tag'] = $agroup->tag;
                            }
                            $groups[$agroup->id] = $group;
                        }
                    }
                    if (preg_match('/\<([^\>]+)\>; *rel=(\"next\"|next)/', $http->responseHeaders, $matches)) {
                        $url = $matches[1];
                        $this->endpoint = $url;
                        $parameters = array();
                    }
                }
            } while ($url);
            $this->endpoint = $endpoint;
            if ($ok) {
                $this->context->groupSets = $groupSets;
                $this->context->groups = $groups;
            }
        }

        return $ok;
    }

}
