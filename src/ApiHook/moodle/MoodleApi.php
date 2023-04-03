<?php

namespace ceLTIc\LTI\ApiHook\moodle;

use ceLTIc\LTI\Context;
use ceLTIc\LTI\ResourceLink;
use ceLTIc\LTI\UserResult;
use ceLTIc\LTI\Http\HttpMessage;
use ceLTIc\LTI\Util;

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
    private static int $DEFAULT_PER_PAGE = 50;

    /**
     * The Moodle site URL
     */
    private ?string $url = null;

    /**
     * The Moodle API token
     */
    private ?string $token = null;

    /**
     * Course ID
     */
    private ?string $courseId = null;

    /**
     * Resource link or context source object
     */
    private ResourceLink|Context|null $sourceObject = null;

    /**
     * Check if the API hook has been configured.
     *
     * @return bool  True if the API hook has been configured
     */
    public function isConfigured(): bool
    {
        $platform = $this->sourceObject->getPlatform();

        return !empty($platform->getSetting('moodle.url')) && !empty($platform->getSetting('moodle.token'));
    }

    /**
     * Get memberships.
     *
     * @param bool $withGroups  True is group information is to be requested as well
     *
     * @return array|bool  Array of UserResult objects or False if the request was not successful
     */
    private function get(bool $withGroups): array|bool
    {
        $platform = $this->sourceObject->getPlatform();
        $this->url = $platform->getSetting('moodle.url');
        $this->token = $platform->getSetting('moodle.token');
        $perPage = $platform->getSetting('moodle.per_page', '');
        if (!is_numeric($perPage)) {
            $perPage = self::$DEFAULT_PER_PAGE;
        } else {
            $perPage = intval($perPage);
        }
        $prefix = $platform->getSetting('moodle.grouping_prefix');
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
     * @param string $prefix  Group set name prefix
     *
     * @return bool  True if the request was successful
     */
    private function setGroupings(string $prefix): bool
    {
        $ok = false;
        $this->sourceObject->groupSets = [];
        $this->sourceObject->groups = [];
        $params = [
            'courseid' => $this->courseId
        ];
        $courseGroupings = $this->callMoodleApi('core_group_get_course_groupings', $params);
        if (is_array($courseGroupings)) {
            $groupingIds = array_map(function($grouping) {
                return $grouping->id;
            }, $courseGroupings);
            if (empty($groupingIds)) {
                $ok = true;
            } else {
                $params = [
                    'groupingids' => $groupingIds,
                    'returngroups' => 1
                ];
                $groupings = $this->callMoodleApi('core_group_get_groupings', $params);
                if (is_array($groupings)) {
                    $ok = true;
                    foreach ($groupings as $grouping) {
                        if (!empty($grouping->groups) && (empty($prefix) || str_starts_with($grouping->name, $prefix))) {
                            $groupingId = strval($grouping->id);
                            $this->sourceObject->groupSets[$groupingId] = [
                                'title' => $grouping->name,
                                'groups' => [],
                                'num_members' => 0,
                                'num_staff' => 0,
                                'num_learners' => 0
                            ];
                            foreach ($grouping->groups as $group) {
                                $groupId = strval($group->id);
                                $this->sourceObject->groupSets[$groupingId]['groups'][] = $groupId;
                                if (!isset($this->sourceObject->groups[$groupId])) {
                                    $this->sourceObject->groups[$groupId] = [
                                        'title' => $group->name,
                                        'set' => $groupingId
                                    ];
                                } elseif (!is_array($this->sourceObject->groups[$groupId]['set'])) {
                                    $this->sourceObject->groups[$groupId]['set'] = [$this->sourceObject->groups[$groupId]['set'], $groupingId];
                                } else {
                                    $this->sourceObject->groups[$groupId]['set'][] = $groupingId;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $ok;
    }

    /**
     * Get users enrolled in course.
     *
     * @param string $perPage   Maximum number of records per request
     * @param bool $withGroups  True is group information is to be requested as well
     *
     * @return array|bool  Array of UserResult objects or False if the request was not successful
     */
    private function getUsers(string $perPage, bool $withGroups): array|bool
    {
        $users = [];
        $params = [
            'courseid' => $this->courseId,
            'options' => [
                [
                    'name' => 'onlyactive',
                    'value' => 1
                ],
                [
                    'name' => 'userfields',
                    'value' => 'id'
                ],
                [
                    'name' => 'withcapability',
                    'value' => 'mod/lti:manage'
                ]
            ]
        ];
        $teachers = [];
        $enrolments = $this->callMoodleApi('core_enrol_get_enrolled_users', $params);
        if (is_array($enrolments)) {
            foreach ($enrolments as $enrolment) {
                $teachers[] = $enrolment->id;
            }
        }
        $userFields = 'id, username, idnumber, firstname, lastname, fullname, email, roles';
        if ($withGroups) {
            $userFields .= ', groups';
        }
        $params = [
            'courseid' => $this->courseId,
            'options' => [
                [
                    'name' => 'onlyactive',
                    'value' => 1
                ],
                [
                    'name' => 'userfields',
                    'value' => $userFields
                ]
            ]
        ];
        if ($perPage > 0) {
            array_push($params['options'],
                [
                    'name' => 'limitnumber',
                    'value' => $perPage
                ]
            );
        }
        $n = 0;
        do {
            if ($perPage > 0) {
                array_push($params['options'],
                    [
                        'name' => 'limitfrom',
                        'value' => $n
                    ]
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
                    $user->setEmail($enrolment->email, $this->sourceObject->getPlatform()->defaultEmail);
                    $user->setNames($enrolment->firstname, $enrolment->lastname, $enrolment->fullname);
                    $user->username = $enrolment->username;
                    if (!empty($enrolment->idnumber)) {
                        $user->sourcedId = $enrolment->idnumber;
                    } else {
                        $user->sourcedId = null;
                    }
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
     *
     * @return void
     */
    private function setGroups(array $users): void
    {
        foreach ($users as $user) {
            $sets = [];
            foreach ($user->groups as $group) {
                if (array_key_exists($group, $this->sourceObject->groups) && !empty($this->sourceObject->groups[$group]['set'])) {
                    $setIds = $this->sourceObject->groups[$group]['set'];
                    if (!is_array($setIds)) {
                        $setIds = [$setIds];
                    }
                    foreach ($setIds as $setId) {
                        // Check that user is not a member of another group in the same grouping
                        if (in_array($setId, $sets)) {
                            // Remove groups but leave grouping as empty to acknowledge its existence in the platform
                            foreach ($this->sourceObject->groupSets[$setId]['groups'] as $groupId) {
                                if (!is_array($this->sourceObject->groups[$groupId]['set']) && ($this->sourceObject->groups[$groupId]['set'] === $setId)) {
                                    unset($this->sourceObject->groups[$groupId]['set']);
                                } else if (is_array($this->sourceObject->groups[$groupId]['set']) && in_array($setId,
                                        $this->sourceObject->groups[$groupId]['set'])) {
                                    $pos = array_search($setId, $this->sourceObject->groups[$groupId]['set']);
                                    unset($this->sourceObject->groups[$groupId]['set'][$pos]);
                                    if (empty($this->sourceObject->groups[$groupId]['set'])) {
                                        unset($this->sourceObject->groups[$groupId]['set']);
                                    }
                                }
                            }
                        } elseif (array_key_exists($group, $this->sourceObject->groups)) {
                            $this->sourceObject->groupSets[$setId]['num_members']++;
                            if ($user->isStaff()) {
                                $this->sourceObject->groupSets[$setId]['num_staff']++;
                            }
                            if ($user->isLearner()) {
                                $this->sourceObject->groupSets[$setId]['num_learners']++;
                            }
                            $sets[] = $setId;
                        }
                    }
                }
            }
        }
    }

    /**
     * Call the specified Moodle API method, passing the parameters provided.
     *
     * @param string $method  The API method to call
     * @param array $params   The parameters to pass
     *
     * @return array|null  The decoded response
     */
    private function callMoodleApi(string $method, array $params): ?array
    {
        $json = null;
        $serviceUrl = $this->url . '/webservice/rest/server.php';
        $params = array_merge([
            'wstoken' => $this->token,
            'wsfunction' => $method,
            'moodlewsrestformat' => 'json'
            ], $params);
        $http = new HttpMessage($serviceUrl, 'POST', $params);
        $http->send();
        if ($http->ok) {
            $json = Util::json_decode($http->response);
            $http->ok = !is_null($json) && is_array($json);
            if (!$http->ok) {
                Util::logError("Moodle web service returned an error: {$http->response}");
            }
        }

        return $json;
    }

}
