<?php
declare(strict_types=1);

namespace ceLTIc\LTI;

use ceLTIc\LTI\DataConnector\DataConnector;
use ceLTIc\LTI\Content\Item;
use ceLTIc\LTI\Http\HttpMessage;
use ceLTIc\LTI\OAuth;
use ceLTIc\LTI\Jwt\Jwt;
use ceLTIc\LTI\Jwt\ClientInterface;
use ceLTIc\LTI\Tool;
use ceLTIc\LTI\Profile\ServiceDefinition;
use ceLTIc\LTI\Enum\LtiVersion;
use ceLTIc\LTI\Enum\IdScope;
use ceLTIc\LTI\Util;

/**
 * Class to represent an LTI system
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
trait System
{

    /**
     * True if the last request was successful.
     *
     * @var bool $ok
     */
    public bool $ok = true;

    /**
     * LTI version.
     *
     * @var LtiVersion|null $ltiVersion
     */
    public ?LtiVersion $ltiVersion = null;

    /**
     * Local name of platform/tool.
     *
     * @var string|null $name
     */
    public ?string $name = null;

    /**
     * Shared secret.
     *
     * @var string|null $secret
     */
    public ?string $secret = null;

    /**
     * Method used for signing messages.
     *
     * @var string $signatureMethod
     */
    public string $signatureMethod = 'HMAC-SHA1';

    /**
     * Algorithm used for encrypting messages.
     *
     * @var string|null $encryptionMethod
     */
    public ?string $encryptionMethod = '';

    /**
     * Data connector object.
     *
     * @var DataConnector|null $dataConnector
     */
    public ?DataConnector $dataConnector = null;

    /**
     * RSA/EC key in PEM or JSON format.
     *
     * Set to the private key for signing outgoing messages and service requests, and to the public key
     * for verifying incoming messages and service requests.
     *
     * @var string|null $rsaKey
     */
    public ?string $rsaKey = null;

    /**
     * Scopes to request when obtaining an access token.
     *
     * @var array  $requiredScopes
     */
    public array $requiredScopes = [];

    /**
     * Key ID.
     *
     * @var string|null $kid
     */
    public ?string $kid = null;

    /**
     * Endpoint for public key.
     *
     * @var string|null $jku
     */
    public ?string $jku = null;

    /**
     * Error message for last request processed.
     *
     * @var string|null $reason
     */
    public ?string $reason = null;

    /**
     * Details for error message relating to last request processed.
     *
     * @var array $details
     */
    public array $details = [];

    /**
     * Warnings relating to last request processed.
     *
     * @deprecated Use Util::getMessages() instead
     *
     * @var array $warnings
     */
    public array $warnings = [];

    /**
     * Whether debug level messages are to be reported.
     *
     * @var bool $debugMode
     */
    public bool $debugMode = false;

    /**
     * Whether the system instance is enabled to accept connection requests.
     *
     * @var bool $enabled
     */
    public bool $enabled = false;

    /**
     * Timestamp from which the the system instance is enabled to accept connection requests.
     *
     * @var int|null $enableFrom
     */
    public ?int $enableFrom = null;

    /**
     * Timestamp until which the system instance is enabled to accept connection requests.
     *
     * @var int|null $enableUntil
     */
    public ?int $enableUntil = null;

    /**
     * Timestamp for date of last connection to this system.
     *
     * @var int|null $lastAccess
     */
    public ?int $lastAccess = null;

    /**
     * Timestamp for when the object was created.
     *
     * @var int|null $created
     */
    public ?int $created = null;

    /**
     * Timestamp for when the object was last updated.
     *
     * @var int|null $updated
     */
    public ?int $updated = null;

    /**
     * Default scope to use when generating an Id value for a user.
     *
     * @var IdScope|null $idScope
     */
    public ?IdScope $idScope = IdScope::IdOnly;

    /**
     * Class of exception to be thrown instead of calling exit.
     *
     * @var string|null $onExitExceptionClass
     */
    public ?string $onExitExceptionClass = null;

    /**
     * JWT ClientInterface object.
     *
     * @var ClientInterface|null $jwt
     */
    protected ?ClientInterface $jwt = null;

    /**
     * Raw message parameters.
     *
     * @var array|null $rawParameters
     */
    protected ?array $rawParameters = null;

    /**
     * LTI message parameters.
     *
     * @var array|null $messageParameters
     */
    protected ?array $messageParameters = null;

    /**
     * System ID value.
     *
     * @var int|string|null $id
     */
    private int|string|null $id = null;

    /**
     * Consumer key/client ID value.
     *
     * @var string|null $key
     */
    private ?string $key = null;

    /**
     * Setting values (LTI parameters, custom parameters and local parameters).
     *
     * @var array|null $settings
     */
    private ?array $settings = null;

    /**
     * Whether the settings value have changed since last saved.
     *
     * @var bool $settingsChanged
     */
    private bool $settingsChanged = false;

    /**
     * Signature base string from last OAuth signature generated.
     *
     * @var bool $baseString
     */
    private ?string $baseString = null;

    /**
     * Get the system record ID.
     *
     * @return int|string|null  System record ID value
     */
    public function getRecordId(): int|string|null
    {
        return $this->id;
    }

    /**
     * Sets the system record ID.
     *
     * @param int|string|null $id  System record ID value
     *
     * @return void
     */
    public function setRecordId(int|string|null $id): void
    {
        $this->id = $id;
    }

    /**
     * Get the consumer key.
     *
     * @return string|null  Consumer key value
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * Set the consumer key.
     *
     * @param string|null $key  Consumer key value
     *
     * @return void
     */
    public function setKey(?string $key): void
    {
        $this->key = $key;
    }

    /**
     * Get a setting value.
     *
     * @param string $name          Name of setting
     * @param string|null $default  Value to return if the setting does not exist (optional, default is an empty string)
     *
     * @return string|null  Setting value
     */
    public function getSetting(string $name, ?string $default = ''): ?string
    {
        if (array_key_exists($name, $this->settings)) {
            $value = $this->settings[$name];
        } else {
            $value = $default;
        }

        return $value;
    }

    /**
     * Set a setting value.
     *
     * @param string $name              Name of setting
     * @param string|array|null $value  Value to set, use an empty value to delete a setting (optional, default is null)
     *
     * @return void
     */
    public function setSetting(string $name, string|array|null $value = null): void
    {
        $old_value = $this->getSetting($name);
        if ($value !== $old_value) {
            if (!empty($value)) {
                $this->settings[$name] = $value;
            } else {
                unset($this->settings[$name]);
            }
            $this->settingsChanged = true;
        }
    }

    /**
     * Get an array of all setting values.
     *
     * @return array  Associative array of setting values
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Set an array of all setting values.
     *
     * @param array $settings  Associative array of setting values
     *
     * @return void
     */
    public function setSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    /**
     * Save setting values.
     *
     * @return bool  True if the settings were successfully saved
     */
    public function saveSettings(): bool
    {
        if ($this->settingsChanged) {
            $ok = $this->save();
        } else {
            $ok = true;
        }

        return $ok;
    }

    /**
     * Check whether a JWT exists
     *
     * @return bool  True if a JWT exists
     */
    public function hasJwt(): bool
    {
        return !empty($this->jwt) && $this->jwt->hasJwt();
    }

    /**
     * Get the JWT
     *
     * @return ClientInterface  The JWT
     */
    public function getJwt(): ?ClientInterface
    {
        return $this->jwt;
    }

    /**
     * Get the raw POST parameters
     *
     * @return array  The POST parameter array
     */
    public function getRawParameters(): array
    {
        if (is_null($this->rawParameters)) {
            $this->rawParameters = OAuth\OAuthUtil::parse_parameters(file_get_contents(OAuth\OAuthRequest::$POST_INPUT));
        }

        return $this->rawParameters;
    }

    /**
     * Get the message claims
     *
     * @param bool $fullyQualified  True if claims should be fully qualified rather than grouped (default is false)
     *
     * @return array| null  The message claim array
     */
    public function getMessageClaims(bool $fullyQualified = false): ?array
    {
        $messageClaims = null;
        if (!is_null($this->messageParameters)) {
            $messageParameters = $this->messageParameters;
            $messageType = '';
            if (!empty($messageParameters['lti_message_type'])) {
                if (array_key_exists($messageParameters['lti_message_type'], Util::MESSAGE_TYPE_MAPPING)) {
                    $messageParameters['lti_message_type'] = Util::MESSAGE_TYPE_MAPPING[$messageParameters['lti_message_type']];
                }
                $messageType = $messageParameters['lti_message_type'];
            }
            if (!empty($messageParameters['accept_media_types'])) {
                $mediaTypes = array_map('trim', explode(',', $messageParameters['accept_media_types']));
                $mediaTypes = array_filter($mediaTypes);
                $types = [];
                if (!empty($messageParameters['accept_types'])) {
                    $types = array_map('trim', explode(',', $this->messageParameters['accept_types']));
                    $types = array_filter($types);
                    foreach ($mediaTypes as $mediaType) {
                        if (str_starts_with($mediaType, 'application/vnd.ims.lti.')) {
                            unset($mediaTypes[array_search($mediaType, $mediaTypes)]);
                        }
                    }
                    $messageParameters['accept_media_types'] = implode(',', $mediaTypes);
                } else {
                    foreach ($mediaTypes as $mediaType) {
                        if ($mediaType === Item::LTI_LINK_MEDIA_TYPE) {
                            unset($mediaTypes[array_search(Item::LTI_LINK_MEDIA_TYPE, $mediaTypes)]);
                            $messageParameters['accept_media_types'] = implode(',', $mediaTypes);
                            $types[] = Item::TYPE_LTI_LINK;
                        } elseif ($mediaType === Item::LTI_ASSIGNMENT_MEDIA_TYPE) {
                            unset($mediaTypes[array_search(Item::LTI_ASSIGNMENT_MEDIA_TYPE, $mediaTypes)]);
                            $messageParameters['accept_media_types'] = implode(',', $mediaTypes);
                            $types[] = Item::TYPE_LTI_ASSIGNMENT;
                        } elseif (str_starts_with($mediaType, 'image/')) {
                            $types[] = 'image';
                            $types[] = 'link';
                            $types[] = 'file';
                        } elseif ($mediaType === 'text/html') {
                            $types[] = 'html';
                            $types[] = 'link';
                            $types[] = 'file';
                        } elseif ($mediaType === '*/*') {
                            $types[] = 'html';
                            $types[] = 'image';
                            $types[] = 'file';
                            $types[] = 'link';
                        } else {
                            $types[] = 'file';
                        }
                    }
                    $types = array_unique($types);
                    $messageParameters['accept_types'] = implode(',', $types);
                }
            }
            if (!empty($messageParameters['accept_presentation_document_targets'])) {
                $documentTargets = array_map('trim', explode(',', $messageParameters['accept_presentation_document_targets']));
                $documentTargets = array_filter($documentTargets);
                $targets = [];
                foreach ($documentTargets as $documentTarget) {
                    switch ($documentTarget) {
                        case 'frame':
                        case 'popup':
                        case 'overlay':
                        case 'none':
                            break;
                        default:
                            $targets[] = $documentTarget;
                            break;
                    }
                }
                $targets = array_unique($targets);
                $messageParameters['accept_presentation_document_targets'] = implode(',', $targets);
            }
            $messageClaims = [];
            if (!empty($messageParameters['oauth_consumer_key'])) {
                $messageClaims['aud'] = [$messageParameters['oauth_consumer_key']];
                $messageClaims['azp'] = $messageParameters['oauth_consumer_key'];
            }
            foreach ($messageParameters as $key => $value) {
                $ok = true;
                $key = strval($key);
                if (array_key_exists($key, Util::JWT_CLAIM_MAPPING)) {
                    if (array_key_exists("{$key}.{$messageType}", Util::JWT_CLAIM_MAPPING)) {
                        $mapping = Util::JWT_CLAIM_MAPPING["{$key}.{$messageType}"];
                    } else {
                        $mapping = Util::JWT_CLAIM_MAPPING[$key];
                    }
                    if (isset($mapping['isArray']) && $mapping['isArray']) {
                        $value = array_map('trim', explode(',', $value));
                        $value = array_filter($value);
                        sort($value);
                    } elseif (isset($mapping['isObject']) && $mapping['isObject']) {
                        $value = array_map('trim', explode(",", $value));
                        $value = array_filter($value);
                        $props = [];
                        foreach ($value as $line) {
                            $parts = explode('=', $line, 2);
                            if (count($parts) > 1) {
                                $props[trim($parts[0])] = trim($parts[1]);
                            } else {
                                $props[trim($parts[0])] = '';
                            }
                        }
                        ksort($props);
                        $value = (object) $props;
                    } elseif (isset($mapping['isContentItemSelection']) && $mapping['isContentItemSelection']) {
                        $value = Util::jsonDecode($value);
                        if (is_object($value) && isset($value->{'@graph'}) && is_array($value->{'@graph'})) {
                            $value = $value->{'@graph'};
                        } else if (!is_array($value)) {
                            $value = null;
                        }
                    } elseif (isset($mapping['isBoolean']) && $mapping['isBoolean']) {
                        $value = (is_bool($value)) ? $value : $value === 'true';
                    } elseif (isset($mapping['isInteger']) && $mapping['isInteger']) {
                        $value = intval($value);
                    } elseif (is_bool($value)) {
                        $value = ($value) ? 'true' : 'false';
                    } else {
                        $value = strval($value);
                    }
                    $group = '';
                    $claim = Util::JWT_CLAIM_PREFIX;
                    if (!empty($mapping['suffix'])) {
                        $claim .= "-{$mapping['suffix']}";
                    }
                    $claim .= '/claim/';
                    if (is_null($mapping['group'])) {
                        $claim = $mapping['claim'];
                    } elseif (empty($mapping['group'])) {
                        $claim .= $mapping['claim'];
                    } else {
                        $group = $claim . $mapping['group'];
                        $claim = $mapping['claim'];
                    }
                } elseif (str_starts_with($key, 'custom_')) {
                    $group = Util::JWT_CLAIM_PREFIX . '/claim/custom';
                    $claim = substr($key, 7);
                } elseif (str_starts_with($key, 'ext_')) {
                    if ($key === 'ext_d2l_username') {
                        $group = 'http://www.brightspace.com';
                        $claim = 'username';
                    } else {
                        $group = Util::JWT_CLAIM_PREFIX . '/claim/ext';
                        $claim = substr($key, 4);
                    }
                } elseif (str_starts_with($key, 'lti1p1_')) {
                    $group = Util::JWT_CLAIM_PREFIX . '/claim/lti1p1';
                    $claim = substr($key, 7);
                    if (empty($value)) {
                        $value = null;
                    } else {
                        $json = Util::jsonDecode($value);
                        if (!is_null($json)) {
                            $value = $json;
                        }
                    }
                } else {
                    $ok = false;
                }
                if ($ok) {
                    if ($fullyQualified) {
                        if (empty($group)) {
                            $messageClaims = array_merge($messageClaims, self::fullyQualifyClaim($claim, $value));
                        } else {
                            $messageClaims = array_merge($messageClaims, self::fullyQualifyClaim("{$group}/{$claim}", $value));
                        }
                    } elseif (empty($group)) {
                        $messageClaims[$claim] = $value;
                    } else {
                        $messageClaims[$group][$claim] = $value;
                    }
                }
            }
            if (!empty($messageParameters['unmapped_claims'])) {
                $claims = Util::jsonDecode($messageParameters['unmapped_claims']);
                foreach ($claims as $claim => $value) {
                    if ($fullyQualified) {
                        $messageClaims = array_merge($messageClaims, self::fullyQualifyClaim($claim, $value));
                    } elseif (!is_object($value)) {
                        $messageClaims[$claim] = $value;
                    } elseif (!isset($messageClaims[$claim])) {
                        $messageClaims[$claim] = $value;
                    } else {
                        $objVars = get_object_vars($value);
                        foreach ($objVars as $attrName => $attrValue) {
                            if (is_object($messageClaims[$claim])) {
                                $messageClaims[$claim]->{$attrName} = $attrValue;
                            } else {
                                $messageClaims[$claim][$attrName] = $attrValue;
                            }
                        }
                    }
                }
            }
        }

        return $messageClaims;
    }

    /**
     * Parse a set of roles to comply with a specified version of LTI.
     *
     * @param array|string $roles     Comma-separated list of roles or array of roles
     * @param LtiVersion $ltiVersion  LTI version for roles being returned (optional, default is LTI-1p0)
     * @param bool $addPrincipalRole  Add principal role when true (optional, default is false)
     *
     * @return array  Array of roles
     */
    public static function parseRoles(array|string $roles, LtiVersion $ltiVersion = LtiVersion::V1, bool $addPrincipalRole = false): array
    {
        if (!is_array($roles)) {
            $roles = array_map('trim', explode(',', $roles));
            $roles = array_filter($roles);
        }
        $parsedRoles = [];
        foreach ($roles as $role) {
            $role = trim($role);
            if (!str_starts_with($role, 'urn:') &&
                !str_starts_with($role, 'http://') && !str_starts_with($role, 'https://')) {
                switch ($ltiVersion) {
                    case LtiVersion::V1:
                        $role = str_replace('#', '/', $role);
                        $role = "urn:lti:role:ims/lis/{$role}";
                        break;
                    case LtiVersion::V2:
                    case LtiVersion::V1P3:
                        $pos = strrpos($role, '#');
                        if ($pos === false) {
                            $sep = '#';
                        } else {
                            $sep = '/';
                        }
                        $role = "http://purl.imsglobal.org/vocab/lis/v2/membership{$sep}{$role}";
                        break;
                }
            }
            $systemRoles = [
                'AccountAdmin',
                'Administrator',
                'Creator',
                'None',
                'SysAdmin',
                'SysSupport',
                'User'
            ];
            $institutionRoles = [
//            'Administrator',  // System Administrator role takes precedence
                'Alumni',
                'Faculty',
                'Guest',
                'Instructor',
                'Learner',
                'Member',
                'Mentor',
                'None',
                'Observer',
                'Other',
                'ProspectiveStudent',
                'Staff',
                'Student'
            ];
            switch ($ltiVersion) {
                case LtiVersion::V1:
                    if (in_array(substr($role, 0, 53),
                            ['http://purl.imsglobal.org/vocab/lis/v2/system/person#',
                                'http://purl.imsglobal.org/vocab/lis/v2/system/person/'])) {
                        $role = 'urn:lti:sysrole:ims/lis/' . substr($role, 53);
                    } elseif (in_array(substr($role, 0, 58),
                            ['http://purl.imsglobal.org/vocab/lis/v2/institution/person#',
                                'http://purl.imsglobal.org/vocab/lis/v2/institution/person/'])) {
                        $role = 'urn:lti:instrole:ims/lis/' . substr($role, 58);
                    } elseif (str_starts_with($role, 'http://purl.imsglobal.org/vocab/lis/v2/membership#')) {
                        $principalRole = substr($role, 50);
                        if (($principalRole === 'Instructor') &&
                            (!empty(preg_grep('/^http:\/\/purl.imsglobal.org\/vocab\/lis\/v2\/membership\/Instructor#TeachingAssistant.*$/',
                                    $roles)) ||
                            !empty(preg_grep('/^Instructor#TeachingAssistant.*$/', $roles)))) {
                            $role = '';
                        } elseif (!empty(preg_grep("/^http:\/\/purl.imsglobal.org\/vocab\/lis\/v2\/membership\/{$principalRole}#.*$/",
                                    $roles)) ||
                            !empty(preg_grep('/^{$principalRole}#.*$/', $roles))) {
                            $role = '';
                        } else {
                            $role = "urn:lti:role:ims/lis/{$principalRole}";
                        }
                    } elseif (str_starts_with($role, 'http://purl.imsglobal.org/vocab/lis/v2/membership/')) {
                        $subroles = explode('#', substr($role, 50));
                        if (count($subroles) === 2) {
                            if (($subroles[0] === 'Instructor') && ($subroles[1] === 'TeachingAssistant')) {
                                $role = 'urn:lti:role:ims/lis/TeachingAssistant';
                            } elseif (($subroles[0] === 'Instructor') && (str_starts_with($subroles[1], 'TeachingAssistant'))) {
                                $role = "urn:lti:role:ims/lis/TeachingAssistant#{$subroles[1]}";
                            } else {
                                $role = "urn:lti:role:ims/lis/{$subroles[0]}/{$subroles[1]}";
                            }
                        } else {
                            $role = 'urn:lti:role:ims/lis/' . substr($role, 50);
                        }
                    } elseif (in_array(substr($role, 0, 46),
                            ['http://purl.imsglobal.org/vocab/lis/v2/person#',
                                'http://purl.imsglobal.org/vocab/lis/v2/person/'])) {
                        if (in_array(substr($role, 46), $systemRoles)) {
                            $role = 'urn:lti:sysrole:ims/lis/' . substr($role, 46);
                        } elseif (in_array(substr($role, 46), $institutionRoles)) {
                            $role = 'urn:lti:instrole:ims/lis/' . substr($role, 46);
                        }
                    } elseif (strpos($role, 'Instructor#TeachingAssistant') !== false) {
                        if (str_ends_with($role, 'Instructor#TeachingAssistant')) {
                            $role = str_replace('Instructor#', '', $role);
                        } else {
                            $role = str_replace('Instructor#', 'TeachingAssistant/', $role);
                        }
                    } elseif ((str_ends_with($role, 'Instructor')) &&
                        !empty(preg_grep('/^http:\/\/purl.imsglobal.org\/vocab\/lis\/v2\/membership\/Instructor#TeachingAssistant.*$/',
                                $roles))) {
                        $role = '';
                    }
                    $role = str_replace('#', '/', $role);
                    break;
                case LtiVersion::V2:
                    $prefix = '';
                    if (str_starts_with($role, 'urn:lti:sysrole:ims/lis/')) {
                        $prefix = 'http://purl.imsglobal.org/vocab/lis/v2/person';
                        $role = substr($role, 24);
                    } elseif (str_starts_with($role, 'urn:lti:instrole:ims/lis/')) {
                        $prefix = 'http://purl.imsglobal.org/vocab/lis/v2/person';
                        $role = substr($role, 25);
                    } elseif (str_starts_with($role, 'urn:lti:role:ims/lis/')) {
                        $prefix = 'http://purl.imsglobal.org/vocab/lis/v2/membership';
                        $subroles = explode('/', substr($role, 21));
                        if (count($subroles) === 2) {
                            if (($subroles[0] === 'Instructor') && ($subroles[1] === 'TeachingAssistant')) {
                                $role = 'TeachingAssistant';
                            } elseif (($subroles[0] === 'Instructor') && str_starts_with($subroles[1], 'TeachingAssistant')) {
                                $role = "TeachingAssistant#{$subroles[1]}";
                            } else {
                                $role = "{$subroles[0]}#{$subroles[1]}";
                            }
                        } elseif ((count($subroles) === 1) && (!empty(preg_grep("/^http:\/\/purl.imsglobal.org\/vocab\/lis\/v2\/membership\/{$subroles[0]}#.*$/",
                                    $roles)) ||
                            !empty(preg_grep('/^{$subroles[0]}#.*$/', $roles)))) {
                            $role = '';
                        } else {
                            $role = substr($role, 21);
                        }
                    } elseif (in_array(substr($role, 0, 53),
                            ['http://purl.imsglobal.org/vocab/lis/v2/system/person#',
                                'http://purl.imsglobal.org/vocab/lis/v2/system/person/'])) {
                        $prefix = 'http://purl.imsglobal.org/vocab/lis/v2/person';
                        $role = substr($role, 53);
                    } elseif (in_array(substr($role, 0, 58),
                            ['http://purl.imsglobal.org/vocab/lis/v2/institution/person#',
                                'http://purl.imsglobal.org/vocab/lis/v2/institution/person/'])) {
                        $prefix = 'http://purl.imsglobal.org/vocab/lis/v2/person';
                        $role = substr($role, 58);
                    } elseif (str_starts_with($role, 'http://purl.imsglobal.org/vocab/lis/v2/membership#')) {
                        $prefix = 'http://purl.imsglobal.org/vocab/lis/v2/membership';
                        $principalRole = substr($role, 50);
                        $principalRole2 = str_replace('/', '\\/', $principalRole);
                        if (($principalRole === 'Instructor') &&
                            (!empty(preg_grep('/^http:\/\/purl.imsglobal.org\/vocab\/lis\/v2\/membership\/Instructor#TeachingAssistant.*$/',
                                    $roles)) ||
                            !empty(preg_grep('/^Instructor#TeachingAssistant.*$/', $roles)))) {
                            $role = '';
                        } elseif (!empty(preg_grep("/^http:\/\/purl.imsglobal.org\/vocab\/lis\/v2\/membership\/{$principalRole2}#.*$/",
                                    $roles)) ||
                            !empty(preg_grep('/^{$principalRole2}#.*$/', $roles))) {
                            $role = '';
                        } else {
                            $role = $principalRole;
                        }
                    } elseif (str_starts_with($role, 'http://purl.imsglobal.org/vocab/lis/v2/membership/')) {
                        $prefix = 'http://purl.imsglobal.org/vocab/lis/v2/membership';
                        $subroles = explode('#', substr($role, 50));
                        if (count($subroles) === 2) {
                            if (($subroles[0] === 'Instructor') && ($subroles[1] === 'TeachingAssistant')) {
                                $role = 'TeachingAssistant';
                            } elseif (($subroles[0] === 'Instructor') && (str_starts_with($subroles[1], 'TeachingAssistant'))) {
                                $role = "TeachingAssistant#{$subroles[1]}";
                            } else {
                                $role = "{$subroles[0]}#{$subroles[1]}";
                            }
                        } else {
                            $role = substr($role, 50);
                        }
                    }
                    if (!empty($role)) {
                        $pos = strrpos($role, '/');
                        if ((strpos($role, '#') !== false) || ($pos !== false)) {
                            $prefix .= '/';
                            if ($pos !== false) {
                                $role = substr($role, 0, $pos) . '#' . substr($role, $pos + 1);
                            }
                        } else {
                            $prefix .= '#';
                        }
                        $role = "{$prefix}{$role}";
                    }
                    break;
                case LtiVersion::V1P3:
                    $prefix = '';
                    if (str_starts_with($role, 'urn:lti:sysrole:ims/lis/')) {
                        $prefix = 'http://purl.imsglobal.org/vocab/lis/v2/system/person';
                        $role = substr($role, 24);
                    } elseif (str_starts_with($role, 'urn:lti:instrole:ims/lis/')) {
                        $prefix = 'http://purl.imsglobal.org/vocab/lis/v2/institution/person';
                        $role = substr($role, 25);
                    } elseif (str_starts_with($role, 'urn:lti:role:ims/lis/')) {
                        $prefix = 'http://purl.imsglobal.org/vocab/lis/v2/membership';
                        $subroles = explode('/', substr($role, 21));
                        if (count($subroles) === 2) {
                            if ($subroles[0] === 'TeachingAssistant') {
                                $role = "Instructor#{$subroles[1]}";
                                if ($addPrincipalRole) {
                                    $parsedRoles[] = "{$prefix}#Instructor";
                                }
                            } else {
                                $role = "{$subroles[0]}#{$subroles[1]}";
                                if ($addPrincipalRole) {
                                    $parsedRoles[] = "{$prefix}#{$subroles[0]}";
                                }
                            }
                        } elseif ($subroles[0] === 'TeachingAssistant') {
                            $role = 'Instructor#TeachingAssistant';
                            if ($addPrincipalRole) {
                                $parsedRoles[] = "{$prefix}#Instructor";
                            }
                        } else {
                            $role = substr($role, 21);
                        }
                    } elseif (str_starts_with($role, 'http://purl.imsglobal.org/vocab/lis/v2/person#')) {
                        if (in_array(substr($role, 46), $systemRoles)) {
                            $prefix = 'http://purl.imsglobal.org/vocab/lis/v2/system/person';
                        } elseif (in_array(substr($role, 46), $institutionRoles)) {
                            $prefix = 'http://purl.imsglobal.org/vocab/lis/v2/institution/person';
                        }
                        $role = substr($role, 46);
                        $pos = strrpos($role, '/');
                        if ($pos !== false) {
                            $role = substr($role, 0, $pos - 1) . '#' . substr($role, $pos + 1);
                        }
                    } elseif (str_starts_with($role, 'http://purl.imsglobal.org/vocab/lis/v2/membership#')) {
                        $prefix = 'http://purl.imsglobal.org/vocab/lis/v2/membership';
                        if (substr($role, 50, 18) === 'TeachingAssistant') {
                            $role = 'Instructor#TeachingAssistant';
                            if ($addPrincipalRole) {
                                $parsedRoles[] = "{$prefix}#Instructor";
                            }
                        } else {
                            $role = substr($role, 50);
                        }
                    } elseif (str_starts_with($role, 'http://purl.imsglobal.org/vocab/lis/v2/membership/')) {
                        $prefix = 'http://purl.imsglobal.org/vocab/lis/v2/membership';
                        $subroles = explode('#', substr($role, 50));
                        if (count($subroles) === 2) {
                            if ($subroles[0] === 'TeachingAssistant') {
                                $role = "Instructor#{subroles[1]}";
                                if ($addPrincipalRole) {
                                    $parsedRoles[] = "{$prefix}#Instructor";
                                }
                            } else {
                                $role = substr($role, 50);
                                if ($addPrincipalRole) {
                                    $parsedRoles[] = "{$prefix}#{$subroles[0]}";
                                }
                            }
                        } else {
                            $role = substr($role, 50);
                        }
                    }
                    if (!empty($prefix) && !empty($role)) {
                        $pos = strrpos($role, '/');
                        if ((strpos($role, '#') !== false) || ($pos !== false)) {
                            $prefix .= '/';
                            if ($pos !== false) {
                                $role = substr($role, 0, $pos) . '#' . substr($role, $pos + 1);
                            }
                        } else {
                            $prefix .= '#';
                        }
                        $role = "{$prefix}{$role}";
                    }
                    break;
            }
            if (!empty($role)) {
                $parsedRoles[] = $role;
            }
        }

        return array_unique($parsedRoles);
    }

    /**
     * Add the signature to an LTI message.
     *
     * @param string $url               URL for message request
     * @param string $type              LTI message type
     * @param string $ltiVersionString  LTI version
     * @param array $params             Message parameters
     *
     * @return array|string  Array of signed message parameters or request headers
     */
    public function signParameters(string $url, string $type, string $ltiVersionString, array $params): array|string
    {
        if (!empty($url)) {
// Add standard parameters
            if (!empty($ltiVersionString)) {
                $params['lti_version'] = $ltiVersionString;
            } else {
                unset($params['lti_version']);
            }
            $params['lti_message_type'] = $type;
// Add signature
            $params = $this->addSignature($url, $params, 'POST', 'application/x-www-form-urlencoded');
        }

        return $params;
    }

    /**
     * Add the signature to an LTI message.
     *
     * If the message is being sent from a platform using LTI 1.3, then the parameters and URL will be saved and replaced with an
     * initiate login request.
     *
     * @param string $url                  URL for message request
     * @param string $type                 LTI message type
     * @param string $ltiVersionString     LTI version
     * @param array $params                Message parameters
     * @param string|null $loginHint       ID of user (optional)
     * @param string|null $ltiMessageHint  LTI message hint (optional, use null for none)
     *
     * @return array|string  Array of signed message parameters or request headers
     */
    public function signMessage(string &$url, string $type, string $ltiVersionString, array $params, ?string $loginHint = null,
        ?string $ltiMessageHint = null): array
    {
        if (($this instanceof Platform) && ($this->ltiVersion === LtiVersion::V1P3)) {
            if (!isset($loginHint) || (strlen($loginHint) <= 0)) {
                if (isset($params['user_id']) && (strlen($params['user_id']) > 0)) {
                    $loginHint = $params['user_id'];
                } else {
                    $loginHint = 'Anonymous';
                }
            }
// Add standard parameters
            if (!empty($ltiVersionString)) {
                $params['lti_version'] = $ltiVersionString;
            } else {
                unset($params['lti_version']);
            }
            $params['lti_message_type'] = $type;
            $this->onInitiateLogin($url, $loginHint, $ltiMessageHint, $params);

            $params = [
                'iss' => $this->platformId,
                'target_link_uri' => $url,
                'login_hint' => $loginHint
            ];
            if (!is_null($ltiMessageHint)) {
                $params['lti_message_hint'] = $ltiMessageHint;
            }
            if (!empty($this->clientId)) {
                $params['client_id'] = $this->clientId;
            }
            if (!empty($this->deploymentId)) {
                $params['lti_deployment_id'] = $this->deploymentId;
            }
            if (!empty(Tool::$defaultTool)) {
                $url = Tool::$defaultTool->initiateLoginUrl;
            }
            if (!empty(static::$browserStorageFrame)) {
                if (strpos($url, '?') === false) {
                    $sep = '?';
                } else {
                    $sep = '&';
                }
                $url .= "{$sep}lti_storage_target=" . static::$browserStorageFrame;
            }
        } else {
            $params = $this->signParameters($url, $type, $ltiVersionString, $params);
        }

        return $params;
    }

    /**
     * Generate a web page containing an auto-submitted form of LTI message parameters.
     *
     * @param string $url           URL to which the form should be submitted
     * @param string $type          LTI message type
     * @param array $messageParams  Array of form parameters
     * @param string $target        Name of target (optional)
     * @param string|null $userId   ID of user (optional)
     * @param string|null $hint     LTI message hint (optional, use null for none)
     *
     * @return string
     */
    public function sendMessage(string $url, string $type, array $messageParams, string $target = '', ?string $userId = null,
        ?string $hint = null): string
    {
        $sendParams = $this->signMessage($url, $type, $this->ltiVersion ? $this->ltiVersion->value : '', $messageParams, $userId,
            $hint);
        $html = Util::sendForm($url, $sendParams, $target);

        return $html;
    }

    /**
     * Generates the headers for an LTI service request.
     *
     * @param string $url              URL for message request
     * @param string $method           HTTP method
     * @param string $type             Media type
     * @param array|string|null $data  Data being passed in request body (optional)
     *
     * @return array|string  Headers to include with service request
     */
    public function signServiceRequest(string $url, string $method, string $type, array|string|null $data = null): array|string
    {
        $header = '';
        if (!empty($url)) {
            $header = $this->addSignature($url, $data, $method, $type);
        }

        return $header;
    }

    /**
     * Perform a service request
     *
     * @param ServiceDefinition $service  Service object to be executed
     * @param string $method              HTTP action
     * @param string $format              Media type
     * @param array|string $data          Array of parameters or body string
     *
     * @return HttpMessage  HTTP object containing request and response details
     */
    public function doServiceRequest(ServiceDefinition $service, string $method, string $format, array|string $data): HttpMessage
    {
        $header = $this->addSignature($service->endpoint, $data, $method, $format);

// Connect to platform
        $http = new HttpMessage($service->endpoint, $method, $data, $header);
// Parse JSON response
        if ($http->send() && !empty($http->response)) {
            $http->responseJson = Util::jsonDecode($http->response);
            $http->ok = !is_null($http->responseJson);
        }

        return $http;
    }

    /**
     * Determine whether this consumer is using the OAuth 1 security model.
     *
     * @return bool  True if OAuth 1 security model should be used
     */
    public function useOAuth1(): bool
    {
        return empty($this->signatureMethod) || str_starts_with($this->signatureMethod, 'HMAC');
    }

    /**
     * Add the signature to an array of message parameters or to a header string.
     *
     * @param string $endpoint         URL to which message is being sent
     * @param array|string|null $data  Data to be passed
     * @param string $method           HTTP method
     * @param string|null $type        Content type of data being passed
     * @param string|null $nonce       Nonce value for JWT
     * @param string|null $hash        OAuth body hash value
     * @param int|null $timestamp      Timestamp
     *
     * @return array|string  Array of signed message parameters or header string
     */
    public function addSignature(string $endpoint, array|string|null $data, string $method = 'POST', ?string $type = null,
        ?string $nonce = '', ?string $hash = null, ?int $timestamp = null): array|string
    {
        if ($this->useOAuth1()) {
            return $this->addOAuth1Signature($endpoint, $data, $method, $type, $hash, $timestamp);
        } else {
            return $this->addJWTSignature($endpoint, $data, $method, $type, $nonce, $timestamp);
        }
    }

    /**
     * Get the last signature base string.
     *
     * @return string|null  Signature base string
     */
    public function getBaseString(): ?string
    {
        return $this->baseString;
    }

    /**
     * Verify the required properties of an LTI message.
     *
     * @param bool $generateWarnings    True if warning messages should be generated
     *
     * @return bool  True if it is a valid LTI message
     */
    public function checkMessage(bool $generateWarnings = false): bool
    {
        $this->ok = $_SERVER['REQUEST_METHOD'] === 'POST';
        if (!$this->ok) {
            $this->setReason('LTI messages must use HTTP POST');
        } else {
// Set signature method from request
            if (isset($this->messageParameters['oauth_signature_method'])) {
                $this->signatureMethod = $this->messageParameters['oauth_signature_method'];
                if (($this instanceof Tool) && !empty($this->platform)) {
                    $this->platform->signatureMethod = $this->signatureMethod;
                }
            }
// Check all required launch parameters
            if ($this->ok || $generateWarnings) {
                if (!isset($this->messageParameters['lti_message_type'])) {
                    $this->setReason('Missing \'lti_message_type\' parameter');
                }
            }
            if ($this->ok || $generateWarnings) {
                if (isset($this->messageParameters['lti_version'])) {
                    $this->ltiVersion = LtiVersion::tryFrom($this->messageParameters['lti_version']);
                }
                if (empty($this->ltiVersion)) {
                    $this->setReason('Invalid or missing \'lti_version\' parameter');
                }
            }
        }

        return $this->ok;
    }

    /**
     * Verify the signature of a message.
     *
     * @param bool $generateWarnings    True if warning messages should be generated
     *
     * @return bool  True if the signature is valid
     */
    public function verifySignature($generateWarnings = false): bool
    {
        $key = $this->key;
        if (!empty($key)) {
            $secret = $this->secret;
        } elseif (($this instanceof Tool) && !empty($this->platform)) {
            $key = $this->platform->getKey();
            $secret = $this->platform->secret;
        } elseif (($this instanceof Platform) && !empty(Tool::$defaultTool)) {
            $key = Tool::$defaultTool->getKey();
            $secret = Tool::$defaultTool->secret;
        }
        if ($this instanceof Tool) {
            $platform = $this->platform;
            $publicKey = $this->platform->rsaKey;
            $jku = $this->platform->jku;
        } else {
            $platform = $this;
            if (!empty(Tool::$defaultTool)) {
                $publicKey = Tool::$defaultTool->rsaKey;
                $jku = Tool::$defaultTool->jku;
            } else {
                $publicKey = $this->rsaKey;
                $jku = $this->jku;
            }
        }
        if (!empty($this->jwt) && !empty($this->jwt->hasJwt())) {  // JWT-signed message
            $nonce = new PlatformNonce($platform, $this->jwt->getClaim('nonce'));
            $ok = !$nonce->load();
            if ($ok) {
                $ok = $nonce->save();
            }
            if (!$ok) {
                $this->setReason('Invalid nonce');
            } elseif (!empty($publicKey) || !empty($jku) || Jwt::$allowJkuHeader) {
                $currentKey = $publicKey;
                if (!empty($publicKey) && $this->jwt->hasHeader('kid')) {
                    if ($this instanceof Tool) {
                        $cachedKid = $this->platform->kid;
                    } else {
                        $cachedKid = Tool::$defaultTool->kid;
                    }
                    if (!empty($jku) && !empty($cachedKid) && ($cachedKid !== $this->jwt->getHeader('kid'))) {  // Ignore cached key
                        $publicKey = null;
                    }
                }
                $ok = $this->jwt->verifySignature($publicKey, $jku);
                if (!Util::$disableFetchedPublicKeysSave && ($currentKey !== $publicKey)) {
                    $kid = null;
                    if ($this->jwt->hasHeader('kid')) {
                        $kid = $this->jwt->getHeader('kid');
                    }
                    if ($this instanceof Tool) {
                        $this->platform->rsaKey = $publicKey;
                        $this->platform->kid = $kid;
                        $this->platform->save();
                    } else {
                        if (!empty(Tool::$defaultTool)) {
                            Tool::$defaultTool->rsaKey = $publicKey;
                            Tool::$defaultTool->kid = $kid;
                            Tool::$defaultTool->save();
                        } else {
                            $this->rsaKey = $publicKey;
                            $this->kid = $kid;
                        }
                    }
                }
                if (!$ok) {
                    $this->setReason('JWT signature check failed - perhaps an invalid public key or timestamp');
                }
            } else {
                $this->setReason('Unable to verify JWT signature as neither a public key nor a JSON Web Key URL is specified');
            }
        } else {
            $request = OAuth\OAuthRequest::from_request();
            $parameters = $request->get_parameters();
            if (!isset($parameters['client_assertion_type'])) {  // OAuth-signed message
                try {
                    $store = new OAuthDataStore($this);
                    $server = new OAuth\OAuthServer($store);
                    $method = new OAuth\OAuthSignatureMethod_HMAC_SHA224();
                    $server->add_signature_method($method);
                    $method = new OAuth\OAuthSignatureMethod_HMAC_SHA256();
                    $server->add_signature_method($method);
                    $method = new OAuth\OAuthSignatureMethod_HMAC_SHA384();
                    $server->add_signature_method($method);
                    $method = new OAuth\OAuthSignatureMethod_HMAC_SHA512();
                    $server->add_signature_method($method);
                    $method = new OAuth\OAuthSignatureMethod_HMAC_SHA1();
                    $server->add_signature_method($method);
                    $request = OAuth\OAuthRequest::from_request();
                    if (isset($parameters['_new_window']) && !isset($this->messageParameters['_new_window'])) {
                        $request->unset_parameter('_new_window');
                    }
                    $server->verify_request($request);
                } catch (\Exception $e) {
                    $this->ok = false;
                    if (empty($this->reason)) {
                        $oauthConsumer = new OAuth\OAuthConsumer($key, $secret);
                        $signature = $request->build_signature($method, $oauthConsumer, null);
                        if ($this->debugMode) {
                            $this->setReason($e->getMessage());
                        }
                        if (empty($this->reason)) {
                            $this->setReason('OAuth signature check failed - perhaps an incorrect secret or timestamp');
                        }
                        $this->details[] = "Shared secret: '{$secret}'";
                        $this->details[] = 'Current timestamp: ' . time();
                        $this->details[] = "Expected signature: {$signature}";
                        $this->details[] = "Base string: {$request->base_string}";
                    }
                }
            } elseif (isset($parameters['grant_type']) && ($parameters['grant_type'] === 'client_credentials') &&
                ($parameters['client_assertion_type'] === 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer') &&
                isset($parameters['client_assertion']) && !empty($parameters['scope'])) {
                $jwt = Jwt::getJwtClient();
                $ok = $jwt->load($parameters['client_assertion']);
                if (!$ok) {
                    $this->setReason('Request does not contain a valid client_assertion JWT');
                } else {
                    $this->jwt = $jwt;
                    if ($this->ok || $generateWarnings) {
                        $iat = $this->getClaimInteger('iat', true, $generateWarnings);
                    }
                    if ($this->ok || $generateWarnings) {
                        $exp = $this->getClaimInteger('exp', true, $generateWarnings);
                    }
                    if (($this->ok || $generateWarnings) && !is_null($iat) && !is_null($exp) && ($iat > $exp)) {
                        $this->setReason('\'iat\' claim must not have a value greater than \'exp\' claim');
                    }
                    if ($this->ok || $generateWarnings) {
                        $nonce = $this->getClaimString('jti', true, true, $generateWarnings);
                    }
                    if ($this->ok || $generateWarnings) {
                        $iss = $this->getClaimString('iss', true, true, $generateWarnings);
                    }
                    if ($this->ok || $generateWarnings) {
                        $sub = $this->getClaimString('sub', true, true, $generateWarnings);
                    }
                    if ($this->ok || $generateWarnings) {
                        if ($sub !== $iss) {
                            $this->setReason('\'iss\' and \'sub\' claims must have the same value');
                        } elseif ($sub !== $platform->clientId) {
                            $this->setReason('\'iss\' and \'sub\' claim values do not match the client ID');
                        }
                    }
                    if ($this->ok || $generateWarnings) {
                        $aud = $this->getClaimArray('aud', true, true, $generateWarnings);
                    }
                    if ($this->ok || $generateWarnings) {
                        if (is_array($aud) && !in_array($platform->accessTokenUrl, $aud)) {
                            $this->setReason('\'aud\' claim must contain access token URL');
                        }
                    }
                    if ($this->ok || $generateWarnings) {
                        $deploymentId = $this->getClaimString(Util::JWT_CLAIM_PREFIX . '/claim/deployment_id', false, true,
                            $generateWarnings);
                    }
                }
                if ($ok) {
                    $ok = $jwt->verifySignature($publicKey, $jku);
                    if (!$ok) {
                        $this->setReason('Invalid JWT signature');
                    }
                }
            } else {
                $this->setReason('Invalid request');
            }
        }

        return $this->ok;
    }

    /**
     * Set the error reason.
     *
     * @param string $reason  Reason value
     *
     * @return bool  Returns false
     */
    public function setReason(string $reason): bool
    {
        $this->ok = false;
        Util::setMessage(true, $reason);
        if (empty($this->reason)) {
            $this->reason = $reason;
        }

        return false;
    }

###
###    PRIVATE METHODS
###

    /**
     * Parse the message.
     *
     * @param bool $disableCookieCheck  True if no cookie check should be made
     * @param bool $generateWarnings    True if warning messages should be generated
     *
     * @return void
     */
    private function parseMessage(bool $disableCookieCheck, bool $generateWarnings): void
    {
        $this->getRawParameters();
        if (isset($this->rawParameters['id_token']) || isset($this->rawParameters['JWT'])) {  // JWT-signed message
            try {
                $this->jwt = Jwt::getJwtClient();
                if (isset($this->rawParameters['id_token'])) {
                    $this->ok = $this->jwt->load($this->rawParameters['id_token'], $this->rsaKey);
                } else {
                    $this->ok = $this->jwt->load($this->rawParameters['JWT'], $this->rsaKey);
                }
                if (!$this->ok) {
                    $this->setReason('Message does not contain a valid JWT');
                } else {
                    if ($this->ok || $generateWarnings) {
                        $iat = $this->getClaimInteger('iat', true, $generateWarnings);
                    }
                    if ($this->ok || $generateWarnings) {
                        $exp = $this->getClaimInteger('exp', true, $generateWarnings);
                    }
                    if (($this->ok || $generateWarnings) && !is_null($iat) && !is_null($exp) && ($iat > $exp)) {
                        $this->setReason('\'iat\' claim must not have a value greater than \'exp\' claim');
                    }
                    if ($this->ok || $generateWarnings) {
                        $nonce = $this->getClaimString('nonce', true, true, $generateWarnings);
                    }
                    if ($this->ok || $generateWarnings) {
                        $iss = $this->getClaimString('iss', true, true, $generateWarnings);
                    }
                    if ($this->ok || $generateWarnings) {
                        $azp = $this->getClaimString('azp', false, true, $generateWarnings);
                    }
                    if ($this->ok || $generateWarnings) {
                        $aud = $this->jwt->getClaim('aud');
                        if (is_string($aud)) {
                            $aud = [$aud];
                        }
                        $aud = $this->checkClaimArray('aud', $aud, true, true, $generateWarnings);
                        if (!empty($aud)) {
                            if (!empty($azp)) {
                                if (in_array($azp, $aud)) {
                                    $aud = $azp;
                                } else {
                                    $this->setReason('\'azp\' claim value is not included in \'aud\' claim');
                                }
                            } else {
                                $aud = $aud[0];
                                if (empty($aud)) {
                                    $this->setReason('First element of \'aud\' claim is empty');
                                }
                            }
                        }
                    }
                    if ($this->ok || $generateWarnings) {
                        $deploymentId = $this->getClaimString(Util::JWT_CLAIM_PREFIX . '/claim/deployment_id', true, true,
                            $generateWarnings);
                    }
                    if ($this->ok) {
                        if ($this instanceof Tool) {
                            $this->platform = Platform::fromPlatformId($iss, $aud, $deploymentId, $this->dataConnector);
                            $this->platform->platformId = $iss;
                            if (isset($this->rawParameters['id_token'])) {
                                $this->ok = !empty($this->rawParameters['state']);
                                if ($this->ok) {
                                    $state = $this->rawParameters['state'];
                                    $parts = explode('.', $state);
                                    if (!empty(session_id()) && (count($parts) > 1) && (session_id() !== $parts[1]) &&
                                        ($parts[1] !== 'platformStorage')) {  // Reset to original session
                                        session_abort();
                                        session_id($parts[1]);
                                        session_start();
                                        $this->onResetSessionId();
                                    }
                                    $usePlatformStorage = (str_ends_with($state, '.platformStorage'));
                                    if ($usePlatformStorage) {
                                        $state = substr($state, 0, -16);
                                    }
                                    $this->onAuthenticate($state, $nonce, $usePlatformStorage);
                                    if (!$disableCookieCheck) {
                                        if (empty($_COOKIE) && !isset($_POST['_new_window'])) {  // Reopen in a new window
                                            Util::setTestCookie();
                                            $_POST['_new_window'] = '';
                                            echo Util::sendForm($_SERVER['REQUEST_URI'], $_POST, '_blank');
                                            $this->doExit();
                                        }
                                        Util::setTestCookie(true);
                                    }
                                } else {
                                    $this->setReason('\'state\' parameter is missing');
                                }
                                if ($this->ok) {
                                    $nonce = new PlatformNonce($this->platform, $state);
                                    $this->ok = $nonce->load();
                                    if (!$this->ok) {
                                        $platform = Platform::fromPlatformId($iss, $aud, null, $this->dataConnector);
                                        $nonce = new PlatformNonce($platform, $state);
                                        $this->ok = $nonce->load();
                                    }
                                    if (!$this->ok) {
                                        $platform = Platform::fromPlatformId($iss, null, null, $this->dataConnector);
                                        $nonce = new PlatformNonce($platform, $state);
                                        $this->ok = $nonce->load();
                                    }
                                    if ($this->ok) {
                                        $this->ok = $nonce->delete();
                                    }
                                    if (!$this->ok) {
                                        $this->setReason('\'state\' parameter is invalid or has expired');
                                    }
                                }
                            }
                        }
                        $this->messageParameters = [];
                        if ($this->ok) {
                            $this->messageParameters['oauth_consumer_key'] = $aud;
                            $this->messageParameters['oauth_signature_method'] = $this->jwt->getHeader('alg');
                            $this->parseClaims($generateWarnings);
                            $url = $this->jwt->getClaim(Util::JWT_CLAIM_PREFIX . '/claim/target_link_uri');
                            if (!empty($url)) {
                                $queryString = parse_url($url, PHP_URL_QUERY);
                                if ($queryString) {
                                    $this->messageParameters = array_merge($this->getCustomQueryParameters($queryString),
                                        $this->messageParameters);
                                }
                            }
                        }
                    }
                    if ($this->ok || $generateWarnings) {
                        if (isset($iss) && isset($this->rawParameters['iss']) && ($this->rawParameters['iss'] !== $iss)) {
                            $this->setReason('\'iss\' parameter does not match \'iss\' claim');
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->setReason('Message does not contain a valid JWT');
            }
        } elseif (isset($this->rawParameters['error'])) {  // Error with JWT-signed message
            $reason = $this->rawParameters['error'];
            if (!empty($this->rawParameters['error_description'])) {
                $reason .= ": {$this->rawParameters['error_description']}";
            }
            $this->setReason($reason);
        } else {  // OAuth
            if ($this instanceof Tool) {
                if (isset($this->rawParameters['oauth_consumer_key'])) {
                    $this->platform = Platform::fromConsumerKey($this->rawParameters['oauth_consumer_key'], $this->dataConnector);
                }
                if (isset($this->rawParameters['tool_state'])) {  // Relaunch?
                    $state = $this->rawParameters['tool_state'];
                    if (!$disableCookieCheck) {
                        $parts = explode('.', $state);
                        if (empty($_COOKIE) && !isset($_POST['_new_window'])) {  // Reopen in a new window
                            Util::setTestCookie();
                            $_POST['_new_window'] = '';
                            echo Util::sendForm($_SERVER['REQUEST_URI'], $_POST, '_blank');
                            $this->doExit();
                        } elseif (!empty(session_id()) && (count($parts) > 1) && (session_id() !== $parts[1])) {  // Reset to original session
                            session_abort();
                            session_id($parts[1]);
                            session_start();
                            $this->onResetSessionId();
                        }
                        unset($this->rawParameters['_new_window']);
                        Util::setTestCookie(true);
                    }
                    $nonce = new PlatformNonce($this->platform, $state);
                    $this->ok = $nonce->load();
                    if (!$this->ok) {
                        $this->setReason("Invalid tool_state parameter: '{$state}'");
                    }
                }
            }
            $this->messageParameters = array_merge($this->getCustomQueryParameters($_SERVER['QUERY_STRING']), $this->rawParameters);
        }
    }

    /**
     * Parse the claims
     *
     * @param bool $generateWarnings  True if warning messages should be generated
     *
     * @return void
     */
    private function parseClaims(bool $generateWarnings): void
    {
        $payload = Util::cloneObject($this->jwt->getPayload());
        foreach (Util::JWT_CLAIM_MAPPING as $key => $mapping) {
            $claim = Util::JWT_CLAIM_PREFIX;
            if (!empty($mapping['suffix'])) {
                $claim .= "-{$mapping['suffix']}";
            }
            $claim .= '/claim/';
            if (is_null($mapping['group'])) {
                $claim = $mapping['claim'];
            } elseif (empty($mapping['group'])) {
                $claim .= $mapping['claim'];
            } else {
                $claim .= $mapping['group'];
            }
            if ($this->jwt->hasClaim($claim)) {
                $value = null;
                if (empty($mapping['group'])) {
                    unset($payload->{$claim});
                    $value = $this->jwt->getClaim($claim);
                } else {
                    $group = $this->jwt->getClaim($claim);
                    if (is_array($group) && array_key_exists($mapping['claim'], $group)) {
                        unset($payload->{$claim}[$mapping['claim']]);
                        $value = $group[$mapping['claim']];
                        $claim .= "[{$mapping['claim']}]";
                    } elseif (is_object($group) && isset($group->{$mapping['claim']})) {
                        unset($payload->{$claim}->{$mapping['claim']});
                        $value = $group->{$mapping['claim']};
                        $claim .= "/{$mapping['claim']}";
                    }
                }
                if (!is_null($value)) {
                    if (isset($mapping['isArray']) && $mapping['isArray']) {
                        if (!is_array($value)) {
                            $value = $this->checkClaimArray($claim, $value, false, false, $generateWarnings);
                        }
                        if (is_array($value)) {
                            $value = implode(',', $value);
                        }
                    } elseif (isset($mapping['isObject']) && $mapping['isObject']) {
                        if (!is_object($value)) {
                            $value = $this->checkClaimObject($claim, $value, false);
                        }
                        $values = [];
                        if (is_object($value)) {
                            $props = get_object_vars($value);
                            foreach ($props as $k => $v) {
                                $values[] = "{$k}={$v}";
                            }
                            $value = implode(',', $values);
                        }
                    } elseif (isset($mapping['isContentItemSelection']) && $mapping['isContentItemSelection']) {
                        $value = $this->checkClaimArray($claim, $value, false);
                        if (is_array($value)) {
                            $value = json_encode($value, JSON_UNESCAPED_SLASHES);
                        }
                    } elseif (isset($mapping['isBoolean']) && $mapping['isBoolean']) {
                        $value = $this->checkClaimBoolean($claim, $value, false, $generateWarnings);
                        if (is_bool($value)) {
                            $value = $value ? 'true' : 'false';
                        }
                    } elseif (isset($mapping['isInteger']) && $mapping['isInteger']) {
                        $value = $this->checkClaimInteger($claim, $value, false, $generateWarnings);
                        $value = Util::valToString($value);
                    } else {
                        $value = $this->checkClaimString($claim, $value, false, false, $generateWarnings);
                    }
                }
                if (is_string($value)) {
                    $this->messageParameters[$key] = $value;
                }
            }
        }
        if (!empty($this->messageParameters['lti_message_type']) &&
            in_array($this->messageParameters['lti_message_type'], array_values(Util::MESSAGE_TYPE_MAPPING))) {
            $this->messageParameters['lti_message_type'] = array_search($this->messageParameters['lti_message_type'],
                Util::MESSAGE_TYPE_MAPPING);
        }
        if (!empty($this->messageParameters['accept_types'])) {
            $types = array_map('trim', explode(',', $this->messageParameters['accept_types']));
            $types = array_filter($types);
            $mediaTypes = [];
            if (!empty($this->messageParameters['accept_media_types'])) {
                $mediaTypes = array_map('trim', explode(',', $this->messageParameters['accept_media_types']));
                $mediaTypes = array_filter($mediaTypes);
            }
            if (in_array(Item::TYPE_LTI_LINK, $types)) {
                $mediaTypes[] = Item::LTI_LINK_MEDIA_TYPE;
            }
            if (in_array(Item::TYPE_LTI_ASSIGNMENT, $types)) {
                $mediaTypes[] = Item::LTI_ASSIGNMENT_MEDIA_TYPE;
            }
            if (in_array('html', $types) && !in_array('*/*', $mediaTypes)) {
                $mediaTypes[] = 'text/html';
            }
            if (in_array('image', $types) && !in_array('*/*', $mediaTypes)) {
                $mediaTypes[] = 'image/*';
            }
            $mediaTypes = array_unique($mediaTypes);
            $this->messageParameters['accept_media_types'] = implode(',', $mediaTypes);
        }
        $claim = Util::JWT_CLAIM_PREFIX . '/claim/custom';
        if ($this->jwt->hasClaim($claim)) {
            unset($payload->{$claim});
            $custom = $this->getClaimObject($claim, false);
            if (is_object($custom)) {
                foreach ($custom as $key => $value) {
                    if (!is_string($value)) {
                        if (Util::$strictMode) {
                            $this->setReason("Properties of the '{$claim}' claim object must have string values (" . gettype($value) . ' found)');
                        } else {
                            Util::setMessage(false,
                                "Properties of the '{$claim}' claim object should have string values (" . gettype($value) . ' found)');
                            $value = Util::valToString($value);
                        }
                    }
                    if (is_string($value)) {
                        $this->messageParameters["custom_{$key}"] = $value;
                    }
                }
            }
        }
        $claim = Util::JWT_CLAIM_PREFIX . '/claim/ext';
        if ($this->jwt->hasClaim($claim)) {
            unset($payload->{$claim});
            $ext = $this->getClaimObject($claim, false);
            if (is_object($ext)) {
                foreach ($ext as $key => $value) {
                    if (!is_string($value)) {
                        if (Util::$strictMode) {
                            $this->setReason("Properties of the '{$claim}' claim object must have string values (" . gettype($value) . ' found)');
                        } else {
                            Util::setMessage(false,
                                "Properties of the '{$claim}' claim object should have string values (" . gettype($value) . ' found)');
                            $value = Util::valToString($value);
                        }
                    }
                    if (is_string($value)) {
                        $this->messageParameters["ext_{$key}"] = $value;
                    }
                }
            }
        }
        $claim = Util::JWT_CLAIM_PREFIX . '/claim/lti1p1';
        if ($this->jwt->hasClaim($claim)) {
            unset($payload->{$claim});
            $lti1p1 = $this->getClaimObject($claim, false);
            if (is_array($lti1p1)) {
                foreach ($lti1p1 as $key => $value) {
                    if (is_null($value)) {
                        $value = '';
                    } elseif (is_object($value)) {
                        $value = json_encode($value);
                    } elseif (!is_string($value)) {
                        if (Util::$strictMode) {
                            $this->setReason("Properties of the '{$claim}' claim object must have string or object values (" . gettype($value) . ' found)');
                        } else {
                            Util::setMessage(false,
                                "Properties of the '{$claim}' claim object should have string or object values (" . gettype($value) . ' found)');
                            $value = Util::valToString($value);
                        }
                    }
                    if (is_string($value)) {
                        $this->messageParameters["lti1p1_{$key}"] = $value;
                    }
                }
            }
        }
        $claim = 'http://www.brightspace.com';
        if ($this->jwt->hasClaim($claim)) {
            $d2l = $this->jwt->getClaim($claim);
            if (is_array($d2l)) {
                if (!empty($d2l['username'])) {
                    $this->messageParameters['ext_d2l_username'] = Util::valToString($d2l['username']);
                    unset($payload->{$claim}['username']);
                }
            } elseif (isset($ext) && is_object($ext)) {
                if (!empty($d2l->username)) {
                    $this->messageParameters['ext_d2l_username'] = Util::valToString($d2l->username);
                    unset($payload->{$claim}->username);
                }
            }
        }
        if (!empty($payload)) {
            $objVars = get_object_vars($payload);
            foreach ($objVars as $attrName => $attrValue) {
                if (empty((array) $attrValue)) {
                    unset($payload->{$attrName});
                }
            }
            $this->messageParameters['unmapped_claims'] = json_encode($payload);
        }
    }

    /**
     * Call any callback function for the requested action.
     *
     * This function may set the redirect_url and output properties.
     *
     * @return void
     */
    private function doCallback(): void
    {
        if (array_key_exists($this->messageParameters['lti_message_type'], Util::$METHOD_NAMES)) {
            $callback = Util::$METHOD_NAMES[$this->messageParameters['lti_message_type']];
        } else {
            $callback = "on{$this->messageParameters['lti_message_type']}";
        }
        if (method_exists($this, $callback)) {
            $this->$callback();
        } elseif ($this->ok) {
            $this->setReason("Message type not supported: {$this->messageParameters['lti_message_type']}");
        }
    }

    /**
     * Add the OAuth 1 signature to an array of message parameters or to a header string.
     *
     * @param string $endpoint         URL to which message is being sent
     * @param array|string|null $data  Data to be passed
     * @param string $method           HTTP method
     * @param string|null $type        Content type of data being passed
     * @param string|null $hash        OAuth body hash value
     * @param int|null $timestamp      Timestamp
     *
     * @return string[]|string  Array of signed message parameters or header string
     */
    private function addOAuth1Signature(string $endpoint, array|string|null $data, string $method, ?string $type, ?string $hash,
        ?int $timestamp): array|string
    {
        $params = [];
        if (is_array($data)) {
            $params = $data;
            $params['oauth_callback'] = 'about:blank';
        }
// Check for query parameters which need to be included in the signature
        $queryString = parse_url($endpoint, PHP_URL_QUERY);
        if ($queryString) {
            $queryParams = OAuth\OAuthUtil::parse_parameters($queryString);
        } else {
            $queryParams = [];
        }

        if (!is_array($data)) {
            if (empty($hash)) {  // Calculate body hash
                if (is_null($data)) {
                    $data = '';
                }
                $hash = match ($this->signatureMethod) {
                    'HMAC-SHA1' => base64_encode(sha1($data, true)),
                    'HMAC-SHA224' => base64_encode(hash('sha224', $data, true)),
                    'HMAC-SHA256' => base64_encode(hash('sha256', $data, true)),
                    'HMAC-SHA384' => base64_encode(hash('sha384', $data, true)),
                    'HMAC-SHA512' => base64_encode(hash('sha512', $data, true)),
                    default => null
                };
            }
            if (!empty($hash)) {
                $params['oauth_body_hash'] = $hash;
            }
        }
        if (!empty($timestamp)) {
            $params['oauth_timestamp'] = strval($timestamp);
        }

// Add OAuth signature
        $hmacMethod = match ($this->signatureMethod) {
            'HMAC-SHA1' => new OAuth\OAuthSignatureMethod_HMAC_SHA1(),
            'HMAC-SHA224' => new OAuth\OAuthSignatureMethod_HMAC_SHA224(),
            'HMAC-SHA256' => new OAuth\OAuthSignatureMethod_HMAC_SHA256(),
            'HMAC-SHA384' => new OAuth\OAuthSignatureMethod_HMAC_SHA384(),
            'HMAC-SHA512' => new OAuth\OAuthSignatureMethod_HMAC_SHA512(),
            default => null
        };
        $key = $this->key;
        $secret = $this->secret;
        if (empty($key)) {
            if (($this instanceof Tool) && !empty($this->platform)) {
                $key = $this->platform->getKey();
                $secret = $this->platform->secret;
            } elseif (($this instanceof Platform) && !empty(Tool::$defaultTool)) {
                $key = Tool::$defaultTool->getKey();
                $secret = Tool::$defaultTool->secret;
            }
        }
        if (is_null($key)) {
            $key = '';
        }
        if (is_null($secret)) {
            $secret = '';
        }
        $oauthConsumer = new OAuth\OAuthConsumer($key, $secret, null);
        $oauthReq = OAuth\OAuthRequest::from_consumer_and_token($oauthConsumer, null, $method, $endpoint, $params);
        if ($hmacMethod) {
            $oauthReq->sign_request($hmacMethod, $oauthConsumer, null);
            $this->baseString = $oauthReq->base_string;
        } else {
            $oauthReq->set_parameter('oauth_signature_method', $this->signatureMethod, false);
            $this->baseString = null;
        }
        if (!is_array($data)) {
            $header = $oauthReq->to_header();
            if (empty($data)) {
                if (!empty($type)) {
                    $header .= "\nAccept: {$type}";
                }
            } elseif (isset($type)) {
                $header .= "\nContent-Type: {$type}; charset=UTF-8";
                $header .= "\nContent-Length: " . strlen($data);
            }
            return $header;
        } else {
// Remove parameters from query string
            $params = $oauthReq->get_parameters();
            foreach ($queryParams as $key => $value) {
                if (!is_array($value)) {
                    if (!is_array($params[$key])) {
                        if ($params[$key] === $value) {
                            unset($params[$key]);
                        }
                    } else {
                        $k = array_search($value, $params[$key]);
                        if ($k !== false) {
                            unset($params[$key][$k]);
                        }
                    }
                } else {
                    foreach ($value as $element) {
                        $k = array_search($element, $params[$key]);
                        if ($k !== false) {
                            unset($params[$key][$k]);
                        }
                    }
                }
            }
// Remove any parameters comprising an empty array of values
            foreach ($params as $key => $value) {
                if (is_array($value)) {
                    if (count($value) <= 0) {
                        unset($params[$key]);
                    } elseif (count($value) === 1) {
                        $params[$key] = reset($value);
                    }
                }
            }
            return $params;
        }
    }

    /**
     * Call exit or throw an exception.
     *
     * @return never
     */
    private function doExit(): never
    {
        if (!empty($this->onExitExceptionClass)) {
            try {
                throw new $this->onExitExceptionClass();
            } catch (\Error $e) {
                Util::logError('Unable to throw exception: ' . $e->getMessage());
            }
        }
        exit;
    }

    /**
     * Add the JWT signature to an array of message parameters or to a header string.
     *
     * @param string $endpoint         URL to which message is being sent
     * @param array|string|null $data  Data to be passed
     * @param string $method           HTTP method
     * @param string|null $type        Content type of data being passed
     * @param string|null $nonce       Nonce value for JWT
     * @param int|null $timestamp      Timestamp
     *
     * @return string[]|string  Array of signed message parameters or header string
     */
    private function addJWTSignature(string $endpoint, array|string|null $data, string $method, ?string $type, ?string $nonce,
        ?int $timestamp): array|string
    {
        $ok = false;
        if (is_array($data)) {
            $ok = true;
            if (empty($nonce)) {
                $nonce = Util::getRandomString(32);
            }
            $publicKey = null;
            if (!array_key_exists('grant_type', $data)) {
                $this->messageParameters = $data;
                $payload = $this->getMessageClaims();
                $privateKey = $this->rsaKey;
                $kid = $this->kid;
                $jku = $this->jku;
                if ($this instanceof Platform) {
                    if (!empty(Tool::$defaultTool)) {
                        $publicKey = Tool::$defaultTool->rsaKey;
                    }
                    $payload['iss'] = $this->platformId;
                    $payload['aud'] = [$this->clientId];
                    $payload['azp'] = $this->clientId;
                    $payload[Util::JWT_CLAIM_PREFIX . '/claim/deployment_id'] = $this->deploymentId;
                    $payload[Util::JWT_CLAIM_PREFIX . '/claim/target_link_uri'] = $endpoint;
                    $paramName = 'id_token';
                } else {
                    if (!empty($this->platform)) {
                        $publicKey = $this->platform->rsaKey;
                        $payload['iss'] = $this->platform->clientId;
                        $payload['aud'] = [$this->platform->platformId];
                        $payload['azp'] = $this->platform->platformId;
                        $payload[Util::JWT_CLAIM_PREFIX . '/claim/deployment_id'] = $this->platform->deploymentId;
                    }
                    $paramName = 'JWT';
                }
                $payload['nonce'] = $nonce;
            } else {
                $authorizationId = '';
                if ($this instanceof Tool) {
                    $sub = '';
                    if (!empty($this->platform)) {
                        $sub = $this->platform->clientId;
                        $authorizationId = $this->platform->authorizationServerId;
                        $publicKey = $this->platform->rsaKey;
                    }
                    $privateKey = $this->rsaKey;
                    $kid = $this->kid;
                    $jku = $this->jku;
                } else {  // Tool-hosted services not yet defined in LTI
                    $sub = $this->clientId;
                    $kid = $this->kid;
                    $jku = $this->jku;
                    $privateKey = $this->rsaKey;
                    if (!empty(Tool::$defaultTool)) {
                        $publicKey = Tool::$defaultTool->rsaKey;
                    }
                }
                $payload['iss'] = $sub;
                $payload['sub'] = $sub;
                if (empty($authorizationId)) {
                    $authorizationId = $endpoint;
                }
                $payload['aud'] = [$authorizationId];
                $payload['jti'] = $nonce;
                $params = $data;
                $paramName = 'client_assertion';
            }
        }
        if ($ok) {
            if (empty($timestamp)) {
                $timestamp = time();
            }
            $payload['iat'] = $timestamp;
            $payload['exp'] = $timestamp + Jwt::$life;
            try {
                $jwt = Jwt::getJwtClient();
                $params[$paramName] = $jwt::sign($payload, $this->signatureMethod, $privateKey, $kid, $jku, $this->encryptionMethod,
                    $publicKey);
            } catch (\Exception $e) {
                $this->setReason($e->getMessage());
                $params = [];
            }

            return $params;
        } else {
            $header = '';
            if ($this instanceof Tool) {
                $platform = $this->platform;
            } else {
                $platform = $this;
            }
            $accessToken = $platform->getAccessToken();
            if (empty($accessToken)) {
                $accessToken = new AccessToken($platform);
                $platform->setAccessToken($accessToken);
            }
            if (!$accessToken->hasScope()) {  // Check token has not expired
                $accessToken->get();
            }
            if (!empty($accessToken->token)) {
                $header = "Authorization: Bearer {$accessToken->token}";
            }
            if (empty($data) && ($method !== 'DELETE')) {
                if (!empty($type)) {
                    $header .= "\nAccept: {$type}";
                }
            } elseif (isset($type)) {
                $header .= "\nContent-Type: {$type}; charset=UTF-8";
                if (!empty($data) && is_string($data)) {
                    $header .= "\nContent-Length: " . strlen($data);
                }
            }

            return $header;
        }
    }

    /**
     * Expand a claim into an array of individual fully-qualified claims.
     *
     * @param string $claim  Name of claim
     * @param mixed $value   Value of claim
     *
     * @return array  Array of individual claims and values
     */
    private static function fullyQualifyClaim(string $claim, mixed $value): array
    {
        $claims = [];
        $empty = true;
        if (is_object($value)) {
            foreach ($value as $c => $v) {
                $empty = false;
                $claims = array_merge($claims, static::fullyQualifyClaim("{$claim}/{$c}", $v));
            }
        }
        if ($empty) {
            $claims[$claim] = $value;
        }

        return $claims;
    }

    /**
     * Check a JWT claim value is a string.
     *
     * @param string $name            Name of claim
     * @param mixed $value            Value to check
     * @param bool $required          True if the claim must be present (optional, default is false)
     * @param bool $notEmpty          True if the claim must not be empty (optional, default is false)
     * @param bool $generateWarnings  True if warning messages should be generated (optional, default is false)
     *
     * @return string|null  String value (or null if not valid)
     */
    private function checkClaimString(string $name, mixed $value, bool $required = false, bool $notEmpty = false,
        bool $generateWarnings = false): ?string
    {
        if (!is_null($value)) {
            $type = gettype($value);
            if (!is_string($value) && !Util::$strictMode) {
                if ($generateWarnings) {
                    $this->warnings[] = "Value of claim '{$name}' is not a string: '{$value}'";
                    Util::setMessage(false, "The '{$name}' claim should have a string value ({$type} found)");
                }
                $value = Util::valToString($value, null);
            }
            if (!is_string($value)) {
                $this->setReason("'{$name}' claim must have a string value ({$type} found)");
                $value = null;
            } else {
                $value = trim($value);
                if ($notEmpty && empty($value)) {
                    $this->setReason("'{$name}' claim must not be empty");
                    $value = null;
                }
            }
        } elseif ($required) {
            $this->setReason("Missing '{$name}' claim");
        }

        return $value;
    }

    /**
     * Get the named string claim from JWT.
     *
     * @param string $name            Name of claim
     * @param bool $required          True if the claim must be present (optional, default is false)
     * @param bool $notEmpty          True if the claim must not be empty (optional, default is false)
     * @param bool $generateWarnings  True if warning messages should be generated (optional, default is false)
     *
     * @return string|null  Value of element (or null if not found or valid)
     */
    private function getClaimString(string $name, bool $required = false, bool $notEmpty = false, bool $generateWarnings = false): ?string
    {
        $value = $this->jwt->getClaim($name);

        return $this->checkClaimString($name, $value, $required, $notEmpty, $generateWarnings);
    }

    /**
     * Check a JWT claim value is an integer.
     *
     * @param string $name            Name of claim
     * @param mixed $value            Value to check
     * @param bool $required          True if the claim must be present (optional, default is false)
     * @param bool $generateWarnings  True if warning messages should be generated (optional, default is false)
     *
     * @return int|null  Value of element (or null if not found or valid)
     */
    private function checkClaimInteger(string $name, mixed $value, bool $required = false, bool $generateWarnings = false): ?int
    {
        if (!is_null($value)) {
            $type = gettype($value);
            if (!is_int($value) && !Util::$strictMode) {
                if ($generateWarnings) {
                    Util::setMessage(false, "The '{$name}' claim should have an integer value ({$type} found)");
                }
                $value = Util::valToNumber($value);
                if (is_float($value)) {
                    $value = intval($value);
                }
            }
            if (!is_int($value)) {
                $this->setReason("'{$name}' claim must have an integer value ({$type} found)");
                $value = null;
            }
        } elseif ($required) {
            $this->setReason("Missing '{$name}' claim");
        }

        return $value;
    }

    /**
     * Get the named integer claim from JWT.
     *
     * @param string $name            Name of claim
     * @param bool $required          True if the claim must be present (optional, default is false)
     * @param bool $notEmpty          True if the claim must not be empty (optional, default is false)
     * @param bool $generateWarnings  True if warning messages should be generated (optional, default is false)
     *
     * @return int|null  Value of element (or null if not found or valid)
     */
    private function getClaimInteger(string $name, bool $required = false, bool $generateWarnings = false): ?int
    {
        $value = $this->jwt->getClaim($name);

        return $this->checkClaimInteger($name, $value, $required, $generateWarnings);
    }

    /**
     * Check a JWT claim value is a boolean.
     *
     * @param string $name            Name of claim
     * @param mixed $value            Value to check
     * @param bool $required          True if the claim must be present (optional, default is false)
     * @param bool $generateWarnings  True if warning messages should be generated (optional, default is false)
     *
     * @return bool|null  Value of element (or null if not found or valid)
     */
    private function checkClaimBoolean(string $name, mixed $value, bool $required = false, bool $generateWarnings = false): ?bool
    {
        if (!is_null($value)) {
            $type = gettype($value);
            if (!is_bool($value) && !Util::$strictMode) {
                if ($generateWarnings) {
                    Util::setMessage(false, "The '{$name}' claim should have a boolean value ({$type} found)");
                }
                $value = Util::valToBoolean($value);
            }
            if (!is_bool($value)) {
                $this->setReason("'{$name}' claim must have a boolean value ({$type} found)");
                $value = null;
            }
        } elseif ($required) {
            $this->setReason("Missing '{$name}' claim");
        }

        return $value;
    }

    /**
     * Get the named boolean claim from JWT.
     *
     * @param string $name            Name of claim
     * @param bool $required          True if the claim must be present (optional, default is false)
     * @param bool $notEmpty          True if the claim must not be empty (optional, default is false)
     * @param bool $generateWarnings  True if warning messages should be generated (optional, default is false)
     *
     * @return bool|null  Value of element (or null if not found or valid)
     */
    private function getClaimBoolean(string $name, bool $required = false, bool $generateWarnings = false): ?bool
    {
        $value = $this->jwt->getClaim($name);

        return $this->checkClaimBoolean($name, $value, $required, $generateWarnings);
    }

    /**
     * Check a JWT claim value is an integer.
     *
     * @param string $name            Name of claim
     * @param mixed $value            Value to check
     * @param bool $required          True if the claim must be present (optional, default is false)
     * @param bool $notEmpty          True if the claim must not be empty (optional, default is false)
     * @param bool $generateWarnings  True if warning messages should be generated (optional, default is false)
     *
     * @return array|null  Value of element (or null if not found or valid)
     */
    private function checkClaimArray(string $name, mixed $value, bool $required = false, bool $notEmpty = false,
        bool $generateWarnings = false): ?array
    {
        if (!is_null($value)) {
            $type = gettype($value);
            if (!is_array($value) && !Util::$strictMode) {
                if ($generateWarnings) {
                    Util::setMessage(false, "The '{$name}' claim should have an array value ({$type} found)");
                }
                $value = Util::valToString($value, null);
                if (is_string($value)) {
                    $value = [$value];
                } else {
                    $value = null;
                }
            }
            if (!is_array($value)) {
                $this->setReason("'{$name}' claim must have an array value ({$type} found)");
                $value = null;
            } elseif ($notEmpty && empty($value)) {
                $this->setReason("'{$name}' claim must not be empty");
                $value = null;
            }
        } elseif ($required) {
            $this->setReason("Missing '{$name}' claim");
        }

        return $value;
    }

    /**
     * Get the named array claim from JWT.
     *
     * @param string $name            Name of claim
     * @param bool $required          True if the claim must be present (optional, default is false)
     * @param bool $notEmpty          True if the claim must not be empty (optional, default is false)
     * @param bool $generateWarnings  True if warning messages should be generated (optional, default is false)
     *
     * @return array|null  Value of element (or null if not found or valid)
     */
    private function getClaimArray(string $name, bool $required = false, bool $notEmpty = false, bool $generateWarnings = false): ?array
    {
        $value = $this->jwt->getClaim($name);

        return $this->checkClaimArray($name, $value, $required, $notEmpty, $generateWarnings);
    }

    /**
     * Check a JWT claim value is an object.
     *
     * @param string $name            Name of claim
     * @param mixed $value            Value to check
     * @param bool $required  True if the claim must be present (optional, default is false)
     *
     * @return object|null  Value of element (or null if not found or valid)
     */
    private function checkClaimObject(string $name, mixed $value, bool $required = false): ?object
    {
        if (!is_null($value)) {
            if (!is_object($value)) {
                $this->setReason("'{$name}' claim must be an object (" . gettype($value) . ' found)');
                $value = null;
            }
        } elseif ($required) {
            $this->setReason("Missing '{$name}' claim");
        }

        return $value;
    }

    /**
     * Get the named object claim from JWT.
     *
     * @param string $name    Name of claim
     * @param bool $required  True if the claim must be present (optional, default is false)
     *
     * @return object|null  Value of element (or null if not found or valid)
     */
    private function getClaimObject(string $name, bool $required = false): ?object
    {
        $value = $this->jwt->getClaim($name);

        return $this->checkClaimObject($name, $value, $required);
    }

}
