<?php
declare(strict_types=1);

namespace ceLTIc\LTI;

use ceLTIc\LTI\Enum\OutcomeType;

/**
 * Class to represent an outcome
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class Outcome
{

    /**
     * Allowed values for Activity Progress.
     */
    public const ALLOWED_ACTIVITY_PROGRESS = [
        'Initialized',
        'Started',
        'InProgress',
        'Submitted',
        'Completed'
    ];

    /**
     * Allowed values for Grading Progress.
     */
    public const ALLOWED_GRADING_PROGRESS = [
        'FullyGraded',
        'Pending',
        'PendingManual',
        'Failed',
        'NotReady'
    ];

    /**
     * Language value.
     *
     * @var string|null $language
     */
    public ?string $language = null;

    /**
     * Outcome status value.
     *
     * @var string|null $status
     */
    public ?string $status = null;

    /**
     * Outcome date value.
     *
     * @var string|null $date
     */
    public ?string $date = null;

    /**
     * Outcome type value.
     *
     * @var OutcomeType|null $type
     */
    public ?OutcomeType $type = null;

    /**
     * Activity progress.
     *
     * @var string|null $activityProgress
     */
    public ?string $activityProgress = null;

    /**
     * Grading progress.
     *
     * @var string|null $gradingProgress
     */
    public ?string $gradingProgress = null;

    /**
     * Comment.
     *
     * @var string|null $comment
     */
    public ?string $comment = null;

    /**
     * Outcome data source value.
     *
     * @var string|null $dataSource
     */
    public ?string $dataSource = null;

    /**
     * LTI user ID.
     *
     * @var string|null $ltiUserId
     */
    public ?string $ltiUserId = null;

    /**
     * Outcome value.
     *
     * @var int|float|string|null $value
     */
    private int|float|string|null $value = null;

    /**
     * Points possible value.
     *
     * @var int|float|null $pointsPossible
     */
    private int|float|null $pointsPossible = 1;

    /**
     * Class constructor.
     *
     * @param int|float|string|null $value    Outcome value (optional, default is none)
     * @param int|float|null $pointsPossible  Points possible value (optional, default is 1)
     * @param string $activityProgress        Activity progress (optional, default is 'Completed')
     * @param string $gradingProgress         Grading progress (optional, default is 'FullyGraded')
     */
    public function __construct(int|float|string|null $value = null, int|float|null $pointsPossible = 1,
        string $activityProgress = 'Completed', string $gradingProgress = 'FullyGraded')
    {
        $this->value = $value;
        $this->pointsPossible = $pointsPossible;
        $this->language = 'en-US';
        $this->date = gmdate('Y-m-d\TH:i:s\Z', time());
        $this->type = OutcomeType::Decimal;
        if (in_array($activityProgress, self::ALLOWED_ACTIVITY_PROGRESS)) {
            $this->activityProgress = $activityProgress;
        } else {
            $this->activityProgress = 'Completed';
        }
        if (in_array($gradingProgress, self::ALLOWED_GRADING_PROGRESS)) {
            $this->gradingProgress = $gradingProgress;
        } else {
            $this->gradingProgress = 'FullyGraded';
        }
        $this->comment = '';
        $this->ltiUserId = null;
    }

    /**
     * Get the outcome value.
     *
     * @return int|float|string|null  Outcome value
     */
    public function getValue(): int|float|string|null
    {
        return $this->value;
    }

    /**
     * Set the outcome value.
     *
     * @param int|float|string|null $value  Outcome value
     *
     * @return void
     */
    public function setValue(int|float|string|null $value): void
    {
        $this->value = $value;
    }

    /**
     * Get the points possible value.
     *
     * @return int|float|null  Points possible value
     */
    public function getPointsPossible(): int|float|null
    {
        return $this->pointsPossible;
    }

    /**
     * Set the points possible value.
     *
     * @param int|float|null $pointsPossible  Points possible value
     *
     * @return void
     */
    public function setPointsPossible(int|float|null $pointsPossible): void
    {
        $this->pointsPossible = $pointsPossible;
    }

    /**
     * Assign property values from another outcome instance.
     *
     * @param Outcome $outcome  Outcome instance
     *
     * @return void
     */
    public function assign($outcome): void
    {
        foreach (get_object_vars($outcome) as $name => $value) {
            $this->$name = $value;
        }
    }

}
