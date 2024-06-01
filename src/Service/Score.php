<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Service;

use ceLTIc\LTI\Platform;
use ceLTIc\LTI\User;
use ceLTIc\LTI\Outcome;

/**
 * Class to implement the Score service
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class Score extends AssignmentGrade
{

    /**
     * Access scope.
     *
     * @var string $SCOPE
     */
    public static string $SCOPE = 'https://purl.imsglobal.org/spec/lti-ags/scope/score';

    /**
     * Class constructor.
     *
     * @param Platform $platform  Platform object for this service request
     * @param string $endpoint    Service endpoint
     */
    public function __construct(Platform $platform, string $endpoint)
    {
        parent::__construct($platform, $endpoint, '/scores');
        $this->scope = self::$SCOPE;
        $this->mediaType = 'application/vnd.ims.lis.v1.score+json';
    }

    /**
     * Submit an outcome for a user.
     *
     * @param Outcome $ltiOutcome  Outcome object
     * @param User $user           User object
     *
     * @return bool  True if successful, otherwise false
     */
    public function submit(Outcome $ltiOutcome, User $user): bool
    {
        $score = $ltiOutcome->getValue();
        $activityProgress = $ltiOutcome->activityProgress;
        if (empty($activityProgress)) {
            $activityProgress = 'Initialized';
        }
        $gradingProgress = $ltiOutcome->gradingProgress;
        if (empty($gradingProgress)) {
            $gradingProgress = 'NotReady';
        }
        $json = [
            'timestamp' => date_format(new \DateTime(), 'Y-m-d\TH:i:s.uP'),
            'userId' => $user->ltiUserId,
            'comment' => $ltiOutcome->comment,
            'activityProgress' => $activityProgress,
            'gradingProgress' => $gradingProgress
        ];
        if (!is_null($score)) {
            $json['scoreGiven'] = $score;
            $json['scoreMaximum'] = $ltiOutcome->getPointsPossible();
        }
        $data = json_encode($json);
        $http = $this->send('POST', null, $data);

        return $http->ok;
    }

}
