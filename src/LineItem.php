<?php
declare(strict_types=1);

namespace ceLTIc\LTI;

use ceLTIc\LTI\Service;

/**
 * Class to represent a line-item
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class LineItem
{

    /**
     * Label value.
     *
     * @var string|null $label
     */
    public ?string $label = null;

    /**
     * Points possible value.
     *
     * @var int|float $pointsPossible
     */
    public int|float $pointsPossible = 1;

    /**
     * LTI Resource Link ID with which the line-item is associated.
     *
     * @var string|null $ltiResourceLinkId
     */
    public ?string $ltiResourceLinkId = null;

    /**
     * Tool resource ID associated with the line-item.
     *
     * @var string|null $resourceId
     */
    public ?string $resourceId = null;

    /**
     * Tag value.
     *
     * @var string|null $tag
     */
    public ?string $tag = null;

    /**
     * Outcome start submit timestamp.
     *
     * @var int|null $submitFrom
     */
    public ?int $submitFrom = null;

    /**
     * Outcome end submit timestamp.
     *
     * @var int|null $submitUntil
     */
    public ?int $submitUntil = null;

    /**
     * Line-item endpoint.
     *
     * @var string|null $endpoint
     */
    public ?string $endpoint = null;

    /**
     * Submission review.
     *
     * @var SubmissionReview|null $submissionReview
     */
    public ?SubmissionReview $submissionReview = null;

    /**
     * Platform for this line-item.
     *
     * @var Platform|null $platform
     */
    private ?Platform $platform = null;

    /**
     * Class constructor.
     *
     * @param Platform $platform            Platform object
     * @param string $label                 Label
     * @param int|float $pointsPossible     Points possible value
     */
    public function __construct(Platform $platform, string $label, int|float $pointsPossible)
    {
        $this->platform = $platform;
        $this->label = $label;
        $this->pointsPossible = $pointsPossible;
    }

    /**
     * Get platform.
     *
     * @return Platform  Platform object for this line-item.
     */
    public function getPlatform(): Platform
    {
        return $this->platform;
    }

    /**
     * Save the line-item to the platform.
     *
     * @return bool  True if successful
     */
    public function save(): bool
    {
        $service = new Service\LineItem($this->platform, $this->endpoint);
        return $service->saveLineItem($this);
    }

    /**
     * Delete the line-item on the platform.
     *
     * @return bool  True if successful
     */
    public function delete(): bool
    {
        $service = new Service\LineItem($this->platform, $this->endpoint);
        return $service->deleteLineItem($this);
    }

    /**
     * Retrieve all outcomes.
     *
     * @param int|null $limit  Limit of outcomes to be returned in each request, null for service default
     *
     * @return Outcome[]|bool  Array of outcome objects, or false on error
     */
    public function getOutcomes(?int $limit = null): array|bool
    {
        $resultService = new Service\Result($this->platform, $this->endpoint);
        return $resultService->getAll();
    }

    /**
     * Retrieve the outcome for a user.
     *
     * @param User $user  User object
     *
     * @return Outcome|null|bool  Outcome object, or null if none, or false on error
     */
    public function readOutcome(User $user): Outcome|null|bool
    {
        $resultService = new Service\Result($this->platform, $this->endpoint);
        return $resultService->get($user);
    }

    /**
     * Submit the outcome for a user.
     *
     * @param Outcome $ltiOutcome  Outcome object
     * @param User $user           User object
     *
     * @return bool  True if successful
     */
    public function submitOutcome(Outcome $ltiOutcome, User $user): bool
    {
        $scoreService = new Service\Score($this->platform, $this->endpoint);
        return $scoreService->submit($ltiOutcome, $user);
    }

    /**
     * Delete the outcome for a user.
     *
     * @param User $user  User object
     *
     * @return bool  True if successful, otherwise false
     */
    public function deleteOutcome(User $user): bool
    {
        $ltiOutcome = new Outcome();
        $scoreService = new Service\Score($this->platform, $this->endpoint);
        return $scoreService->submit($ltiOutcome, $user);
    }

    /**
     * Retrieve a line-item definition.
     *
     * @param Platform $platform  Platform object
     * @param string $endpoint    ID value
     *
     * @return LineItem|bool  LineItem object or false on error
     */
    public static function fromEndpoint(Platform $platform, string $endpoint): LineItem|bool
    {
        return Service\LineItem::getLineItem($platform, $endpoint);
    }

}
