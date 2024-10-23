<?php
declare(strict_types=1);

namespace ceLTIc\LTI\Service;

use ceLTIc\LTI\Platform;
use ceLTIc\LTI\Context;
use ceLTIc\LTI\ResourceLink;
use ceLTIc\LTI\Enum\ToolSettingsMode;
use ceLTIc\LTI\Util;

/**
 * Class to implement the Tool Settings service
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ToolSettings extends Service
{

    /**
     * Media type for tool settings service.
     */
    public const MEDIA_TYPE_TOOL_SETTINGS = 'application/vnd.ims.lti.v2.toolsettings+json';

    /**
     * Media type for tool settings simple service.
     */
    public const MEDIA_TYPE_TOOL_SETTINGS_SIMPLE = 'application/vnd.ims.lti.v2.toolsettings.simple+json';

    /**
     * Access scope.
     *
     * @var string $SCOPE
     */
    public static string $SCOPE = 'https://purl.imsglobal.org/spec/lti-ts/scope/toolsetting';

    /**
     * Names of LTI parameters to be retained in the consumer settings property.
     *
     * @var array $LEVEL_NAMES
     */
    private static array $LEVEL_NAMES = [
        'ToolProxy' => 'system',
        'ToolProxyBinding' => 'context',
        'LtiLink' => 'link'
    ];

    /**
     * The object to which the settings apply (ResourceLink, Context or Platform).
     *
     * @var Platform|Context|ResourceLink $source
     */
    private Platform|Context|ResourceLink $source;

    /**
     * Whether to use the simple JSON format.
     *
     * @var bool $simple
     */
    private bool $simple;

    /**
     * Class constructor.
     *
     * @param Platform|Context|ResourceLink  $source  The object to which the settings apply (ResourceLink, Context or Platform)
     * @param string $endpoint                        Service endpoint
     * @param bool $simple                            True if the simple media type is to be used (optional, default is true)
     */
    public function __construct(Platform|Context|ResourceLink $source, string $endpoint, bool $simple = true)
    {
        if (is_a($source, 'ceLTIc\LTI\Platform')) {
            $platform = $source;
        } else {
            $platform = $source->getPlatform();
        }
        parent::__construct($platform, $endpoint);
        $this->scope = self::$SCOPE;
        if ($simple) {
            $this->mediaType = self::MEDIA_TYPE_TOOL_SETTINGS_SIMPLE;
        } else {
            $this->mediaType = self::MEDIA_TYPE_TOOL_SETTINGS;
        }
        $this->source = $source;
        $this->simple = $simple;
    }

    /**
     * Get the tool settings.
     *
     * @param ToolSettingsMode|null $mode  Mode for request (optional, default is current level only)
     *
     * @return array|bool  The array of settings if successful, otherwise false
     */
    public function get(?ToolSettingsMode $mode = null): array|bool
    {
        $response = false;
        $parameter = [];
        if (!empty($mode)) {
            $parameter['bubble'] = $mode->value;
        }
        $http = $this->send('GET', $parameter);
        if ($http->ok) {
            if ($this->simple) {
                if (!is_object($http->responseJson)) {
                    Util::setMessage(true, 'The response must be an object');
                    $response = false;
                } else {
                    $response = Util::jsonDecode(json_encode($http->responseJson), true);
                    if (is_array($response)) {
                        $response = $this->checkSettings($response);
                    } else {
                        Util::setMessage(true, 'The response must be a simple object');
                        $response = false;
                    }
                }
            } else {
                $graph = Util::checkArray($http->responseJson, '@graph', true, true);
                if (!empty($graph)) {
                    $response = [];
                    foreach ($graph as $level) {
                        $settings = [];
                        if (isset($level->custom)) {
                            if (!is_object($level->custom)) {
                                Util::setMessage(true, 'The custom element must be an object');
                                $response = false;
                            } else {
                                $settings = Util::jsonDecode(json_encode($level->custom), true);
                                if (is_array($settings)) {
                                    unset($settings['@id']);
                                    $settings = $this->checkSettings($settings);
                                    if ($settings === false) {
                                        $response = false;
                                    }
                                } else {
                                    Util::setMessage(true, 'The custom element must be a simple object');
                                    $response = false;
                                }
                            }
                        }
                        if ($response !== false) {
                            $response[self::$LEVEL_NAMES[$level->{'@type'}]] = $settings;
                        }
                    }
                }
            }
        }

        return $response;
    }

    /**
     * Set the tool settings.
     *
     * @param array $settings  An associative array of settings (optional, default is null)
     *
     * @return bool  True if request was successful
     */
    public function set(array $settings): bool
    {
        if (!$this->simple) {
            if (is_a($this->source, 'ceLTIc\LTI\Platform')) {
                $type = 'ToolProxy';
            } elseif (is_a($this->source, 'ceLTIc\LTI\Context')) {
                $type = 'ToolProxyBinding';
            } else {
                $type = 'LtiLink';
            }
            $level = (object) [
                    '@type' => $type,
                    '@id' => $this->endpoint,
                    'custom' => $settings
            ];
            $obj = (object) [
                    '@context' => 'http://purl.imsglobal.org/ctx/lti/v2/ToolSettings',
                    '@graph' => [$level]
            ];
            $body = json_encode($obj);
        } else {
            $body = json_encode($settings);
        }

        $response = parent::send('PUT', null, $body);

        return $response->ok;
    }

###
###  PRIVATE METHODS
###

    /**
     * Check the tool setting values.
     *
     * @param array $settings  An associative array of settings
     *
     * @return array|false  Array of settings, or false if an invalid value is found
     */
    private function checkSettings(array $settings): array|false
    {
        $response = [];
        foreach ($settings as $key => $value) {
            if (is_string($value)) {
                if ($response !== false) {
                    $response[$key] = $value;
                }
            } elseif (!Util::$strictMode) {
                Util::setMessage(false,
                    'Properties of the custom element should have a string value (' . gettype($value) . ' found)');
                if ($response !== false) {
                    $response[$key] = Util::valToString($value);
                }
            } else {
                Util::setMessage(true, 'Properties of the custom element must have a string value (' . gettype($value) . ' found)');
                $response = false;
            }
        }

        return $response;
    }

}
