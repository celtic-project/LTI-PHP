<?php
declare(strict_types=1);

namespace ceLTIc\LTI\ApiHook\canvas;

use ceLTIc\LTI\Context;
use ceLTIc\LTI\ResourceLink;
use ceLTIc\LTI\UserResult;
use ceLTIc\LTI\Http\HttpMessage;
use ceLTIc\LTI\Util;

/**
 * Class to handle Canvas web service requests.
 *
 * @author  Simon Booth <s.p.booth@stir.ac.uk>
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
trait CanvasApi
{

    /**
     * Default items per page.
     */
    private static int $DEFAULT_PER_PAGE = 50;

    /**
     * The Canvas domain
     */
    private ?string $domain = null;

    /**
     * The Canvas API token
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

        return !empty($platform->getSetting('canvas.domain')) && !empty($platform->getSetting('canvas.token'));
    }

    /**
     * Get memberships.
     *
     * @param bool $withGroups  True is group information is to be requested as well
     *
     * @return array|bool  Array of UserResult objects or false if the request was not successful
     */
    private function get(bool $withGroups): array|bool
    {
        $platform = $this->sourceObject->getPlatform();
        $this->domain = $platform->getSetting('canvas.domain');
        $this->token = $platform->getSetting('canvas.token');
        $this->courseId = $this->sourceObject->getSetting('custom_canvas_course_id');
        $perPage = $platform->getSetting('canvas.per_page', strval(self::$DEFAULT_PER_PAGE));
        if (!is_numeric($perPage)) {
            $perPage = self::$DEFAULT_PER_PAGE;
        }
        $prefix = $platform->getSetting('canvas.group_set_prefix');
        if ($this->domain && $this->token && $this->courseId) {
            if ($withGroups) {
                $this->setGroupSets($perPage, $prefix);
            }
            $users = $this->getUsers($perPage, $withGroups);
            if ($withGroups) {
                $this->setGroups($perPage, $users);
            }
        } else {
            $users = false;
        }

        return $users;
    }

    /**
     * Set group sets for resource link.
     *
     * @param string $perPage  Maximum number of records per request
     * @param string $prefix   Group set name prefix
     *
     * @return bool  True if the request was successful
     */
    private function setGroupSets(string $perPage, string $prefix): bool
    {
        $this->sourceObject->groupSets = [];
        $url = "https://{$this->domain}/api/v1/courses/{$this->courseId}/group_categories";
        if ($perPage > 0) {
            $url .= "?per_page={$perPage}";
        }
        do {
            $http = new HttpMessage($url, 'GET', null, "Authorization: Bearer {$this->token}");
            $http->send();
            if ($http->ok) {
                $allCategories = Util::jsonDecode($http->response);
                $http->ok = !is_null($allCategories) && is_array($allCategories);
            }
            $url = '';
            if ($http->ok) {
                foreach ($allCategories as $category) {
                    if (empty($prefix) || str_starts_with($category->name, $prefix)) {
                        $this->sourceObject->groupSets[strval($category->id)] = [
                            'title' => $category->name,
                            'groups' => [],
                            'num_members' => 0,
                            'num_staff' => 0,
                            'num_learners' => 0
                        ];
                    }
                }
                if (preg_match('/\<([^\>]+)\>; *rel=\"next\"/', implode("\n", $http->responseHeaders), $matches)) {
                    $url = $matches[1];
                }
            }
        } while ($url);

        return $http->ok;
    }

    /**
     * Get roles for users enrolled in course.
     *
     * @param string $perPage  Maximum number of records per request
     *
     * @return array|bool  Array of UserResult objects or false if the request was not successful
     */
    private function getRoles(string $perPage): array|bool
    {
        $roles = [];

        $url = "https://{$this->domain}/api/v1/courses/{$this->courseId}/enrollments?state[]=invited&state[]=active&state[]=completed";
        if ($perPage > 0) {
            $url .= "&per_page={$perPage}";
        }
        do {
            $http = new HttpMessage($url, 'GET', null, "Authorization: Bearer {$this->token}");
            $http->send();
            if ($http->ok) {
                $enrolments = Util::jsonDecode($http->response);
                $http->ok = !is_null($enrolments) && is_array($enrolments);
            }
            $url = '';
            if ($http->ok) {
                foreach ($enrolments as $enrolment) {
                    $roles[strval($enrolment->user_id)] = $enrolment->type;
                }
                if (preg_match('/\<([^\>]+)\>; *rel=\"next\"/', implode("\n", $http->responseHeaders), $matches)) {
                    $url = $matches[1];
                }
            } else {
                $roles = false;
            }
        } while ($url);

        return $roles;
    }

    /**
     * Get users enrolled in course.
     *
     * @param string $perPage   Maximum number of records per request
     * @param bool $withGroups  True is group information is to be requested as well
     *
     * @return array|bool  Array of UserResult objects or false if the request was not successful
     */
    private function getUsers(string $perPage, bool $withGroups): array|bool
    {
        $users = [];
        $url = "https://{$this->domain}/api/v1/courses/{$this->courseId}/users?state[]=invited&state[]=active&state[]=completed";
        if ($perPage > 0) {
            $url .= "&per_page={$perPage}";
        }
        if ($withGroups) {
            $url .= '&include[]=group_ids';
        }
        $roles = $this->getRoles($perPage);
        do {
            $http = new HttpMessage($url, 'GET', null, "Authorization: Bearer {$this->token}");
            $http->send();
            if ($http->ok) {
                $enrolments = Util::jsonDecode($http->response);
                $http->ok = !is_null($enrolments) && is_array($enrolments);
            }
            $url = '';
            if ($http->ok) {
                foreach ($enrolments as $enrolment) {
                    $userId = strval($enrolment->id);
                    if (array_key_exists($userId, $users)) {
                        $user = $users[$userId];
                    } else {
                        if (is_a($this->sourceObject, 'ceLTIc\LTI\ResourceLink')) {
                            $user = UserResult::fromResourceLink($this->sourceObject, $userId);
                        } else {
                            $user = new UserResult();
                            $user->ltiUserId = $userId;
                        }
                    }
                    $user->setNames('', '', $enrolment->name);
                    $user->setEmail($enrolment->email, $this->sourceObject->getPlatform()->defaultEmail);
                    $user->username = $enrolment->login_id;
                    $user->sourcedId = $enrolment->sis_user_id;
                    if (!empty($enrolment->group_ids)) {
                        foreach ($enrolment->group_ids as $groupId) {
                            $user->groups[] = strval($groupId);
                        }
                    }
                    if (array_key_exists($userId, $roles)) {
                        switch ($roles[$userId]) {
                            case 'StudentEnrollment':
                                $user->roles[] = 'urn:lti:role:ims/lis/Learner';
                                break;
                            case 'TeacherEnrollment':
                                $user->roles[] = 'urn:lti:role:ims/lis/Instructor';
                                break;
                            case 'TaEnrollment':
                                $user->roles[] = 'urn:lti:role:ims/lis/TeachingAssistant';
                                break;
                            case 'DesignerEnrollment':
                                $user->roles[] = 'urn:lti:role:ims/lis/ContentDeveloper';
                                break;
                            case 'ObserverEnrollment':
                                $user->roles[] = 'urn:lti:instrole:ims/lis/Observer';
                                $user->roles[] = 'urn:lti:role:ims/lis/Mentor';
                                break;
                        }
                    }
                    $users[$userId] = $user;
                }
                if (preg_match('/\<([^\>]+)\>; *rel=\"next\"/', implode("\n", $http->responseHeaders), $matches)) {
                    $url = $matches[1];
                }
            } else {
                $users = false;
            }
        } while ($url);

        return $users;
    }

    /**
     * Set groups for users.
     *
     * @param string $perPage  Maximum number of records per request
     * @param array $users     Array of UserResult objects
     *
     * @return bool  True if the request was successful
     */
    private function setGroups(string $perPage, array $users): bool
    {
        $this->sourceObject->groups = [];
        $url = "https://{$this->domain}/api/v1/courses/{$this->courseId}/groups";
        if ($perPage > 0) {
            $url .= "?per_page={$perPage}";
        }
        do {
            $http = new HttpMessage($url, 'GET', null, "Authorization: Bearer {$this->token}");
            $http->send();
            if ($http->ok) {
                $allGroups = Util::jsonDecode($http->response);
                $http->ok = !is_null($allGroups) && is_array($allGroups);
            }
            $url = '';
            if ($http->ok) {
                foreach ($allGroups as $group) {
                    $setId = strval($group->group_category_id);
                    if (array_key_exists($setId, $this->sourceObject->groupSets)) {
                        $groupId = strval($group->id);
                        $this->sourceObject->groups[$groupId] = [
                            'title' => $group->name,
                            'set' => $setId
                        ];
                        foreach ($users as $user) {
                            if (in_array($groupId, $user->groups)) {
                                $this->sourceObject->groupSets[$setId]['num_members']++;
                                if ($user->isStaff()) {
                                    $this->sourceObject->groupSets[$setId]['num_staff']++;
                                }
                                if ($user->isLearner()) {
                                    $this->sourceObject->groupSets[$setId]['num_learners']++;
                                }
                                if (!in_array($groupId, $this->sourceObject->groupSets[$setId]['groups'])) {
                                    $this->sourceObject->groupSets[$setId]['groups'][] = $groupId;
                                }
                            }
                        }
                    }
                }
                if (preg_match('/\<([^\>]+)\>; *rel=\"next\"/', implode("\n", $http->responseHeaders), $matches)) {
                    $url = $matches[1];
                }
            }
        } while ($url);

        return $http->ok;
    }

}
