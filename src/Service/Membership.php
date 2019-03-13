<?php

namespace ceLTIc\LTI\Service;

use ceLTIc\LTI;
use ceLTIc\LTI\Context;
use ceLTIc\LTI\ResourceLink;

/**
 * Class to implement the Membership service
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @version  3.1.0
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class Membership extends Service
{

    /**
     * The object to which the memberships apply (ResourceLink or Context).
     *
     * @var Context|ResourceLink $source
     */
    private $source;

    /**
     * Class constructor.
     *
     * @param object       $source     The object to which the memberships apply (ResourceLink or Context)
     * @param string       $endpoint   Service endpoint
     */
    public function __construct($source, $endpoint)
    {
        $consumer = $source->getConsumer();
        parent::__construct($consumer, $endpoint, 'application/vnd.ims.lis.v2.membershipcontainer+json');
        $this->source = $source;
    }

    /**
     * Get the memberships.
     *
     * @param string    $role   Role for which memberships are to be requested (optional, default is all roles)
     * @param int       $limit  Limit on the number of memberships to be returned (optional, default is all)
     *
     * @return mixed The array of UserResult objects if successful, otherwise false
     */
    public function get($role = null, $limit = 0)
    {
        $isLink = is_a($this->source, 'ceLTIc\LTI\ResourceLink');
        $parameters = array();
        if (!empty($role)) {
            $parameters['role'] = $role;
        }
        if ($limit > 0) {
            $parameters['limit'] = strval($limit);
        }
        if ($isLink) {
            $parameters['rlid'] = $this->source->getId();
        }
        $http = $this->send('GET', $parameters);
        if (!$http->ok) {
            $userResults = false;
        } else {
            $userResults = array();
            if ($isLink) {
                $oldUsers = $this->source->getUserResultSourcedIDs(true, LTI\ToolProvider::ID_SCOPE_RESOURCE);
            }
            foreach ($http->responseJson->pageOf->membershipSubject->membership as $membership) {
                $member = $membership->member;
                if ($isLink) {
                    $userresult = LTI\UserResult::fromResourceLink($this->source, $member->userId);
                } else {
                    $userresult = new LTI\UserResult();
                    $userresult->ltiUserId = $member->userId;
                }

// Set the user name
                $firstname = (isset($member->givenName)) ? $member->givenName : '';
                $lastname = (isset($member->familyName)) ? $member->familyName : '';
                $fullname = (isset($member->name)) ? $member->name : '';
                $userresult->setNames($firstname, $lastname, $fullname);

// Set the user email
                $email = (isset($member->email)) ? $member->email : '';
                $userresult->setEmail($email, $this->source->getConsumer()->defaultEmail);

// Set the user roles
                if (isset($membership->role)) {
                    $userresult->roles = LTI\ToolProvider::parseRoles($membership->role);
                }

// If a result sourcedid is provided save the user
                if ($isLink) {
                    if (isset($member->message)) {
                        foreach ($member->message as $message) {
                            if (isset($message->message_type) && (($message->message_type === 'basic-lti-launch-request') || (strtolower($message->message_type) === 'ltiresourcelinkrequest'))) {
                                if (isset($message->lis_result_sourcedid)) {
                                    $userresult->ltiResultSourcedId = $message->lis_result_sourcedid;
                                    $userresult->save();
                                }
                                break;
                            }
                        }
                    }
                }
                $userResults[] = $userresult;

// Remove old user (if it exists)
                if ($isLink) {
                    unset($oldUsers[$userresult->getId(LTI\ToolProvider::ID_SCOPE_RESOURCE)]);
                }
            }

/// Delete any old users which were not in the latest list from the tool consumer
            if ($isLink) {
                foreach ($oldUsers as $id => $userresult) {
                    $userresult->delete();
                }
            }
        }

        return $userResults;
    }

}
