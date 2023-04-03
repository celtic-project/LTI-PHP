<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Service;

use ceLTIc\LTI;
use ceLTIc\LTI\Context;
use ceLTIc\LTI\ResourceLink;
use ceLTIc\LTI\Enum\IdScope;

/**
 * Class to implement the Membership service
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class Membership extends Service
{

    /**
     * Media type for version 1 of Memberships service.
     */
    const MEDIA_TYPE_MEMBERSHIPS_V1 = 'application/vnd.ims.lis.v2.membershipcontainer+json';

    /**
     * Media type for Names and Role Provisioning service.
     */
    const MEDIA_TYPE_MEMBERSHIPS_NRPS = 'application/vnd.ims.lti-nrps.v2.membershipcontainer+json';

    /**
     * Access scope.
     */
    public static string $SCOPE = 'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly';

    /**
     * Default limit on size of container to be returned from requests.
     */
    public static int $defaultLimit = 100;

    /**
     * The object to which the memberships apply (ResourceLink or Context).
     *
     * @var Context|ResourceLink $source
     */
    private Context|ResourceLink|null $source = null;

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
     * @param object $source    The object to which the memberships apply (ResourceLink or Context)
     * @param string $endpoint  Service endpoint
     * @param string $format    Format to request
     * @param int|null $limit   Limit of line-items to be returned in each request, null for all
     * @param bool $pagingMode  True if only a single page should be requested when a limit is set
     */
    public function __construct(ResourceLink|Context $source, string $endpoint, string $format = self::MEDIA_TYPE_MEMBERSHIPS_V1,
        ?int $limit = null, bool $pagingMode = false)
    {
        $platform = $source->getPlatform();
        parent::__construct($platform, $endpoint);
        $this->scope = self::$SCOPE;
        $this->mediaType = $format;
        $this->source = $source;
        $this->limit = $limit;
        $this->pagingMode = $pagingMode;
    }

    /**
     * Get the memberships.
     *
     * @param string|null $role  Role for which memberships are to be requested (optional, default is all roles)
     * @param int|null $limit    Limit on the number of memberships to be returned in each request, null for service default (optional)
     *
     * @return array|bool  The array of UserResult objects if successful, otherwise false
     */
    public function get(?string $role = null, ?int $limit = null): array|bool
    {
        return $this->getMembers(false, $role, $limit);
    }

    /**
     * Get the memberships.
     *
     * @param string|null $role  Role for which memberships are to be requested (optional, default is all roles)
     * @param int|null $limit    Limit on the number of memberships to be returned in each request, null for service default (optional)
     *
     * @return array|bool  The array of UserResult objects if successful, otherwise false
     */
    public function getWithGroups(?string $role = null, ?int $limit = null): array|bool
    {
        return $this->getMembers(true, $role, $limit);
    }

    /**
     * Get the memberships.
     *
     * @param bool $withGroups   True is group information is to be requested as well
     * @param string|null $role  Role for which memberships are to be requested (optional, default is all roles)
     * @param int|null $limit    Limit on the number of memberships to be returned in each request, null for service default (optional)
     *
     * @return array|bool  The array of UserResult objects if successful, otherwise false
     */
    private function getMembers(bool $withGroups, ?string $role = null, ?int $limit = null): array|bool
    {
        $isLink = is_a($this->source, 'ceLTIc\LTI\ResourceLink');
        $parameters = [];
        if (!empty($role)) {
            $parameters['role'] = $role;
        }
        if (is_null($limit)) {
            $limit = $this->limit;
        }
        if (is_null($limit)) {
            $limit = self::$defaultLimit;
        }
        if (!empty($limit)) {
            $parameters['limit'] = strval($limit);
        }
        if ($isLink) {
            $context = $this->source->getContext();
            if (!empty($this->source->getId())) {
                $parameters['rlid'] = $this->source->getId();
            }
            if ($withGroups && ($this->mediaType === self::MEDIA_TYPE_MEMBERSHIPS_NRPS) && !empty($context)) {
                $context->getGroups();
                $this->source->groupSets = $context->groupSets;
                $this->source->groups = $context->groups;
                $parameters['groups'] = 'true';
            }
        } elseif ($withGroups && ($this->mediaType === self::MEDIA_TYPE_MEMBERSHIPS_NRPS)) {
            $this->source->getGroups();
            $parameters['groups'] = 'true';
        }
        $userResults = [];
        $memberships = [];
        $endpoint = $this->endpoint;
        do {
            $http = $this->send('GET', $parameters);
            $url = '';
            if (!empty($http) && $http->ok) {
                $isjsonld = false;
                if (isset($http->responseJson->pageOf) && isset($http->responseJson->pageOf->membershipSubject) &&
                    isset($http->responseJson->pageOf->membershipSubject->membership)) {
                    $isjsonld = true;
                    $memberships = array_merge($memberships, $http->responseJson->pageOf->membershipSubject->membership);
                    if (!empty($http->responseJson->nextPage) && !empty($http->responseJson->pageOf->membershipSubject->membership)) {
                        $http->relativeLinks['next'] = $http->responseJson->nextPage;
                    }
                } elseif (isset($http->responseJson->members)) {
                    $memberships = array_merge($memberships, $http->responseJson->members);
                }
                if (!$this->pagingMode && $http->hasRelativeLink('next')) {
                    $url = $http->getRelativeLink('next');
                    $this->endpoint = $url;
                    $parameters = [];
                }
            } else {
                $userResults = false;
            }
        } while ($url);
        $this->endpoint = $endpoint;
        if ($userResults !== false) {
            if ($isLink) {
                $oldUsers = $this->source->getUserResultSourcedIDs(true, IdScope::Resource);
            }
            foreach ($memberships as $membership) {
                if ($isjsonld) {
                    $member = $membership->member;
                    if ($isLink) {
                        $userResult = LTI\UserResult::fromResourceLink($this->source, $member->userId);
                    } else {
                        $userResult = new LTI\UserResult();
                        $userResult->ltiUserId = $member->userId;
                    }

// Set the user name
                    $firstname = $member->givenName ?? '';
                    $middlename = $member->middleName ?? '';
                    $lastname = $member->familyName ?? '';
                    $fullname = $member->name ?? '';
                    $userResult->setNames($firstname, $lastname, $fullname);

// Set the sourcedId
                    if (isset($member->sourcedId)) {
                        $userResult->sourcedId = $member->sourcedId;
                    }

// Set the username
                    if (isset($member->ext_username)) {
                        $userResult->username = $member->ext_username;
                    } elseif (isset($member->ext_user_username)) {
                        $userResult->username = $member->ext_user_username;
                    } elseif (isset($member->custom_username)) {
                        $userResult->username = $member->custom_username;
                    } elseif (isset($member->custom_user_username)) {
                        $userResult->username = $member->custom_user_username;
                    }

// Set the user email
                    $email = $member->email ?? '';
                    $userResult->setEmail($email, $this->source->getPlatform()->defaultEmail);

// Set the user roles
                    if (isset($membership->role)) {
                        $roles = $this->parseContextsInArray($http->responseJson->{'@context'}, $membership->role);
                        $userResult->roles = LTI\Tool::parseRoles($roles, LTI\Enum\LtiVersion::V2);
                    }

// If a result sourcedid is provided save the user
                    if ($isLink) {
                        $doSave = false;
                        if (isset($membership->message)) {
                            foreach ($membership->message as $message) {
                                if (isset($message->message_type) && (($message->message_type === 'basic-lti-launch-request') || ($message->message_type) === 'LtiResourceLinkRequest')) {
                                    if (isset($message->lis_result_sourcedid)) {
                                        if (empty($userResult->ltiResultSourcedId) || ($userResult->ltiResultSourcedId !== $message->lis_result_sourcedid)) {
                                            $userResult->ltiResultSourcedId = $message->lis_result_sourcedid;
                                            $doSave = true;
                                        }
                                    } elseif ($userResult->isLearner() && empty($userResult->created)) {  // Ensure all learners are recorded in case Assignment and Grade services are used
                                        $userResult->ltiResultSourcedId = '';
                                        $doSave = true;
                                    }
                                    if (isset($message->ext)) {
                                        if (empty($userResult->username)) {
                                            if (!empty($message->ext->username)) {
                                                $userResult->username = $message->ext->username;
                                            } elseif (!empty($message->ext->user_username)) {
                                                $userResult->username = $message->ext->user_username;
                                            }
                                        }
                                    }
                                    if (isset($message->custom)) {
                                        if (empty($userResult->username)) {
                                            if (!empty($message->custom->username)) {
                                                $userResult->username = $message->custom->username;
                                            } elseif (!empty($message->custom->user_username)) {
                                                $userResult->username = $message->custom->user_username;
                                            }
                                        }
                                    }
                                    break;
                                }
                            }
                        } elseif ($userResult->isLearner() && empty($userResult->created)) {  // Ensure all learners are recorded in case Assignment and Grade services are used
                            $userResult->ltiResultSourcedId = '';
                            $doSave = true;
                        }
                        if (!$doSave && isset($member->resultSourcedId)) {
                            $userResult->setResourceLinkId($this->source->getId());
                            $userResult->ltiResultSourcedId = $member->resultSourcedId;
                            $doSave = true;
                        }
                        if ($doSave) {
                            $userResult->save();
                        }
                    }
                    $userResults[] = $userResult;

// Remove old user (if it exists)
                    if ($isLink) {
                        unset($oldUsers[$userResult->getId(IdScope::Resource)]);
                    }
                } else {  // Version 2
                    $member = $membership;
                    if ($isLink) {
                        $userResult = LTI\UserResult::fromResourceLink($this->source, $member->user_id);
                    } else {
                        $userResult = new LTI\UserResult();
                        $userResult->ltiUserId = $member->user_id;
                    }

// Set the user name
                    $firstname = $member->given_name ?? '';
                    $middlename = $member->middle_name ?? '';
                    $lastname = $member->family_name ?? '';
                    $fullname = $member->name ?? '';
                    $userResult->setNames($firstname, $lastname, $fullname);

// Set the sourcedId
                    if (isset($member->lis_person_sourcedid)) {
                        $userResult->sourcedId = $member->lis_person_sourcedid;
                    }

// Set the user email
                    $email = $member->email ?? '';
                    $userResult->setEmail($email, $this->source->getPlatform()->defaultEmail);

// Set the user roles
                    if (isset($member->roles)) {
                        $userResult->roles = LTI\Tool::parseRoles($member->roles, LTI\Enum\LtiVersion::V2);
                    }

// If a result sourcedid is provided save the user
                    if ($isLink) {
                        $doSave = false;
                        if (isset($member->message)) {
                            $messages = $member->message;
                            if (!is_array($messages)) {
                                $messages = [$member->message];
                            }
                            foreach ($messages as $message) {
                                if (isset($message->{'https://purl.imsglobal.org/spec/lti/claim/message_type'}) && (($message->{'https://purl.imsglobal.org/spec/lti/claim/message_type'} === 'basic-lti-launch-request') || ($message->{'https://purl.imsglobal.org/spec/lti/claim/message_type'}) === 'LtiResourceLinkRequest')) {
                                    if (isset($message->{'https://purl.imsglobal.org/spec/lti-bo/claim/basicoutcome'}) &&
                                        isset($message->{'https://purl.imsglobal.org/spec/lti-bo/claim/basicoutcome'}->lis_result_sourcedid)) {
                                        $userResult->ltiResultSourcedId = $message->{'https://purl.imsglobal.org/spec/lti-bo/claim/basicoutcome'}->lis_result_sourcedid;
                                        $doSave = true;
                                    } elseif ($userResult->isLearner() && empty($userResult->created)) {  // Ensure all learners are recorded in case Assignment and Grade services are used
                                        $userResult->ltiResultSourcedId = '';
                                        $doSave = true;
                                    }
                                    if (isset($message->{'https://purl.imsglobal.org/spec/lti/claim/ext'})) {
                                        if (empty($userResult->username)) {
                                            if (!empty($message->{'https://purl.imsglobal.org/spec/lti/claim/ext'}->username)) {
                                                $userResult->username = $message->{'https://purl.imsglobal.org/spec/lti/claim/ext'}->username;
                                            } elseif (!empty($message->{'https://purl.imsglobal.org/spec/lti/claim/ext'}->user_username)) {
                                                $userResult->username = $message->{'https://purl.imsglobal.org/spec/lti/claim/ext'}->user_username;
                                            }
                                        }
                                    }
                                    if (isset($message->{'https://purl.imsglobal.org/spec/lti/claim/custom'})) {
                                        if (empty($userResult->username)) {
                                            if (!empty($message->{'https://purl.imsglobal.org/spec/lti/claim/custom'}->username)) {
                                                $userResult->username = $message->{'https://purl.imsglobal.org/spec/lti/claim/custom'}->username;
                                            } elseif (!empty($message->{'https://purl.imsglobal.org/spec/lti/claim/custom'}->user_username)) {
                                                $userResult->username = $message->{'https://purl.imsglobal.org/spec/lti/claim/custom'}->user_username;
                                            }
                                        }
                                    }
                                    break;
                                }
                            }
                        } elseif ($userResult->isLearner() && empty($userResult->created)) {  // Ensure all learners are recorded in case Assignment and Grade services are used
                            $userResult->ltiResultSourcedId = '';
                            $doSave = true;
                        }
                        if ($doSave) {
                            $userResult->save();
                        }
                    }
                    $userResults[] = $userResult;
                    if (isset($member->group_enrollments)) {
                        $userResult->groups = [];
                        foreach ($member->group_enrollments as $group) {
                            $groupId = $group->group_id;
                            if (empty($this->source->groups) || !array_key_exists($groupId, $this->source->groups)) {
                                $this->source->groups[$groupId] = [
                                    'title' => "Group {$groupId}"
                                ];
                            }
                            if (!empty($this->source->groups[$groupId]['set'])) {
                                $this->source->groupSets[$this->source->groups[$groupId]['set']]['num_members']++;
                                if ($userResult->isStaff()) {
                                    $this->source->groupSets[$this->source->groups[$groupId]['set']]['num_staff']++;
                                }
                                if ($userResult->isLearner()) {
                                    $this->source->groupSets[$this->source->groups[$groupId]['set']]['num_learners']++;
                                }
                            }
                            $userResult->groups[] = $groupId;
                        }
                    }

// Remove old user (if it exists)
                    if ($isLink) {
                        unset($oldUsers[$userResult->getId(IdScope::Resource)]);
                    }
                }
            }

/// Delete any old users which were not in the latest list from the platform if request is not paged
            if ($isLink && !$this->pagingMode) {
                foreach ($oldUsers as $id => $userResult) {
                    $userResult->delete();
                }
            }
        }

        return $userResults;
    }

}
