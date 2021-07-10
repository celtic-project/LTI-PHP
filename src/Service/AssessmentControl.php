<?php

namespace ceLTIc\LTI\Service;

use ceLTIc\LTI;

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
     * @param LTI\AssessmentControl     $assessmentControl   AssessmentControl object
     * @param LTI\User                  $user                User object
     * @param int                       $attemptNumber       Attempt number
     *
     * @return bool  True if successful, otherwise false
     */
    public function submit($assessmentControl, $user, $attemptNumber)
    {
        $json = array(
            'user' => array('iss' => $this->resourceLink->getPlatform()->platformId, 'sub' => $user->ltiUserId),
            'resource_link' => array('id' => $this->resourceLink->ltiResourceLinkId),
            'attempt_number' => $attemptNumber,
            'action' => $assessmentControl->getAction(),
            'incident_time' => $assessmentControl->getDate()->format('Y-m-d\TH:i:s\Z'),
            'incident_severity' => $assessmentControl->getSeverity()
        );
        if (!empty($assessmentControl->extraTime)) {
            $json['extra_time'] = $assessmentControl->extraTime;
        }
        if (!empty($assessmentControl->code)) {
            $json['reason_code'] = $assessmentControl->code;
        }
        if (!empty($assessmentControl->message)) {
            $json['reason_msg'] = $assessmentControl->message;
        }
        $data = json_encode($json);
        $http = $this->send('POST', null, $data);

        return $http->ok;
    }

}
