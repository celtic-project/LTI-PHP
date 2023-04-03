<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Service;

use ceLTIc\LTI\Platform;
use ceLTIc\LTI\User;
use ceLTIc\LTI\Outcome;

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
                if (!empty($http->responseJson)) {
                    foreach ($http->responseJson as $outcome) {
                        $outcomes[] = self::getOutcome($outcome);
                    }
                }
                if (!$this->pagingMode && $http->hasRelativeLink('next')) {
                    $url = $http->getRelativeLink('next');
                    $this->endpoint = $url;
                    $params = [];
                }
            } else {
                $outcomes = false;
            }
        } while ($url);
        $this->endpoint = $endpoint;

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
            if (!empty($http->responseJson)) {
                $outcome = self::getOutcome(reset($http->responseJson));
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
     * @return Outcome  Outcome object
     */
    private static function getOutcome(object $json): Outcome
    {
        $outcome = new Outcome();
        $outcome->ltiUserId = $json->userId;
        if (isset($json->resultScore)) {
            $outcome->setValue($json->resultScore);
        }
        if (isset($json->resultMaximum)) {
            $outcome->setPointsPossible($json->resultMaximum);
        }
        if (isset($json->comment)) {
            $outcome->comment = $json->comment;
        }

        return $outcome;
    }

}
