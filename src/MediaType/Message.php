<?php

namespace ceLTIc\LTI\MediaType;

use ceLTIc\LTI\Profile;

/**
 * Class to represent an LTI Message
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class Message
{

    /**
     * LTI message type.
     *
     * @var string|null $message_type
     */
    public $message_type = null;

    /**
     * Path to send message request to (used in conjunction with a base URL for the Tool).
     *
     * @var string|null $path
     */
    public $path = null;

    /**
     * Enabled capabilities.
     *
     * @var array|null $enabled_capability
     */
    public $enabled_capability = null;

    /**
     * Message parameters.
     *
     * @var array|null $parameter
     */
    public $parameter = null;

    /**
     * Class constructor.
     *
     * @param Profile\Message $message      Message object
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
