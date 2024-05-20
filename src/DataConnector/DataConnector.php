<?php
declare(strict_types=1);

namespace ceLTIc\LTI\DataConnector;

use ceLTIc\LTI\PlatformNonce;
use ceLTIc\LTI\Context;
use ceLTIc\LTI\ResourceLink;
use ceLTIc\LTI\ResourceLinkShare;
use ceLTIc\LTI\ResourceLinkShareKey;
use ceLTIc\LTI\Platform;
use ceLTIc\LTI\AccessToken;
use ceLTIc\LTI\UserResult;
use ceLTIc\LTI\Tool;
use ceLTIc\LTI\Util;
use ceLTIc\LTI\Enum\LogLevel;
use ceLTIc\LTI\Enum\IdScope;

/**
 * Class to provide a connection to a persistent store for LTI objects
 *
 * This class assumes no data persistence - it should be extended for specific database connections.
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class DataConnector
{

    /**
     * Default name for database table used to store platforms.
     */
    public const PLATFORM_TABLE_NAME = 'lti2_consumer';

    /**
     * Default name for database table used to store contexts.
     */
    public const CONTEXT_TABLE_NAME = 'lti2_context';

    /**
     * Default name for database table used to store resource links.
     */
    public const RESOURCE_LINK_TABLE_NAME = 'lti2_resource_link';

    /**
     * Default name for database table used to store users.
     */
    public const USER_RESULT_TABLE_NAME = 'lti2_user_result';

    /**
     * Default name for database table used to store resource link share keys.
     */
    public const RESOURCE_LINK_SHARE_KEY_TABLE_NAME = 'lti2_share_key';

    /**
     * Default name for database table used to store nonce values.
     */
    public const NONCE_TABLE_NAME = 'lti2_nonce';

    /**
     * Default name for database table used to store access token values.
     */
    public const ACCESS_TOKEN_TABLE_NAME = 'lti2_access_token';

    /**
     * Default name for database table used to store tools.
     */
    public const TOOL_TABLE_NAME = 'lti2_tool';

    /**
     * Database connection.
     *
     * @var object|resource $db
     */
    protected mixed $db = null;

    /**
     * Prefix for database table names.
     *
     * @var string $dbTableNamePrefix
     */
    protected string $dbTableNamePrefix = '';

    /**
     * SQL date format (default = 'Y-m-d')
     *
     * @var string $dateFormat
     */
    protected string $dateFormat = 'Y-m-d';

    /**
     * SQL time format (default = 'H:i:s')
     *
     * @var string $timeFormat
     */
    protected string $timeFormat = 'H:i:s';

    /**
     * memcache object.
     *
     * @var object|null $memcache
     */
    private static ?object $memcache = null;

    /**
     * Class constructor
     *
     * @param object|resource $db                 Database connection object
     * @param string          $dbTableNamePrefix  Prefix for database table names (optional, default is none)
     */
    protected function __construct(mixed $db, string $dbTableNamePrefix = '')
    {
        $this->db = $db;
        $this->dbTableNamePrefix = $dbTableNamePrefix;
    }

    /**
     * Set/check whether memcached should be used when available.
     *
     * @param string $host  Name or IP address of host running memcache server (use an empty string to disable)
     * @param int    $port  Port number used by memcache server (use -1 for default)
     *
     * @return bool  True if memcache is enabled
     */
    public static function useMemcache(string $host = null, int $port = -1): bool
    {
        if (is_null($host)) {
            $useMemcache = !empty(self::$memcache);
        } else {
            $useMemcache = !empty($host);
            if ($useMemcache) {
                if (!class_exists('Memcache')) {
                    $useMemcache = false;
                    Util::logError("Memcache extension not installed");
                } else {
                    self::$memcache = new \Memcache();
                    if ($port < 0) {
                        $useMemcache = self::$memcache->connect($host);
                    } else {
                        $useMemcache = self::$memcache->connect($host, $port);
                    }
                    if (!$useMemcache) {
                        self::$memcache = null;
                    }
                    if (!$useMemcache) {
                        if ($port < 0) {
                            Util::logError("Unable to connect to memcache at {$host}");
                        } else {
                            Util::logError("Unable to connect to memcache at {$host}:{$port}");
                        }
                    }
                }
            }
            if (!$useMemcache) {
                self::$memcache = null;
            }
        }

        return $useMemcache;
    }

###
###  Platform methods
###

    /**
     * Load platform object.
     *
     * @param Platform $platform  Platform object
     *
     * @return bool  True if the platform object was successfully loaded
     */
    public function loadPlatform(Platform $platform): bool
    {
        $platform->secret = 'secret';
        $platform->enabled = true;
        $now = time();
        $platform->created = $now;
        $platform->updated = $now;

        return true;
    }

    /**
     * Save platform object.
     *
     * @param Platform $platform  Platform object
     *
     * @return bool  True if the platform object was successfully saved
     */
    public function savePlatform(Platform $platform): bool
    {
        $platform->updated = time();

        return true;
    }

    /**
     * Delete platform object.
     *
     * @param Platform $platform  Platform object
     *
     * @return bool  True if the platform object was successfully deleted
     */
    public function deletePlatform(Platform $platform): bool
    {
        $platform->initialize();

        return true;
    }

    /**
     * Load platform objects.
     *
     * @return Platform[]  Array of all defined Platform objects
     */
    public function getPlatforms(): array
    {
        return [];
    }

###
###  Context methods
###

    /**
     * Load context object.
     *
     * @param Context $context  Context object
     *
     * @return bool  True if the context object was successfully loaded
     */
    public function loadContext(Context $context): bool
    {
        $now = time();
        $context->created = $now;
        $context->updated = $now;

        return true;
    }

    /**
     * Save context object.
     *
     * @param Context $context  Context object
     *
     * @return bool  True if the context object was successfully saved
     */
    public function saveContext(Context $context): bool
    {
        $context->updated = time();

        return true;
    }

    /**
     * Delete context object.
     *
     * @param Context $context  Context object
     *
     * @return bool  True if the Context object was successfully deleted
     */
    public function deleteContext(Context $context): bool
    {
        $context->initialize();

        return true;
    }

###
###  ResourceLink methods
###

    /**
     * Load resource link object.
     *
     * @param ResourceLink $resourceLink  ResourceLink object
     *
     * @return bool  True if the resource link object was successfully loaded
     */
    public function loadResourceLink(ResourceLink $resourceLink): bool
    {
        $now = time();
        $resourceLink->created = $now;
        $resourceLink->updated = $now;

        return true;
    }

    /**
     * Save resource link object.
     *
     * @param ResourceLink $resourceLink  ResourceLink object
     *
     * @return bool  True if the resource link object was successfully saved
     */
    public function saveResourceLink(ResourceLink $resourceLink): bool
    {
        $resourceLink->updated = time();

        return true;
    }

    /**
     * Delete resource link object.
     *
     * @param ResourceLink $resourceLink  ResourceLink object
     *
     * @return bool  True if the resource link object was successfully deleted
     */
    public function deleteResourceLink(ResourceLink $resourceLink): bool
    {
        $resourceLink->initialize();

        return true;
    }

    /**
     * Get array of user objects.
     *
     * Obtain an array of UserResult objects for users with a result sourcedId.  The array may include users from other
     * resource links which are sharing this resource link.  It may also be optionally indexed by the user ID of a specified scope.
     *
     * @param ResourceLink $resourceLink  Resource link object
     * @param bool         $localOnly     True if only users within the resource link are to be returned (excluding users sharing this resource link)
     * @param IdScope|null $idScope       Scope value to use for user IDs
     *
     * @return UserResult[]  Array of UserResult objects
     */
    public function getUserResultSourcedIDsResourceLink(ResourceLink $resourceLink, bool $localOnly, ?IdScope $idScope): array
    {
        return [];
    }

    /**
     * Get array of shares defined for this resource link.
     *
     * @param ResourceLink $resourceLink  ResourceLink object
     *
     * @return ResourceLinkShare[]  Array of ResourceLinkShare objects
     */
    public function getSharesResourceLink(ResourceLink $resourceLink): array
    {
        return [];
    }

###
###  PlatformNonce methods
###

    /**
     * Load nonce object.
     *
     * @param PlatformNonce $nonce  Nonce object
     *
     * @return bool  True if the nonce object was successfully loaded
     */
    public function loadPlatformNonce(PlatformNonce $nonce): bool
    {
        $ok = false;  // assume the nonce does not already exist
        if (!empty(self::$memcache)) {
            $id = $nonce->getPlatform()->getRecordId();
            $value = $nonce->getValue();
            $name = self::NONCE_TABLE_NAME . "_{$id}_{$value}";
            $ok = self::$memcache->get($name) !== false;
        }

        return $ok;
    }

    /**
     * Save nonce object.
     *
     * @param PlatformNonce $nonce  Nonce object
     *
     * @return bool  True if the nonce object was successfully saved
     */
    public function savePlatformNonce(PlatformNonce $nonce): bool
    {
        $ok = true;  // assume the nonce was saved
        if (!empty(self::$memcache)) {
            $ok = false;
            $id = $nonce->getPlatform()->getRecordId();
            $value = $nonce->getValue();
            $expires = $nonce->expires;
            $name = self::NONCE_TABLE_NAME . "_{$id}_{$value}";
            $current = self::$memcache->get($name);
            if ($current === false) {
                $ok = self::$memcache->set($name, true, 0, $expires);
            }
        }

        return $ok;
    }

    /**
     * Delete nonce object.
     *
     * @param PlatformNonce $nonce  Nonce object
     *
     * @return bool  True if the nonce object was successfully deleted
     */
    public function deletePlatformNonce(PlatformNonce $nonce): bool
    {
        $ok = true;  // assume the nonce was deleted
        if (!empty(self::$memcache)) {
            $id = $nonce->getPlatform()->getRecordId();
            $value = $nonce->getValue();
            $name = self::NONCE_TABLE_NAME . "_{$id}_{$value}";
            $ok = self::$memcache->get($name);
            if ($ok !== false) {
                $ok = self::$memcache->delete($name);
            }
        }

        return $ok;
    }

###
###  AccessToken methods
###

    /**
     * Load access token object.
     *
     * @param AccessToken $accessToken  Access token object
     *
     * @return bool  True if the nonce object was successfully loaded
     */
    public function loadAccessToken(AccessToken $accessToken): bool
    {
        $ok = false;  // assume the access token does not already exist
        if (!empty(self::$memcache)) {
            $id = $accessToken->getPlatform()->getRecordId();
            $value = $accessToken->token;
            $name = self::ACCESS_TOKEN_TABLE_NAME . "_{$id}_{$value}";
            $current = self::$memcache->get($name);
            $ok = is_array($current);
            if ($ok) {
                $accessToken->scopes = $current['scopes'];
                $accessToken->token = $current['token'];
                $accessToken->expires = $current['expires'];
                $accessToken->created = $current['created'];
                $accessToken->updated = $current['updated'];
            }
        }

        return $ok;
    }

    /**
     * Save access token object.
     *
     * @param AccessToken $accessToken  Access token object
     *
     * @return bool  True if the access token object was successfully saved
     */
    public function saveAccessToken(AccessToken $accessToken): bool
    {
        $ok = true;  // assume the access token was saved
        if (!empty(self::$memcache)) {
            $ok = false;
            $id = $accessToken->getPlatform()->getRecordId();
            $value = $accessToken->token;
            $expires = $accessToken->expires;
            $name = self::ACCESS_TOKEN_TABLE_NAME . "_{$id}_{$value}";
            $current = self::$memcache->get($name);
            if ($current === false) {
                $current = [
                    'scopes' => $accessToken->scopes,
                    'token' => $value,
                    'expires' => $expires,
                    'created' => $accessToken->created,
                    'updated' => $accessToken->updated
                ];
                $ok = self::$memcache->set($name, $current, 0, $expires);
            }
        }

        return $ok;
    }

###
###  ResourceLinkShareKey methods
###

    /**
     * Load resource link share key object.
     *
     * @param ResourceLinkShareKey $shareKey  ResourceLink share key object
     *
     * @return bool  True if the resource link share key object was successfully loaded
     */
    public function loadResourceLinkShareKey(ResourceLinkShareKey $shareKey): bool
    {
        return true;
    }

    /**
     * Save resource link share key object.
     *
     * @param ResourceLinkShareKey $shareKey  Resource link share key object
     *
     * @return bool  True if the resource link share key object was successfully saved
     */
    public function saveResourceLinkShareKey(ResourceLinkShareKey $shareKey): bool
    {
        return true;
    }

    /**
     * Delete resource link share key object.
     *
     * @param ResourceLinkShareKey $shareKey  Resource link share key object
     *
     * @return bool  True if the resource link share key object was successfully deleted
     */
    public function deleteResourceLinkShareKey(ResourceLinkShareKey $shareKey): bool
    {
        return true;
    }

###
###  UserResult methods
###

    /**
     * Load user object.
     *
     * @param UserResult $userResult  UserResult object
     *
     * @return bool  True if the user object was successfully loaded
     */
    public function loadUserResult(UserResult $userResult): bool
    {
        $now = time();
        $userResult->created = $now;
        $userResult->updated = $now;

        return true;
    }

    /**
     * Save user object.
     *
     * @param UserResult $userResult  UserResult object
     *
     * @return bool  True if the user object was successfully saved
     */
    public function saveUserResult(UserResult $userResult): bool
    {
        $userResult->updated = time();

        return true;
    }

    /**
     * Delete user object.
     *
     * @param UserResult $userResult  UserResult object
     *
     * @return bool  True if the user object was successfully deleted
     */
    public function deleteUserResult(UserResult $userResult): bool
    {
        $userResult->initialize();

        return true;
    }

###
###  Tool methods
###

    /**
     * Load tool object.
     *
     * @param Tool $tool  Tool object
     *
     * @return bool  True if the tool object was successfully loaded
     */
    public function loadTool(Tool $tool): bool
    {
        $tool->secret = 'secret';
        $tool->enabled = true;
        $now = time();
        $tool->created = $now;
        $tool->updated = $now;

        return true;
    }

    /**
     * Save tool object.
     *
     * @param Tool $tool  Tool object
     *
     * @return bool  True if the tool object was successfully saved
     */
    public function saveTool(Tool $tool): bool
    {
        $tool->updated = time();

        return true;
    }

    /**
     * Delete tool object.
     *
     * @param Tool $tool  Tool object
     *
     * @return bool  True if the tool object was successfully deleted
     */
    public function deleteTool(Tool $tool): bool
    {
        $tool->initialize();

        return true;
    }

    /**
     * Load tool objects.
     *
     * @return Tool[]  Array of all defined Tool objects
     */
    public function getTools(): array
    {
        return [];
    }

###
###  Other methods
###

    /**
     * Create data connector object.
     *
     * A data connector provides access to persistent storage for the different objects.
     *
     * Names of tables may be given a prefix to allow multiple versions to share the same schema.  A separate sub-class is defined for
     * each different database connection - the class to use is determined by inspecting the database object passed, but this can be overridden
     * (for example, to use a bespoke connector) by specifying a type.  If no database is passed then this class is used which acts as a dummy
     * connector with no persistence.
     *
     * @param object|resource $db                 A database connection object or string (optional, default is no persistence)
     * @param string          $dbTableNamePrefix  Prefix for database table names (optional, default is none)
     * @param string          $type               The type of data connector (optional, default is based on $db parameter)
     *
     * @return DataConnector Data connector object
     */
    public static function getDataConnector(mixed $db = null, string $dbTableNamePrefix = '', string $type = ''): DataConnector
    {
        if (is_null($dbTableNamePrefix)) {
            $dbTableNamePrefix = '';
        }
        if (!is_null($db) && empty($type)) {
            if (is_object($db)) {
                $type = explode('\\', get_class($db), 2)[0];
            } elseif (is_resource($db)) {
                $type = strtok(get_resource_type($db), ' ');
            }
        }
        $type = strtolower($type);
        if ($type === 'pdo') {
            $type .= '_' . $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        }
        if (!empty($type)) {
            $type = "DataConnector_{$type}";
        } else {
            $type = 'DataConnector';
        }
        $type = "\\ceLTIc\\LTI\\DataConnector\\{$type}";
        $dataConnector = new $type($db, $dbTableNamePrefix);

        return $dataConnector;
    }

    /**
     * Adjust the settings for any platform properties being stored as a setting value.
     *
     * @param Platform $platform  Platform object
     * @param bool     $isSave    True if the settings are being saved
     *
     * @return void
     */
    protected function fixPlatformSettings(Platform $platform, bool $isSave): void
    {
        if (!$isSave) {
            $platform->authorizationServerId = $platform->getSetting('_authorization_server_id', $platform->authorizationServerId);
            $platform->setSetting('_authorization_server_id');
            $platform->authenticationUrl = $platform->getSetting('_authentication_request_url', $platform->authenticationUrl);
            $platform->setSetting('_authentication_request_url');
            $platform->accessTokenUrl = $platform->getSetting('_oauth2_access_token_url', $platform->accessTokenUrl);
            $platform->setSetting('_oauth2_access_token_url');
            $platform->jku = $platform->getSetting('_jku', $platform->jku);
            $platform->setSetting('_jku');
            $platform->encryptionMethod = $platform->getSetting('_encryption_method', $platform->encryptionMethod);
            $platform->setSetting('_encryption_method');
            $platform->debugMode = $platform->getSetting('_debug', $platform->debugMode ? 'true' : 'false') === 'true';
            $platform->setSetting('_debug');
            if ($platform->debugMode) {
                Util::$logLevel = LogLevel::Debug;
            }
        } else {
            $platform->setSetting('_authorization_server_id',
                !empty($platform->authorizationServerId) ? $platform->authorizationServerId : null);
            $platform->setSetting('_authentication_request_url',
                !empty($platform->authenticationUrl) ? $platform->authenticationUrl : null);
            $platform->setSetting('_oauth2_access_token_url', !empty($platform->accessTokenUrl) ? $platform->accessTokenUrl : null);
            $platform->setSetting('_jku', !empty($platform->jku) ? $platform->jku : null);
            $platform->setSetting('_encryption_method', !empty($platform->encryptionMethod) ? $platform->encryptionMethod : null);
            $platform->setSetting('_debug', $platform->debugMode ? 'true' : null);
        }
    }

    /**
     * Adjust the settings for any tool properties being stored as a setting value.
     *
     * @param Tool $tool    Tool object
     * @param bool $isSave  True if the settings are being saved
     *
     * @return void
     */
    protected function fixToolSettings(Tool $tool, bool $isSave): void
    {
        if (!$isSave) {
            $tool->encryptionMethod = $tool->getSetting('_encryption_method', $tool->encryptionMethod);
            $tool->setSetting('_encryption_method');
            $tool->debugMode = $tool->getSetting('_debug', $tool->debugMode ? 'true' : 'false') === 'true';
            $tool->setSetting('_debug');
            if ($tool->debugMode) {
                Util::$logLevel = LogLevel::Debug;
            }
        } else {
            $tool->setSetting('_encryption_method', !empty($tool->encryptionMethod) ? $tool->encryptionMethod : null);
            $tool->setSetting('_debug', $tool->debugMode ? 'true' : null);
        }
    }

    /**
     * Add the prefix to the name for a database table.
     *
     * @param string $table  Name of table without prefix
     *
     * @return string  The fullname of the database table
     */
    protected function dbTableName(string $table)
    {
        return "{$this->dbTableNamePrefix}{$table}";
    }

}
