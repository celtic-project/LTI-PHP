<?php
declare(strict_types=1);

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
    public ?string $message_type = null;

    /**
     * Path to send message request to (used in conjunction with a base URL for the Tool).
     *
     * @var string|null $path
     */
    public ?string $path = null;

    /**
     * Enabled capabilities.
     *
     * @var array|null $enabled_capability
     */
    public ?array $enabled_capability = null;

    /**
     * Message parameters.
     *
     * @var array|null $parameter
     */
    public ?array $parameter = null;

    /**
     * Class constructor.
     *
     * @param Profile\Message $message      Message object
     * @param array   $capabilitiesOffered  Capabilities offered
     */
    function __construct(Profile\Message $message, array $capabilitiesOffered)
    {
        $this->message_type = $message->type;
        $this->path = $message->path;
        $this->enabled_capability = [];
        foreach ($message->capabilities as $capability) {
            if (in_array($capability, $capabilitiesOffered)) {
                $this->enabled_capability[] = $capability;
            }
        }
        $this->parameter = [];
        foreach ($message->constants as $name => $value) {
            $this->parameter[] = (object) [
                    'name' => $name,
                    'fixed' => $value
            ];
        }
        foreach ($message->variables as $name => $value) {
            if (in_array($value, $capabilitiesOffered)) {
                $this->parameter[] = (object) [
                        'name' => $name,
                        'variable' => $value
                ];
            }
        }
    }

}
