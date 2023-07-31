<?php
declare(strict_types=1);

namespace ceLTIc\LTI;

use \DateTime;

/**
 * Class to represent an assessment control action
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class AssessmentControlAction
{

    /**
     * Pause action.
     */
    public const ACTION_PAUSE = 'pause';

    /**
     * Pause action.
     */
    public const ACTION_RESUME = 'resume';

    /**
     * Pause action.
     */
    public const ACTION_TERMINATE = 'terminate';

    /**
     * Pause action.
     */
    public const ACTION_UPDATE = 'update';

    /**
     * Pause action.
     */
    public const ACTION_FLAG = 'flag';

    /**
     * Extra time.
     *
     * @var int|null $extraTime
     */
    public ?int $extraTime = null;

    /**
     * Reason code.
     *
     * @var string|null $code
     */
    public ?string $code = null;

    /**
     * Reason message.
     *
     * @var string|null $message
     */
    public ?string $message = null;

    /**
     * Action.
     *
     * @var string|null $action
     */
    private ?string $action = null;

    /**
     * Incident date value.
     *
     * @var DateTime|null $date
     */
    private ?DateTime $date = null;

    /**
     * Severity.
     *
     * @var float|null $severity
     */
    private ?float $severity = null;

    /**
     * Class constructor.
     *
     * @param string $action   Action
     * @param DateTime $date  Date/time of incident
     * @param float $severity  Severity of incident
     */
    public function __construct(string $action, DateTime $date, float $severity)
    {
        $this->action = $action;
        $this->date = $date;
        $this->severity = $severity;
    }

    /**
     * Get the action.
     *
     * @return string Action value
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Get the incident date.
     *
     * @return DateTime Incident date value
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * Get the severity.
     *
     * @return float Severity value
     */
    public function getSeverity(): float
    {
        return $this->severity;
    }

}
