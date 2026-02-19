<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Service;

use ceLTIc\LTI;
use ceLTIc\LTI\Context;
use ceLTIc\LTI\ResourceLink;
use ceLTIc\LTI\Enum\IdScope;
use ceLTIc\LTI\Enum\LtiVersion;
use ceLTIc\LTI\Util;

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
    public const MEDIA_TYPE_MEMBERSHIPS_V1 = 'application/vnd.ims.lis.v2.membershipcontainer+json';

    /**
     * Media type for Names and Role Provisioning service.
     */
    public const MEDIA_TYPE_MEMBERSHIPS_NRPS = 'application/vnd.ims.lti-nrps.v2.membershipcontainer+json';

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
        $ok = true;
        $userResults = [];
        $memberships = [];
        $endpoint = $this->endpoint;
        do {
            $http = $this->send('GET', $parameters);
            $ok = $http->ok;
            $url = '';
            if ($ok) {
                $members = [];
                if ($this->mediaType === self::MEDIA_TYPE_MEMBERSHIPS_V1) {
                    if (empty(Util::checkString($http->responseJson, '@type', true, true, 'Page', false))) {
                        $ok = $ok && !Util::$strictMode;
                    }
                    if (!isset($http->responseJson->pageOf)) {
                        $ok = false;
                        Util::setMessage(true, 'The pageOf element is missing');
                    } else {
                        if (empty(Util::checkString($http->responseJson->pageOf, 'pageOf/@type', true, true,
                                    'LISMembershipContainer', false))) {
                            $ok = $ok && !Util::$strictMode;
                        }
                        if (isset($http->responseJson->pageOf->membershipSubject)) {
                            if (empty(Util::checkString($http->responseJson->pageOf->membershipSubject,
                                        'pageOf/membershipSubject/@type', true, true, 'Context', false))) {
                                $ok = $ok && !Util::$strictMode;
                            }
                            if (empty(Util::checkString($http->responseJson->pageOf->membershipSubject,
                                        'pageOf/membershipSubject/contextId'))) {
                                $ok = $ok && !Util::$strictMode;
                            }
                            $members = Util::checkArray($http->responseJson->pageOf->membershipSubject,
                                'pageOf/membershipSubject/membership');
                        }
                    }
                    if (!empty($http->responseJson->nextPage) && !empty($members)) {
                        $http->relativeLinks['next'] = $http->responseJson->nextPage;
                    }
                } else {  // Version 2 (NRPS)
                    if (!isset($http->responseJson->context)) {
                        if (Util::$strictMode) {
                            $ok = false;
                            Util::setMessage(true, 'The context element is missing');
                        } else {
                            Util::setMessage(false, 'The context element should be present');
                        }
                    } elseif (empty(Util::checkString($http->responseJson->context, 'context/id'))) {
                        $ok = $ok && !Util::$strictMode;
                    }
                    $members = Util::checkArray($http->responseJson, 'members');
                    if ($ok && !$this->pagingMode && $http->hasRelativeLink('next')) {
                        $url = $http->getRelativeLink('next');
                        $this->endpoint = $url;
                        $parameters = [];
                    }
                }
                $memberships = array_merge($memberships, $members);
            }
        } while ($url);
        $this->endpoint = $endpoint;
        if ($ok) {
            if ($isLink) {
                $oldUsers = $this->source->getUserResultSourcedIDs(true, IdScope::Resource);
            }
            if ($this->mediaType === self::MEDIA_TYPE_MEMBERSHIPS_V1) {
                if (!empty($http->responseJson->{'@context'})) {
                    $contexts = $http->responseJson->{'@context'};
                    if (is_string($contexts)) {
                        $contexts = [$contexts];
                    } elseif (!is_array($contexts)) {
                        $contexts = [];
                    }
                } else {
                    $contexts = [];
                }
            }
            foreach ($memberships as $membership) {
                if ($this->mediaType === self::MEDIA_TYPE_MEMBERSHIPS_V1) {
                    if (isset($membership->member)) {
                        $member = $membership->member;
                    } else {
                        Util::setMessage(true, 'The membership/member element is missing');
                        $member = null;
                    }
                    if (empty(Util::checkArray($membership, 'membership/role', true, true)) && Util::$strictMode) {
                        $member = null;
                    }
                    if ($isLink) {
                        $messages = Util::checkArray($membership, 'membership/message');
                    }
                    if (!empty($member)) {
                        $userid = null;
                        if (!empty($member->userId)) {
                            $userid = $member->userId;
                        } elseif (!empty($member->{'@id'})) {
                            $userid = $member->{'@id'};
                        }
                        if (empty($userid)) {
                            Util::setMessage(true, 'The membership/member/userid or @id element is missing');
                        } elseif (!is_string($userid)) {
                            if (Util::$strictMode) {
                                Util::setMessage(true, 'The membership/member/userid or @id element must have a string ');
                                $userid = null;
                            } else {
                                Util::setMessage(false, 'The membership/member/userid or @id element should have a string ');
                                $userid = LTI\Util::valToString($userid);
                            }
                        }
                        $roles = [];
                        $stringroles = Util::checkArray($membership, 'membership/role', true, true);
                        foreach ($stringroles as $role) {
                            if (!is_string($role)) {
                                if (Util::$strictMode) {
                                    Util::setMessage(true, 'The membership/role element must only comprise string values');
                                    $userid = null;
                                } else {
                                    Util::setMessage(false, 'The membership/role element should only comprise string values');
                                }
                            } else {
                                $roles[] = $role;
                            }
                        }
                        if (!empty($userid)) {
                            if ($isLink) {
                                $userResult = LTI\UserResult::fromResourceLink($this->source, $userid);
                            } else {
                                $userResult = new LTI\UserResult();
                                $userResult->ltiUserId = $userid;
                            }

// Set the user name
                            $firstname = Util::checkString($member, 'membership/member/givenName');
                            $middlename = Util::checkString($member, 'membership/member/middleName');
                            $lastname = Util::checkString($member, 'membership/member/familyName');
                            $fullname = Util::checkString($member, 'membership/member/name');
                            $userResult->setNames($firstname, $lastname, $fullname, $middlename);

// Set the sourcedId
                            if (isset($member->sourcedId)) {
                                $userResult->sourcedId = Util::checkString($member, 'membership/member/sourcedId');
                            }

// Set the user email
                            $email = Util::checkString($member, 'membership/member/email');
                            $userResult->setEmail($email, $this->source->getPlatform()->defaultEmail);

// Set the user roles
                            if (!empty($roles)) {
                                $roles = $this->parseContextsInArray($contexts, $roles);
                                $ltiVersion = $this->getPlatform()->ltiVersion;
                                if (empty($ltiVersion)) {
                                    $ltiVersion = LtiVersion::V1;
                                }
                                $userResult->roles = LTI\Tool::parseRoles($roles, $ltiVersion);
                            }

// If a result sourcedid is provided save the user
                            if ($isLink) {
                                $doSave = false;
                                if (is_array($messages)) {
                                    foreach ($messages as $message) {
                                        if (!is_object($message)) {
                                            Util::setMessage(true,
                                                'The membership/message element must comprise an array of objects (' . gettype($message) . ' found)');
                                            continue;
                                        } else {
                                            if (isset($message->ext)) {
                                                if (!is_object($message->ext)) {
                                                    Util::setMessage(true,
                                                        'The membership/message/ext element must be an object (' . gettype($message->ext) . ' found)');
                                                }
                                            }
                                            if (isset($message->custom)) {
                                                if (!is_object($message->custom)) {
                                                    Util::setMessage(true,
                                                        'The membership/message/custom element must be an object (' . gettype($message->custom) . ' found)');
                                                }
                                            }
                                        }
                                        if (!isset($message->message_type)) {
                                            Util::setMessage(true,
                                                'The membership/message elements must include a \'message_type\' property');
                                        } elseif (($message->message_type === 'basic-lti-launch-request') || ($message->message_type === 'LtiResourceLinkRequest')) {
                                            if (isset($message->lis_result_sourcedid)) {
                                                $sourcedid = Util::checkString($message, 'membership/message/lis_result_sourcedid');
                                                if (empty($userResult->ltiResultSourcedId) || ($userResult->ltiResultSourcedId !== $sourcedid)) {
                                                    $userResult->ltiResultSourcedId = $sourcedid;
                                                    $doSave = true;
                                                }
                                            } elseif ($userResult->isLearner() && empty($userResult->created)) {  // Ensure all learners are recorded in case Assignment and Grade services are used
                                                $userResult->ltiResultSourcedId = '';
                                                $doSave = true;
                                            }
                                            $username = null;
                                            if (is_object($message->ext)) {
                                                if (!empty($message->ext->username)) {
                                                    $username = Util::checkString($message->ext, 'membership/message/ext/username');
                                                } elseif (!empty($message->ext->user_username)) {
                                                    $username = Util::checkString($message->ext,
                                                        'membership/message/ext/user_username');
                                                }
                                            }
                                            if (empty($username) && is_object($message->custom)) {
                                                if (!empty($message->custom->username)) {
                                                    $username = Util::checkString($message->custom,
                                                        'membership/message/custom/username');
                                                } elseif (!empty($message->custom->user_username)) {
                                                    $username = Util::checkString($message->custom,
                                                        'membership/message/custom/user_username');
                                                }
                                            }
                                            if (!empty($username)) {
                                                $userResult->username = $username;
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
                                    $userResult->ltiResultSourcedId = Util::checkString($member, 'membership/member/resultSourcedId');
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
                        }
                    }
                } else {  // Version 2 (NRPS)
                    $member = $membership;
                    $userid = null;
                    $userid = Util::checkString($member, 'members/user_id', true);
                    $roles = [];
                    $stringroles = Util::checkArray($member, 'members/roles', true, true);
                    foreach ($stringroles as $role) {
                        if (!is_string($role)) {
                            if (Util::$strictMode) {
                                Util::setMessage(true, 'The members/roles element must only comprise string values');
                                $userid = null;
                            } else {
                                Util::setMessage(false, 'The members/roles element should only comprise string values');
                            }
                        } else {
                            $roles[] = $role;
                        }
                    }
                }
                if ($isLink) {
                    if (isset($member->message)) {
                        $messages = $member->message;
                        if (!is_array($messages)) {
                            if (Util::$strictMode) {
                                $userid = null;
                                Util::setMessage(true,
                                    'The members/message element must have an array value (' . gettype($member->message) . ' found)');
                                $messages = [];
                            } else {
                                Util::setMessage(false,
                                    'The members/message element should have an array value (' . gettype($membership->message) . ' found)');
                                if (is_object($messages)) {
                                    $messages = (array) $messages;
                                } else {
                                    $messages = [];
                                }
                            }
                        }
                    } else {
                        $messages = [];
                    }
                }
                if (!empty($userid)) {
                    if ($isLink) {
                        $userResult = LTI\UserResult::fromResourceLink($this->source, $userid);
                    } else {
                        $userResult = new LTI\UserResult();
                        $userResult->ltiUserId = $userid;
                    }

// Set the user name
                    $firstname = Util::checkString($member, 'members/given_name');
                    $middlename = Util::checkString($member, 'members/middle_name');
                    $lastname = Util::checkString($member, 'members/family_name');
                    $fullname = Util::checkString($member, 'members/name');
                    $userResult->setNames($firstname, $lastname, $fullname, $middlename);

// Set the sourcedId
                    if (isset($member->lis_person_sourcedid)) {
                        $userResult->sourcedId = Util::checkString($member, 'members/lis_person_sourcedid');
                    }

// Set the user email
                    $email = Util::checkString($member, 'email');
                    $userResult->setEmail($email, $this->source->getPlatform()->defaultEmail);

// Set the user roles
                    if (!empty($roles)) {
                        $ltiVersion = $this->getPlatform()->ltiVersion;
                        if (empty($ltiVersion)) {
                            $ltiVersion = LtiVersion::V1;
                        }
                        $userResult->roles = LTI\Tool::parseRoles($roles, $ltiVersion);
                    }

// If a result sourcedid is provided save the user
                    $groupenrollments = [];
                    if ($isLink) {
                        $doSave = false;
                        if (is_array($messages)) {
                            foreach ($messages as $message) {
                                if (!is_object($message)) {
                                    Util::setMessage(true,
                                        'The members/message element must comprise an array of objects (' . gettype($message) . ' found)');
                                    continue;
                                } else {
                                    if (isset($message->{'https://purl.imsglobal.org/spec/lti/claim/ext'})) {
                                        if (!is_object($message->{'https://purl.imsglobal.org/spec/lti/claim/ext'})) {
                                            Util::setMessage(true,
                                                'The members/message/https://purl.imsglobal.org/spec/lti/claim/ext element must be an object (' . gettype($message->{'https://purl.imsglobal.org/spec/lti/claim/ext'}) . ' found)');
                                        }
                                    }
                                    if (isset($message->{'https://purl.imsglobal.org/spec/lti/claim/custom'})) {
                                        if (!is_object($message->{'https://purl.imsglobal.org/spec/lti/claim/custom'})) {
                                            Util::setMessage(true,
                                                'The members/message/https://purl.imsglobal.org/spec/lti/claim/custom element must be an object (' . gettype($message->{'https://purl.imsglobal.org/spec/lti/claim/custom'}) . ' found)');
                                        }
                                    }
                                    if (isset($member->group_enrollments)) {
                                        if (!is_array($member->group_enrollments)) {
                                            if (Util::$strictMode) {
                                                Util::setMessage(true,
                                                    'The members/message/group_enrollments element must be an array (' . gettype($member->group_enrollments) . ' found)');
                                            } else {
                                                Util::setMessage(false,
                                                    'The members/message/group_enrollments element should be an array (' . gettype($member->group_enrollments) . ' found)');
                                                if (is_object($member->group_enrollments)) {
                                                    $groupenrollments = (array) $member->group_enrollments;
                                                }
                                            }
                                        } else {
                                            $groupenrollments = $member->group_enrollments;
                                        }
                                    }
                                }
                                if (!isset($message->{'https://purl.imsglobal.org/spec/lti/claim/message_type'})) {
                                    Util::setMessage(true,
                                        'The members/message elements must include a \'https://purl.imsglobal.org/spec/lti/claim/message_type\' property');
                                } elseif (($message->{'https://purl.imsglobal.org/spec/lti/claim/message_type'} === 'basic-lti-launch-request') ||
                                    ($message->{'https://purl.imsglobal.org/spec/lti/claim/message_type'} === 'LtiResourceLinkRequest')) {
                                    if (isset($message->{'https://purl.imsglobal.org/spec/lti-bo/claim/basicoutcome'}) &&
                                        isset($message->{'https://purl.imsglobal.org/spec/lti-bo/claim/basicoutcome'}->lis_result_sourcedid)) {
                                        $sourcedid = Util::checkString($message->{'https://purl.imsglobal.org/spec/lti-bo/claim/basicoutcome'},
                                            'members/message/https://purl.imsglobal.org/spec/lti-bo/claim/basicoutcome/lis_result_sourcedid');
                                        if (empty($userResult->ltiResultSourcedId) || ($userResult->ltiResultSourcedId !== $sourcedid)) {
                                            $userResult->ltiResultSourcedId = $sourcedid;
                                            $doSave = true;
                                        }
                                    } elseif ($userResult->isLearner() && empty($userResult->created)) {  // Ensure all learners are recorded in case Assignment and Grade services are used
                                        $userResult->ltiResultSourcedId = '';
                                        $doSave = true;
                                    }
                                    $username = null;
                                    if (isset($message->{'https://purl.imsglobal.org/spec/lti/claim/ext'}) &&
                                        is_object($message->{'https://purl.imsglobal.org/spec/lti/claim/ext'})) {
                                        if (!empty($message->{'https://purl.imsglobal.org/spec/lti/claim/ext'}->username)) {
                                            $username = Util::checkString($message->{'https://purl.imsglobal.org/spec/lti/claim/ext'},
                                                'members/message/https://purl.imsglobal.org/spec/lti/claim/ext/username');
                                        } elseif (!empty($message->{'https://purl.imsglobal.org/spec/lti/claim/ext'}->user_username)) {
                                            $username = Util::checkString($message->{'https://purl.imsglobal.org/spec/lti/claim/ext'},
                                                'members/message/https://purl.imsglobal.org/spec/lti/claim/ext/user_username');
                                        }
                                    }
                                    if (empty($username) && isset($message->{'https://purl.imsglobal.org/spec/lti/claim/custom'}) &&
                                        is_object($message->{'https://purl.imsglobal.org/spec/lti/claim/custom'})) {
                                        if (!empty($message->{'https://purl.imsglobal.org/spec/lti/claim/custom'}->username)) {
                                            $username = Util::checkString($message->{'https://purl.imsglobal.org/spec/lti/claim/custom'},
                                                'members/message/https://purl.imsglobal.org/spec/lti/claim/custom/username');
                                        } elseif (!empty($message->{'https://purl.imsglobal.org/spec/lti/claim/custom'}->user_username)) {
                                            $username = Util::checkString($message->{'https://purl.imsglobal.org/spec/lti/claim/custom'},
                                                'members/message/https://purl.imsglobal.org/spec/lti/claim/custom/user_username');
                                        }
                                    }
                                    if (!empty($username)) {
                                        $userResult->username = $username;
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
                    if (is_array($groupenrollments)) {
                        $userResult->groups = [];
                        foreach ($groupenrollments as $group) {
                            if (!is_object($group)) {
                                Util::setMessage(true,
                                    'The members/group_enrollments element must comprise an array of objects (' . gettype($group) . ' found)');
                                continue;
                            } elseif (!isset($group->group_id)) {
                                Util::setMessage(true, 'The members/group_enrollments objects must have a \'group_id\' property');
                                continue;
                            } else {
                                $groupId = Util::checkString($group, 'members/group_enrollments/group_id');
                                if (!empty($groupId)) {
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
        } else {
            $userResults = false;
        }

        return $userResults;
    }

}
