<?php

namespace ceLTIc\LTI;

/**
 * Class to represent an assessment control action
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class AssessmentControl
{

    /**
     * Pause action.
     */
    const ACTION_PAUSE = 'pause';

    /**
     * Pause action.
     */
    const ACTION_RESUME = 'resume';

    /**
     * Pause action.
     */
    const ACTION_TERMINATE = 'terminate';

    /**
     * Pause action.
     */
    const ACTION_UPDATE = 'update';

    /**
     * Pause action.
     */
    const ACTION_FLAG = 'flag';

    /**
     * Extra time.
     *
     * @var int|null $extraTime
     */
    public $extraTime = null;

    /**
     * Reason code.
     *
     * @var string|null $code
     */
    public $code = null;

    /**
     * Reason message.
     *
     * @var string|null $message
     */
    public $message = null;

    /**
     * Action.
     *
     * @var string|null $action
     */
    private $action = null;

    /**
     * Incident date value.
     *
     * @var DateTime|null $date
     */
    private $date = null;

    /**
     * Severity.
     *
     * @var float|null $severity
     */
    private $severity = null;

    /**
     * Class constructor.
     *
     * @param string    $action             Action
     * @param DateTime  $date               Date/time of incident
     * @param float     $severity           Severity of incident
     */
    public function __construct($action, $date, $severity)
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
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Get the incident date.
     *
     * @return DateTime Incident date value
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Get the severity.
     *
     * @return float Severity value
     */
    public function getSeverity()
    {
        return $this->severity;
    }

}
