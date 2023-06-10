<?php

namespace ceLTIc\LTI\Service;

use ceLTIc\LTI\User;
use ceLTIc\LTI\AssessmentControlAction;
use ceLTIc\LTI\ResourceLink;

/**
 * Class to implement the Assessment Control service
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class AssessmentControl extends Service
{

    /**
     * Access scope.
     *
     * @var string $SCOPE
     */
    public static $SCOPE = 'https://purl.imsglobal.org/spec/lti-ap/scope/control.all';

    /**
     * Resource link for this service request.
     *
     * @var ResourceLink  $resourceLink
     */
    private $resourceLink = null;

    /**
     * Class constructor.
     *
     * @param ResourceLink  $resourceLink  Resource link object for this service request
     * @param string        $endpoint      Service endpoint
     */
    public function __construct($resourceLink, $endpoint)
    {
        parent::__construct($resourceLink->getPlatform(), $endpoint);
        $this->resourceLink = $resourceLink;
        $this->scope = self::$SCOPE;
        $this->mediaType = 'application/vnd.ims.lti-ap.v1.control+json';
    }

    /**
     * Submit an assessment control action.
     *
     * @param AssessmentControlAction $assessmentControlAction  AssessmentControlAction object
     * @param User $user                                        User object
     * @param int                             $attemptNumber             Attempt number
     *
     * @return string|bool  Value of the status response, or false if not successful
     */
    public function submitAction($assessmentControlAction, $user, $attemptNumber)
    {
        $status = false;
        $json = array(
            'user' => array('iss' => $this->resourceLink->getPlatform()->platformId, 'sub' => $user->ltiUserId),
            'resource_link' => array('id' => $this->resourceLink->ltiResourceLinkId),
            'attempt_number' => $attemptNumber,
            'action' => $assessmentControlAction->getAction(),
            'incident_time' => $assessmentControlAction->getDate()->format('Y-m-d\TH:i:s\Z'),
            'incident_severity' => $assessmentControlAction->getSeverity()
        );
        if (!empty($assessmentControlAction->extraTime)) {
            $json['extra_time'] = $assessmentControlAction->extraTime;
        }
        if (!empty($assessmentControlAction->code)) {
            $json['reason_code'] = $assessmentControlAction->code;
        }
        if (!empty($assessmentControlAction->message)) {
            $json['reason_msg'] = $assessmentControlAction->message;
        }
        $data = json_encode($json);
        $http = $this->send('POST', null, $data);
        if ($http->ok) {
            $http->ok = !empty($http->responseJson->status);
            if ($http->ok) {
                $status = $http->responseJson->status;
            }
        }

        return $status;
    }

}
