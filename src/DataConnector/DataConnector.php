<?php

namespace ceLTIc\LTI\DataConnector;

use ceLTIc\LTI;
use ceLTIc\LTI\ConsumerNonce;
use ceLTIc\LTI\Context;
use ceLTIc\LTI\ResourceLink;
use ceLTIc\LTI\ResourceLinkShare;
use ceLTIc\LTI\ResourceLinkShareKey;
use ceLTIc\LTI\ToolConsumer;
use ceLTIc\LTI\UserResult;

/**
 * Class to provide a connection to a persistent store for LTI objects
 *
 * This class assumes no data persistence - it should be extended for specific database connections.
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @version  3.1.0
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class DataConnector
{

    /**
     * Default name for database table used to store tool consumers.
     */
    const CONSUMER_TABLE_NAME = 'lti2_consumer';

    /**
     * Default name for database table used to store contexts.
     */
    const CONTEXT_TABLE_NAME = 'lti2_context';

    /**
     * Default name for database table used to store resource links.
     */
    const RESOURCE_LINK_TABLE_NAME = 'lti2_resource_link';

    /**
     * Default name for database table used to store users.
     */
    const USER_RESULT_TABLE_NAME = 'lti2_user_result';

    /**
     * Default name for database table used to store resource link share keys.
     */
    const RESOURCE_LINK_SHARE_KEY_TABLE_NAME = 'lti2_share_key';

    /**
     * Default name for database table used to store nonce values.
     */
    const NONCE_TABLE_NAME = 'lti2_nonce';

    /**
     * Database connection.
     *
     * @var object|resource $db
     */
    protected $db = null;

    /**
     * Prefix for database table names.
     *
     * @var string $dbTableNamePrefix
     */
    protected $dbTableNamePrefix = '';

    /**
     * SQL date format (default = 'Y-m-d')
     *
     * @var string $dateFormat
     */
    protected $dateFormat = 'Y-m-d';

    /**
     * SQL time format (default = 'H:i:s')
     *
     * @var string $timeFormat
     */
    protected $timeFormat = 'H:i:s';

    /**
     * Class constructor
     *
     * @param object|resource $db                 Database connection object
     * @param string $dbTableNamePrefix  Prefix for database table names (optional, default is none)
     */
    public function __construct($db, $dbTableNamePrefix = '')
    {
        $this->db = $db;
        $this->dbTableNamePrefix = $dbTableNamePrefix;
    }

###
###  ToolConsumer methods
###

    /**
     * Load tool consumer object.
     *
     * @param ToolConsumer $consumer ToolConsumer object
     *
     * @return bool    True if the tool consumer object was successfully loaded
     */
    public function loadToolConsumer($consumer)
    {
        $consumer->secret = 'secret';
        $consumer->enabled = true;
        $now = time();
        $consumer->created = $now;
        $consumer->updated = $now;

        return true;
    }

    /**
     * Save tool consumer object.
     *
     * @param ToolConsumer $consumer Consumer object
     *
     * @return bool    True if the tool consumer object was successfully saved
     */
    public function saveToolConsumer($consumer)
    {
        $consumer->updated = time();

        return true;
    }

    /**
     * Delete tool consumer object.
     *
     * @param ToolConsumer $consumer Consumer object
     *
     * @return bool    True if the tool consumer object was successfully deleted
     */
    public function deleteToolConsumer($consumer)
    {
        $consumer->initialize();

        return true;
    }

    /**
     * Load tool consumer objects.
     *
     * @return ToolConsumer[] Array of all defined ToolConsumer objects
     */
    public function getToolConsumers()
    {
        return array();
    }

###
###  Context methods
###

    /**
     * Load context object.
     *
     * @param Context $context Context object
     *
     * @return bool    True if the context object was successfully loaded
     */
    public function loadContext($context)
    {
        $now = time();
        $context->created = $now;
        $context->updated = $now;

        return true;
    }

    /**
     * Save context object.
     *
     * @param Context $context Context object
     *
     * @return bool    True if the context object was successfully saved
     */
    public function saveContext($context)
    {
        $context->updated = time();

        return true;
    }

    /**
     * Delete context object.
     *
     * @param Context $context Context object
     *
     * @return bool    True if the Context object was successfully deleted
     */
    public function deleteContext($context)
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
     * @param ResourceLink $resourceLink ResourceLink object
     *
     * @return bool    True if the resource link object was successfully loaded
     */
    public function loadResourceLink($resourceLink)
    {
        $now = time();
        $resourceLink->created = $now;
        $resourceLink->updated = $now;

        return true;
    }

    /**
     * Save resource link object.
     *
     * @param ResourceLink $resourceLink ResourceLink object
     *
     * @return bool    True if the resource link object was successfully saved
     */
    public function saveResourceLink($resourceLink)
    {
        $resourceLink->updated = time();

        return true;
    }

    /**
     * Delete resource link object.
     *
     * @param ResourceLink $resourceLink ResourceLink object
     *
     * @return bool    True if the resource link object was successfully deleted
     */
    public function deleteResourceLink($resourceLink)
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
     * @param ResourceLink $resourceLink      Resource link object
     * @param bool         $localOnly True if only users within the resource link are to be returned (excluding users sharing this resource link)
     * @param int          $idScope     Scope value to use for user IDs
     *
     * @return UserResult[] Array of UserResult objects
     */
    public function getUserResultSourcedIDsResourceLink($resourceLink, $localOnly, $idScope)
    {
        return array();
    }

    /**
     * Get array of shares defined for this resource link.
     *
     * @param ResourceLink $resourceLink ResourceLink object
     *
     * @return ResourceLinkShare[] Array of ResourceLinkShare objects
     */
    public function getSharesResourceLink($resourceLink)
    {
        return array();
    }

###
###  ConsumerNonce methods
###

    /**
     * Load nonce object.
     *
     * @param ConsumerNonce $nonce Nonce object
     *
     * @return bool    True if the nonce object was successfully loaded
     */
    public function loadConsumerNonce($nonce)
    {
        return false;  // assume the nonce does not already exist
    }

    /**
     * Save nonce object.
     *
     * @param ConsumerNonce $nonce Nonce object
     *
     * @return bool    True if the nonce object was successfully saved
     */
    public function saveConsumerNonce($nonce)
    {
        return true;
    }

###
###  ResourceLinkShareKey methods
###

    /**
     * Load resource link share key object.
     *
     * @param ResourceLinkShareKey $shareKey ResourceLink share key object
     *
     * @return bool    True if the resource link share key object was successfully loaded
     */
    public function loadResourceLinkShareKey($shareKey)
    {
        return true;
    }

    /**
     * Save resource link share key object.
     *
     * @param ResourceLinkShareKey $shareKey Resource link share key object
     *
     * @return bool    True if the resource link share key object was successfully saved
     */
    public function saveResourceLinkShareKey($shareKey)
    {
        return true;
    }

    /**
     * Delete resource link share key object.
     *
     * @param ResourceLinkShareKey $shareKey Resource link share key object
     *
     * @return bool    True if the resource link share key object was successfully deleted
     */
    public function deleteResourceLinkShareKey($shareKey)
    {
        return true;
    }

###
###  UserResult methods
###

    /**
     * Load user object.
     *
     * @param UserResult $userresult UserResult object
     *
     * @return bool    True if the user object was successfully loaded
     */
    public function loadUserResult($userresult)
    {
        $now = time();
        $userresult->created = $now;
        $userresult->updated = $now;

        return true;
    }

    /**
     * Save user object.
     *
     * @param UserResult $userresult UserResult object
     *
     * @return bool    True if the user object was successfully saved
     */
    public function saveUserResult($userresult)
    {
        $userresult->updated = time();

        return true;
    }

    /**
     * Delete user object.
     *
     * @param UserResult $userresult UserResult object
     *
     * @return bool    True if the user object was successfully deleted
     */
    public function deleteUserResult($userresult)
    {
        $userresult->initialize();

        return true;
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
     * @param object|resource  $db                 A database connection object or string (optional, default is no persistence)
     * @param string           $dbTableNamePrefix  Prefix for database table names (optional, default is none)
     * @param string           $type               The type of data connector (optional, default is based on $db parameter)
     *
     * @return DataConnector Data connector object
     */
    public static function getDataConnector($db = null, $dbTableNamePrefix = '', $type = '')
    {
        if (is_null($dbTableNamePrefix)) {
            $dbTableNamePrefix = '';
        }
        if (!is_null($db) && empty($type)) {
            if (is_object($db)) {
                $type = get_class($db);
            } elseif (is_resource($db)) {
                $type = strtok(get_resource_type($db), ' ');
            }
        }
        $type = strtolower($type);
        if ($type === 'pdo') {
            if ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'pgsql') {
                $type .= '_pgsql';
            } elseif ($db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'oci') {
                $type .= '_oci';
            }
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
     * Generate a random string.
     *
     * The generated string will only comprise letters (upper- and lower-case) and digits.
     *
     * @param int $length Length of string to be generated (optional, default is 8 characters)
     *
     * @return string Random string
     */
    public static function getRandomString($length = 8)
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

        $value = '';
        $charsLength = strlen($chars) - 1;

        for ($i = 1; $i <= $length; $i++) {
            $value .= $chars[rand(0, $charsLength)];
        }

        return $value;
    }

    /**
     * Escape a string for use in a database query.
     *
     * Any single quotes in the value passed will be replaced with two single quotes.  If a null value is passed, a string
     * of 'null' is returned (which will never be enclosed in quotes irrespective of the value of the $addQuotes parameter.
     *
     * @param string $value     Value to be escaped
     * @param bool $addQuotes If true the returned string will be enclosed in single quotes (optional, default is true)
     *
     * @return string The escaped string.
     */
    public function escape($value, $addQuotes = true)
    {
        return static::quoted($value, $addQuotes);
    }

    /**
     * Quote a string for use in a database query.
     *
     * Any single quotes in the value passed will be replaced with two single quotes.  If a null value is passed, a string
     * of 'null' is returned (which will never be enclosed in quotes irrespective of the value of the $addQuotes parameter.
     *
     * @param string $value     Value to be quoted
     * @param bool $addQuotes If true the returned string will be enclosed in single quotes (optional, default is true)
     *
     * @return string The quoted string.
     */
    public static function quoted($value, $addQuotes = true)
    {
        if (is_null($value)) {
            $value = 'null';
        } else {
            $value = str_replace('\'', '\'\'', $value);
            if ($addQuotes) {
                $value = "'{$value}'";
            }
        }

        return $value;
    }

    /**
     * Return a hash of a consumer key for values longer than 255 characters.
     *
     * @param string $key
     * @return string
     */
    protected static function getConsumerKey($key)
    {
        $len = strlen($key);
        if ($len > 255) {
            $key = 'sha512:' . hash('sha512', $key);
        }

        return $key;
    }

}
