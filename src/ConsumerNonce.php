<?php

namespace ceLTIc\LTI;

use ceLTIc\LTI\Util;

/**
 * Class to represent a tool consumer nonce
 *
 * @deprecated Use PlatformNonce instead
 * @see PlatformNonce
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ConsumerNonce extends PlatformNonce
{

    /**
     * Class constructor.
     *
     * @param ToolConsumer  $consumer  Tool consumer object
     * @param string        $value     Nonce value (optional, default is null)
     */
    public function __construct($consumer, $value = null)
    {
        parent::__construct($consumer, $value);
        Util::logDebug('Class ceLTIc\LTI\ConsumerNonce has been deprecated; please use ceLTIc\LTI\PlatformNonce instead.', true);
    }

    /**
     * Get tool consumer.
     *
     * @return ToolConsumer  Tool consumer for this nonce
     */
    public function getConsumer()
    {
        return $this->getPlatform();
    }

}
