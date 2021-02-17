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
     * Class constructor.
     *
     * @param object       $context             The context to which the course groups apply
     * @param string       $groupsEndpoint      Service endpoint for course groups
     * @param string       $groupSetsEndpoint   Service endpoint for course groupsets (optional)
     */
    public function __construct($context, $groupsEndpoint, $groupSetsEndpoint = null)
    {
        $platform = $context->getPlatform();
        parent::__construct($platform, $groupsEndpoint);
        $this->scope = self::$SCOPE;
        $this->mediaType = self::MEDIA_TYPE_COURSE_GROUPS;
        $this->context = $context;
        $this->groupsEndpoint = $groupsEndpoint;
        $this->groupSetsEndpoint = $groupSetsEndpoint;
    }

    /**
     * Get the course group sets and groups.
     *
     * @param bool      $allowNonSets  Include groups which are not part of a set
     * @param int       $limit         Limit on the number of objects to be returned (optional, default is all)
     *
     * @return bool     True if the operation was successful
     */
    public function get($allowNonSets = false, $limit = 0)
    {
        $ok = $this->getGroupSets($limit);
        if ($ok) {
            $ok = $this->getGroups($limit);
        }
        if (!$ok) {
            $this->context->groupSets = null;
            $this->context->groups = null;
        }

        return $ok;
    }

    /**
     * Get the course group sets.
     *
     * @param int       $limit  Limit on the number of course group sets to be returned
     *
     * @return bool     True if the operation was successful
     */
    private function getGroupSets($limit)
    {
        $this->endpoint = $this->groupSetsEndpoint;
        $ok = !empty($this->endpoint);
        if ($ok) {
            $this->mediaType = self::MEDIA_TYPE_COURSE_GROUP_SETS;
            $parameters = array();
            if ($limit > 0) {
                $parameters['limit'] = strval($limit);
            }
            $http = $this->send('GET', $parameters);
            $ok = !empty($http) && $http->ok;
            if ($ok) {
                $this->context->groupSets = array();
                if (isset($http->responseJson->sets)) {
                    foreach ($http->responseJson->sets as $set) {
                        $this->context->groupSets[$set->id] = array('title' => $set->name, 'groups' => array(),
                            'num_members' => 0, 'num_staff' => 0, 'num_learners' => 0);
                    }
                }
            }
        }

        return $ok;
    }

    /**
     * Get the course groups.
     *
     * @param int       $limit  Limit on the number of course groups to be returned
     *
     * @return bool     True if the operation was successful
     */
    private function getGroups($limit)
    {
        $this->endpoint = $this->groupsEndpoint;
        $ok = !empty($this->endpoint);
        if ($ok) {
            $this->mediaType = self::MEDIA_TYPE_COURSE_GROUPS;
            $parameters = array();
            if ($limit > 0) {
                $parameters['limit'] = strval($limit);
            }
            $http = $this->send('GET', $parameters);
            $ok = !empty($http) && $http->ok;
            if ($ok) {
                if (is_null($this->context->groupSets)) {
                    $this->context->groupSets = array();
                }
                $this->context->groups = array();
                if (isset($http->responseJson->groups)) {
                    foreach ($http->responseJson->groups as $agroup) {
                        $group = array('title' => $agroup->name);
                        if (!empty($agroup->set_id)) {
                            if (!array_key_exists($agroup->set_id, $this->context->groupSets)) {
                                $this->context->groupSets[$agroup->set_id] = array('title' => "Set {$agroup->set_id}", 'groups' => array(),
                                    'num_members' => 0, 'num_staff' => 0, 'num_learners' => 0);
                            }
                            $this->context->groupSets[$agroup->set_id]['groups'][] = $agroup->id;
                            $group['set'] = $agroup->set_id;
                        }
                        if (!empty($agroup->tag)) {
                            $group['tag'] = $agroup->tag;
                        }
                        $this->context->groups[$agroup->id] = $group;
                    }
                }
            }
        }

        return $ok;
    }

}
