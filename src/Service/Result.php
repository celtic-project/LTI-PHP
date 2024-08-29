<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Service;

use ceLTIc\LTI\Platform;
use ceLTIc\LTI\User;
use ceLTIc\LTI\Outcome;
use ceLTIc\LTI\Util;

/**
 * Class to implement the Result service
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class Result extends AssignmentGrade
{

    /**
     * Access scope.
     *
     * @var string $SCOPE
     */
    public static string $SCOPE = 'https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly';

    /**
     * Default limit on size of container to be returned from requests.
     *
     * @var int $defaultLimit
     */
    public static int $defaultLimit = 500;

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
     * @param Platform $platform  Platform object for this service request
     * @param string $endpoint    Service endpoint
     * @param int|null $limit     Limit of results to be returned in each request, null for all
     * @param bool $pagingMode    True if only a single page should be requested when a limit is set
     */
    public function __construct(Platform $platform, string $endpoint, ?int $limit = null, bool $pagingMode = false)
    {
        parent::__construct($platform, $endpoint, '/results');
        $this->limit = $limit;
        $this->pagingMode = $pagingMode;
        $this->scope = self::$SCOPE;
        $this->mediaType = 'application/vnd.ims.lis.v2.resultcontainer+json';
    }

    /**
     * Retrieve all outcomes for a line-item.
     *
     * @param int|null $limit  Limit of results to be returned in each request, null for service default
     *
     * @return Outcome[]|bool  Array of Outcome objects or false on error
     */
    public function getAll(?int $limit = null): array|bool
    {
        $params = [];
        if (is_null($limit)) {
            $limit = $this->limit;
        }
        if (is_null($limit)) {
            $limit = self::$defaultLimit;
        }
        if (!empty($limit)) {
            $params['limit'] = $limit;
        }
        $outcomes = [];
        $endpoint = $this->endpoint;
        do {
            $http = $this->send('GET', $params);
            $url = '';
            if ($http->ok) {
                $http->ok = empty($http->responseJson) || is_array($http->responseJson);
            }
            if ($http->ok) {
                if (!empty($http->responseJson)) {
                    foreach ($http->responseJson as $outcome) {
                        if (!is_object($outcome)) {
                            $http->ok = false;
                            break;
                        } else {
                            $obj = $this->getOutcome($outcome);
                            if (!is_null($obj)) {
                                $outcomes[] = $obj;
                            }
                        }
                    }
                }
                if (!$this->pagingMode && $http->hasRelativeLink('next')) {
                    $url = $http->getRelativeLink('next');
                    $this->endpoint = $url;
                    $params = [];
                }
            }
        } while ($url);
        $this->endpoint = $endpoint;
        if (!$http->ok) {
            $outcomes = false;
        }

        return $outcomes;
    }

    /**
     * Retrieve an outcome for a user.
     *
     * @param User $user  User object
     *
     * @return Outcome|null|bool  Outcome object, or null if none, or false on error
     */
    public function get(User $user): Outcome|null|bool
    {
        $params = [
            'user_id' => $user->ltiUserId
        ];
        $http = $this->send('GET', $params);
        if ($http->ok) {
            $http->ok = empty($http->responseJson) || is_array($http->responseJson);
        }
        if ($http->ok) {
            if (!empty($http->responseJson)) {
                $obj = reset($http->responseJson);
                if (is_object($obj)) {
                    $outcome = $this->getOutcome($obj);
                } else {
                    $outcome = false;
                }
            } else {
                $outcome = null;
            }
            return $outcome;
        } else {
            return false;
        }
    }

###
###  PRIVATE METHOD
###

    /**
     * Extract an outcome from its JSON representation.
     *
     * @param object $json  JSON representation of an outcome
     *
     * @return Outcome|null  Outcome object
     */
    private function getOutcome(object $json): ?Outcome
    {
        $outcome = null;
        $id = Util::checkString($json, 'id', true);
        $scoreOf = Util::checkString($json, 'scoreOf', true);
        $userId = Util::checkString($json, 'userId', true);
        $resultScore = Util::checkNumber($json, 'resultScore');
        $resultMaximum = Util::checkNumber($json, 'resultMaximum', false, 0, true);
        $comment = Util::checkString($json, 'comment');
        if (!empty($userId)) {
            $outcome = new Outcome();
            $outcome->ltiUserId = $userId;
            if (!is_null($resultScore)) {
                $outcome->setValue($resultScore);
            }
            if (!is_null($resultMaximum)) {
                $outcome->setPointsPossible($resultMaximum);
            }
            if (!empty($comment)) {
                $outcome->comment = $comment;
            }
        }

        return $outcome;
    }

}
