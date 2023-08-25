<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Enum;

/**
 * Enumeration to define alternative levels of logging
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
enum LogLevel: int
{

    /**
     * No logging.
     */
    case None = 0;

    /**
     * Log errors only.
     */
    case Error = 1;

    /**
     * Log error and information messages.
     */
    case Info = 2;

    /**
     * Log all messages.
     */
    case Debug = 3;

    /**
     * Check whether no messages are logged
     *
     * @return bool
     */
    public function logNone(): bool
    {
        return match ($this) {
            LogLevel::None => true,
            default => false
        };
    }

    /**
     * Check whether error messages are logged
     *
     * @return bool
     */
    public function logError(): bool
    {
        return match ($this) {
            LogLevel::None => false,
            default => true
        };
    }

    /**
     * Check whether information messages are logged
     *
     * @return bool
     */
    public function logInfo(): bool
    {
        return match ($this) {
            LogLevel::Info, LogLevel::Debug => true,
            default => false
        };
    }

    /**
     * Check whether debug messages are logged
     *
     * @return bool
     */
    public function logDebug(): bool
    {
        return match ($this) {
            LogLevel::Debug => true,
            default => false
        };
    }

}
