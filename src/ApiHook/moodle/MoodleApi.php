<?php

namespace ceLTIc\LTI\ApiHook\moodle;

use ceLTIc\LTI\UserResult;
use ceLTIc\LTI\Http\HttpMessage;

/**
 * Class to handle Moodle web service requests.
 *
 * @author  Tony Butler <a.butler4@lancaster.ac.uk>
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
trait MoodleApi
{

    /**
     * Default items per page.
     */
    private static $DEFAULT_PER_PAGE = 50;

    /**
     * The Moodle site URL
     */
    private $url = null;

    /**
     * The Moodle API token
     */
    private $token = null;

    /**
     * Course ID
     */
    private $courseId = null;

    /**
     * Resource link or context source object
     */
    private $sourceObject = null;

    /**
     * Get memberships.
     *
     * @param bool    $withGroups True is group information is to be requested as well
     *
     * @return mixed Array of UserResult objects or False if the request was not successful
     */
    private function get($withGroups)
    {
        $consumer = $this->sourceObject->getConsumer();
        $this->url = $consumer->getSetting('moodle.url');
        $this->token = $consumer->getSetting('moodle.token');
        $perPage = $consumer->getSetting('moodle.per_page', '');
        if (!is_numeric($perPage)) {
            $perPage = self::$DEFAULT_PER_PAGE;
        } else {
            $perPage = intval($perPage);
        }
        $prefix = $consumer->getSetting('moodle.grouping_prefix');
        if ($this->url && $this->token && $this->courseId) {
            if ($withGroups) {
                $this->setGroupings($prefix);
            }
            $users = $this->getUsers($perPage, $withGroups);
            if ($users && $withGroups) {
                $this->setGroups($users);
            }
        } else {
            $users = false;
        }

        return $users;
    }

    /**
     * Set groupings for resource link.
     *
     * @param string $perPage  Maximum number of records per request
     * @param string $prefix  Group set name prefix
     */
    private function setGroupings($prefix)
    {
        $this->sourceObject->groupSets = array();
        $this->sourceObject->groups = array();
        $params = array(
            'courseid' => $this->courseId
        );
        $courseGroupings = $this->callMoodleApi('core_group_get_course_groupings', $params);
        if (is_array($courseGroupings)) {
            $groupingIds = array_map(function($grouping) {
                return $grouping->id;
            }, $courseGroupings);
            $params = array(
                'groupingids' => $groupingIds,
                'returngroups' => 1
            );
            $groupings = $this->callMoodleApi('core_group_get_groupings', $params);
            if (is_array($groupings)) {
                foreach ($groupings as $grouping) {
                    if (!empty($grouping->groups) && (empty($prefix) || (strpos($grouping->name, $prefix) === 0))) {
                        $groupingId = strval($grouping->id);
                        $this->sourceObject->groupSets[$groupingId] = array('title' => $grouping->name, 'groups' => array(),
                            'num_members' => 0, 'num_staff' => 0, 'num_learners' => 0);
                        foreach ($grouping->groups as $group) {
                            $groupId = strval($group->id);
                            $this->sourceObject->groupSets[$groupingId]['groups'][] = $groupId;
                            $this->sourceObject->groups[$groupId] = array('title' => $group->name, 'set' => $groupingId);
                        }
                    }
                }
            }
        }
    }

    /**
     * Get users enrolled in course.
     *
     * @param string $perPage  Maximum number of records per request
     * @param bool $withGroups True is group information is to be requested as well
     *
     * @return mixed Array of UserResult objects or False if the request was not successful
     */
    private function getUsers($perPage, $withGroups)
    {
        $users = array();

        $params = array(
            'courseid' => $this->courseId,
            'options' => array(
                array(
                    'name' => 'onlyactive',
                    'value' => 1
                ),
                array(
                    'name' => 'userfields',
                    'value' => 'id'
                ),
                array(
                    'name' => 'withcapability',
                    'value' => 'mod/lti:manage'
                )
            )
        );
        $teachers = array();
        $enrolments = $this->callMoodleApi('core_enrol_get_enrolled_users', $params);
        if (is_array($enrolments)) {
            foreach ($enrolments as $enrolment) {
                $teachers[] = $enrolment->id;
            }
        }
        $userFields = 'id, username, firstname, lastname, email, roles';
        if ($withGroups) {
            $userFields .= ', groups';
        }
        $params = array(
            'courseid' => $this->courseId,
            'options' => array(
                array(
                    'name' => 'onlyactive',
                    'value' => 1
                ),
                array(
                    'name' => 'userfields',
                    'value' => $userFields
                )
            )
        );
        if ($perPage > 0) {
            array_push($params['options'],
                array(
                    'name' => 'limitnumber',
                    'value' => $perPage
                )
            );
        }
        $n = 0;
        do {
            if ($perPage > 0) {
                array_push($params['options'],
                    array(
                        'name' => 'limitfrom',
                        'value' => $n
                    )
                );
            }
            $enrolments = $this->callMoodleApi('core_enrol_get_enrolled_users', $params);
            if (is_array($enrolments)) {
                foreach ($enrolments as $enrolment) {
                    $userId = strval($enrolment->id);
                    if (is_a($this->sourceObject, 'ceLTIc\LTI\ResourceLink')) {
                        $user = UserResult::fromResourceLink($this->sourceObject, $userId);
                    } else {
                        $user = new UserResult();
                        $user->ltiUserId = $userId;
                    }
                    $user->setEmail($enrolment->email, $this->sourceObject->getConsumer()->defaultEmail);
                    $user->setNames($enrolment->firstname, $enrolment->lastname, $enrolment->fullname);
                    if (!empty($enrolment->groups)) {
                        foreach ($enrolment->groups as $group) {
                            $groupId = strval($group->id);
                            if (array_key_exists($groupId, $this->sourceObject->groups)) {
                                $user->groups[] = $groupId;
                            }
                        }
                    }
                    // Add Instructor or Learner role - NB no check is made for the Administrator role
                    if (in_array($enrolment->id, $teachers)) {
                        $user->roles[] = 'urn:lti:role:ims/lis/Instructor';
                    } else {
                        $user->roles[] = 'urn:lti:role:ims/lis/Learner';
                    }
                    $users[$userId] = $user;
                }
                if ($perPage > 0) {
                    $n += count($enrolments);
                    array_pop($params['options']);
                }
            } else {
                $users = false;
                break;
            }
        } while (is_array($enrolments) && !empty($enrolments));

        return $users;
    }

    /**
     * Set groups for users.
     *
     * @param array $users  Array of UserResult objects
     */
    private function setGroups($users)
    {
        foreach ($users as $user) {
            $sets = array();
            foreach ($user->groups as $group) {
                if (array_key_exists($group, $this->sourceObject->groups)) {
                    $setId = $this->sourceObject->groups[$group]['set'];
                    // Check that user is not a member of another group in the same grouping
                    if (in_array($setId, $sets)) {
                        // Remove grouping and groups
                        foreach ($users as $user2) {
                            foreach ($user2->groups as $groupId) {
                                if ($this->sourceObject->groups[$groupId]['set'] === $setId) {
                                    unset($user2->groups[$groupId]);
                                }
                            }
                        }
                        foreach ($this->sourceObject->groupSets[$setId]['groups'] as $groupId) {
                            unset($this->sourceObject->groups[$groupId]);
                        }
                        unset($this->sourceObject->groupSets[$setId]);
                    } elseif (array_key_exists($group, $this->sourceObject->groups)) {
                        $this->sourceObject->groupSets[$setId]['num_members'] ++;
                        if ($user->isStaff()) {
                            $this->sourceObject->groupSets[$setId]['num_staff'] ++;
                        }
                        if ($user->isLearner()) {
                            $this->sourceObject->groupSets[$setId]['num_learners'] ++;
                        }
                        $sets[] = $setId;
                    }
                }
            }
        }
    }

    /**
     * Call the specified Moodle API method, passing the parameters provided.
     *
     * @param string $method The API method to call
     * @param array $params The params to pass
     *
     * @return array|null The decoded response
     */
    private function callMoodleApi($method, $params)
    {
        $json = null;
        $serviceUrl = $this->url . '/webservice/rest/server.php';
        $params = array_merge(array(
            'wstoken' => $this->token,
            'wsfunction' => $method,
            'moodlewsrestformat' => 'json'
            ), $params);
        $http = new HttpMessage($serviceUrl, 'POST', $params);
        $http->send();
        if ($http->ok) {
            $json = json_decode($http->response);
            $http->ok = !is_null($json) && is_array($json);
        }

        return $json;
    }

}
