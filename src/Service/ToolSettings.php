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
            $this->mediaType = 'application/vnd.ims.lti.v2.toolsettings.simple+json';
        } else {
            $this->mediaType = 'application/vnd.ims.lti.v2.toolsettings+json';
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
        $parameter = [];
        if (!empty($mode)) {
            $parameter['bubble'] = $mode->value;
        }
        $http = $this->send('GET', $parameter);
        if (!$http->ok) {
            $response = false;
        } elseif ($this->simple) {
            $response = Util::jsonDecode($http->response, true);
        } elseif (isset($http->responseJson->{'@graph'})) {
            $response = [];
            foreach ($http->responseJson->{'@graph'} as $level) {
                $settings = Util::jsonDecode(json_encode($level->custom), true);
                unset($settings['@id']);
                $response[self::$LEVEL_NAMES[$level->{'@type'}]] = $settings;
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
            if (is_a($this->source, 'Platform')) {
                $type = 'ToolProxy';
            } elseif (is_a($this->source, 'Context')) {
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

}
