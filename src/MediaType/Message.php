<?php

namespace ceLTIc\LTI\MediaType;

/**
 * Class to represent an LTI Message
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @version  3.1.0
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class Message
{

    /**
     * Class constructor.
     *
     * @param Message $message               Message object
     * @param array   $capabilitiesOffered   Capabilities offered
     */
    function __construct($message, $capabilitiesOffered)
    {
        $this->message_type = $message->type;
        $this->path = $message->path;
        $this->enabled_capability = array();
        foreach ($message->capabilities as $capability) {
            if (in_array($capability, $capabilitiesOffered)) {
                $this->enabled_capability[] = $capability;
            }
        }
        $this->parameter = array();
        foreach ($message->constants as $name => $value) {
            $parameter = new \stdClass;
            $parameter->name = $name;
            $parameter->fixed = $value;
            $this->parameter[] = $parameter;
        }
        foreach ($message->variables as $name => $value) {
            if (in_array($value, $capabilitiesOffered)) {
                $parameter = new \stdClass;
                $parameter->name = $name;
                $parameter->variable = $value;
                $this->parameter[] = $parameter;
            }
        }
    }

}
