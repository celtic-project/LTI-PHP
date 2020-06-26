<?php

namespace ceLTIc\LTI;

use ceLTIc\LTI\Platform;

/**
 * Class to represent a tool consumer
 *
 * @deprecated Use Platform instead
 * @see Platform
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ToolConsumer extends Platform
{

    /**
     * Class constructor.
     *
     * @param string          $key             Consumer key/client ID
     * @param DataConnector   $dataConnector   A data connector object
     * @param bool            $autoEnable      true if the tool consumer is to be enabled automatically (optional, default is false)
     */
    public function __construct($key = null, $dataConnector = null, $autoEnable = false)
    {
        parent::__construct($dataConnector);
        $platform = Platform::fromConsumerKey($key, $dataConnector, $autoEnable);
        $this->setKey($key);
        $this->setRecordId($platform->getRecordId());
        foreach (get_object_vars($platform) as $key => $value) {
            $this->$key = $value;
        }
        Util::logDebug('Class ceLTIc\LTI\ToolConsumer has been deprecated; please use ceLTIc\LTI\Platform::fromConsumerKey instead.',
            true);
    }

}
