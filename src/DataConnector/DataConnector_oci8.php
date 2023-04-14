<?php
declare(strict_types=1);

namespace ceLTIc\LTI\DataConnector;

use ceLTIc\LTI;
use ceLTIc\LTI\PlatformNonce;
use ceLTIc\LTI\Context;
use ceLTIc\LTI\ResourceLink;
use ceLTIc\LTI\ResourceLinkShare;
use ceLTIc\LTI\ResourceLinkShareKey;
use ceLTIc\LTI\Platform;
use ceLTIc\LTI\UserResult;
use ceLTIc\LTI\Tool;
use ceLTIc\LTI\AccessToken;
use ceLTIc\LTI\Util;
use ceLTIc\LTI\Enum\IdScope;

/**
 * Class to represent an LTI Data Connector for Oracle connections
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
###
#    NB This class assumes that an Oracle connection has already been opened to the appropriate schema
###

class DataConnector_oci8 extends DataConnector
{

    /**
     * Class constructor
     *
     * @param mixed $db                  Database connection resource
     * @param string $dbTableNamePrefix  Prefix for database table names (optional, default is none)
     */
    public function __construct(mixed $db, string $dbTableNamePrefix = '')
    {
        parent::__construct($db, $dbTableNamePrefix);
        $this->dateFormat = 'd-M-Y';
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
        $allowMultiple = false;
        if (!is_null($platform->getRecordId())) {
            $sql = <<< EOD
SELECT consumer_pk, name, consumer_key, secret, platform_id, client_id, deployment_id, public_key,
  lti_version, signature_method, consumer_name, consumer_version, consumer_guid, profile, tool_proxy, settings,
  protected, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::PLATFORM_TABLE_NAME)}
WHERE (consumer_pk = :id)
EOD;
            $query = oci_parse($this->db, $sql);
            $id = $platform->getRecordId();
            oci_bind_by_name($query, 'id', $id);
        } elseif (!empty($platform->platformId)) {
            if (empty($platform->clientId)) {
                $allowMultiple = true;
                $sql = <<< EOD
SELECT consumer_pk, name, consumer_key, secret, platform_id, client_id, deployment_id, public_key,
  lti_version, signature_method, consumer_name, consumer_version, consumer_guid, profile, tool_proxy, settings,
  protected, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::PLATFORM_TABLE_NAME)}
WHERE (platform_id = :platform_id)
EOD;
                $query = oci_parse($this->db, $sql);
                oci_bind_by_name($query, 'platform_id', $platform->platformId);
            } elseif (empty($platform->deploymentId)) {
                $allowMultiple = true;
                $sql = <<< EOD
SELECT consumer_pk, name, consumer_key, secret, platform_id, client_id, deployment_id, public_key,
  lti_version, signature_method, consumer_name, consumer_version, consumer_guid, profile, tool_proxy, settings,
  protected, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::PLATFORM_TABLE_NAME)}
WHERE (platform_id = :platform_id) AND (client_id = :client_id)
EOD;
                $query = oci_parse($this->db, $sql);
                oci_bind_by_name($query, 'platform_id', $platform->platformId);
                oci_bind_by_name($query, 'client_id', $platform->clientId);
            } else {
                $sql = <<< EOD
SELECT consumer_pk, name, consumer_key, secret, platform_id, client_id, deployment_id, public_key,
  lti_version, signature_method, consumer_name, consumer_version, consumer_guid, profile, tool_proxy, settings,
  protected, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::PLATFORM_TABLE_NAME)}
WHERE (platform_id = :platform_id) AND (client_id = :client_id) AND (deployment_id = :deployment_id)
EOD;
                $query = oci_parse($this->db, $sql);
                oci_bind_by_name($query, 'platform_id', $platform->platformId);
                oci_bind_by_name($query, 'client_id', $platform->clientId);
                oci_bind_by_name($query, 'deployment_id', $platform->deploymentId);
            }
        } else {
            $sql = <<< EOD
SELECT consumer_pk, name, consumer_key, secret, platform_id, client_id, deployment_id, public_key,
  lti_version, signature_method, consumer_name, consumer_version, consumer_guid, profile, tool_proxy, settings,
  protected, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::PLATFORM_TABLE_NAME)}
WHERE (consumer_key = :key)
EOD;
            $query = oci_parse($this->db, $sql);
            $consumerKey = $platform->getKey();
            oci_bind_by_name($query, 'key', $consumerKey);
        }
        $ok = $this->executeQuery($sql, $query);
        if ($ok) {
            $row = oci_fetch_assoc($query);
            $ok = ($row !== false) && ($allowMultiple || !oci_fetch_assoc($query));
        }
        if ($ok) {
            $row = array_change_key_case($row);
            $platform->setRecordId(intval($row['consumer_pk']));
            $platform->name = $row['name'];
            $platform->setkey($row['consumer_key']);
            $platform->secret = $row['secret'];
            $platform->platformId = $row['platform_id'];
            $platform->clientId = $row['client_id'];
            $platform->deploymentId = $row['deployment_id'];
            $platform->rsaKey = $row['public_key'];
            $platform->ltiVersion = $row['lti_version'];
            $platform->signatureMethod = $row['signature_method'];
            $platform->consumerName = $row['consumer_name'];
            $platform->consumerVersion = $row['consumer_version'];
            $platform->consumerGuid = $row['consumer_guid'];
            $platform->profile = Util::json_decode($row['profile']);
            $platform->toolProxy = $row['tool_proxy'];
            $settingsValue = $row['settings']->load();
            if (is_string($settingsValue)) {
                $settings = Util::json_decode($settingsValue, true);
                if (!is_array($settings)) {
                    $settings = @unserialize($settingsValue);  // check for old serialized setting
                }
                if (!is_array($settings)) {
                    $settings = [];
                }
            } else {
                $settings = [];
            }
            $platform->setSettings($settings);
            $platform->protected = (intval($row['protected']) === 1);
            $platform->enabled = (intval($row['enabled']) === 1);
            $platform->enableFrom = null;
            if (!is_null($row['enable_from'])) {
                $platform->enableFrom = strtotime($row['enable_from']);
            }
            $platform->enableUntil = null;
            if (!is_null($row['enable_until'])) {
                $platform->enableUntil = strtotime($row['enable_until']);
            }
            $platform->lastAccess = null;
            if (!is_null($row['last_access'])) {
                $platform->lastAccess = strtotime($row['last_access']);
            }
            $platform->created = strtotime($row['created']);
            $platform->updated = strtotime($row['updated']);
            $this->fixPlatformSettings($platform, false);
        }

        return $ok;
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
        $id = $platform->getRecordId();
        $consumerKey = $platform->getKey();
        $protected = ($platform->protected) ? 1 : 0;
        $enabled = ($platform->enabled) ? 1 : 0;
        $profile = (!empty($platform->profile)) ? json_encode($platform->profile) : null;
        $this->fixPlatformSettings($platform, true);
        $settingsValue = json_encode($platform->getSettings());
        $this->fixPlatformSettings($platform, false);
        $time = time();
        $now = date("{$this->dateFormat} {$this->timeFormat}", $time);
        $from = null;
        if (!is_null($platform->enableFrom)) {
            $from = date("{$this->dateFormat} {$this->timeFormat}", $platform->enableFrom);
        }
        $until = null;
        if (!is_null($platform->enableUntil)) {
            $until = date("{$this->dateFormat} {$this->timeFormat}", $platform->enableUntil);
        }
        $last = null;
        if (!is_null($platform->lastAccess)) {
            $last = date($this->dateFormat, $platform->lastAccess);
        }
        if (empty($id)) {
            $pk = null;
            $sql = <<< EOD
INSERT INTO {$this->dbTableName(static::PLATFORM_TABLE_NAME)} (
  consumer_key, name, secret, platform_id, client_id, deployment_id, public_key,
  lti_version, signature_method, consumer_name, consumer_version, consumer_guid, profile, tool_proxy, settings,
  protected, enabled, enable_from, enable_until, last_access, created, updated)
VALUES (:key, :name, :secret, :platform_id, :client_id, :deployment_id, :public_key,
  :lti_version, :signature_method, :consumer_name, :consumer_version, :consumer_guid, :profile, :tool_proxy, :settings,
  :protected, :enabled, :enable_from, :enable_until, :last_access, :created, :updated)
RETURNING consumer_pk INTO :pk
EOD;
            $query = oci_parse($this->db, $sql);
            oci_bind_by_name($query, 'key', $consumerKey);
            oci_bind_by_name($query, 'name', $platform->name);
            oci_bind_by_name($query, 'secret', $platform->secret);
            oci_bind_by_name($query, 'platform_id', $platform->platformId);
            oci_bind_by_name($query, 'client_id', $platform->clientId);
            oci_bind_by_name($query, 'deployment_id', $platform->deploymentId);
            oci_bind_by_name($query, 'public_key', $platform->rsaKey);
            oci_bind_by_name($query, 'lti_version', $platform->ltiVersion);
            oci_bind_by_name($query, 'signature_method', $platform->signatureMethod);
            oci_bind_by_name($query, 'consumer_name', $platform->consumerName);
            oci_bind_by_name($query, 'consumer_version', $platform->consumerVersion);
            oci_bind_by_name($query, 'consumer_guid', $platform->consumerGuid);
            oci_bind_by_name($query, 'profile', $profile);
            oci_bind_by_name($query, 'tool_proxy', $platform->toolProxy);
            oci_bind_by_name($query, 'settings', $settingsValue);
            oci_bind_by_name($query, 'protected', $protected);
            oci_bind_by_name($query, 'enabled', $enabled);
            oci_bind_by_name($query, 'enable_from', $from);
            oci_bind_by_name($query, 'enable_until', $until);
            oci_bind_by_name($query, 'last_access', $last);
            oci_bind_by_name($query, 'created', $now);
            oci_bind_by_name($query, 'updated', $now);
            oci_bind_by_name($query, 'pk', $pk);
        } else {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::PLATFORM_TABLE_NAME)}
SET consumer_key = :key, name = :name, secret = :secret, platform_id = :platform_id, client_id = :client_id, deployment_id = :deployment_id,
  public_key = :public_key, lti_version = :lti_version, signature_method = :signature_method,
  consumer_name = :consumer_name, consumer_version = :consumer_version, consumer_guid = :consumer_guid,
  profile = :profile, tool_proxy = :tool_proxy, settings = :settings,
  protected = :protected, enabled = :enabled, enable_from = :enable_from, enable_until = :enable_until, last_access = :last_access, updated = :updated
WHERE (consumer_pk = :id)
EOD;
            $query = oci_parse($this->db, $sql);
            oci_bind_by_name($query, 'key', $consumerKey);
            oci_bind_by_name($query, 'name', $platform->name);
            oci_bind_by_name($query, 'secret', $platform->secret);
            oci_bind_by_name($query, 'platform_id', $platform->platformId);
            oci_bind_by_name($query, 'client_id', $platform->clientId);
            oci_bind_by_name($query, 'deployment_id', $platform->deploymentId);
            oci_bind_by_name($query, 'public_key', $platform->rsaKey);
            oci_bind_by_name($query, 'lti_version', $platform->ltiVersion);
            oci_bind_by_name($query, 'signature_method', $platform->signatureMethod);
            oci_bind_by_name($query, 'consumer_name', $platform->consumerName);
            oci_bind_by_name($query, 'consumer_version', $platform->consumerVersion);
            oci_bind_by_name($query, 'consumer_guid', $platform->consumerGuid);
            oci_bind_by_name($query, 'profile', $profile);
            oci_bind_by_name($query, 'tool_proxy', $platform->toolProxy);
            oci_bind_by_name($query, 'settings', $settingsValue);
            oci_bind_by_name($query, 'protected', $protected);
            oci_bind_by_name($query, 'enabled', $enabled);
            oci_bind_by_name($query, 'enable_from', $from);
            oci_bind_by_name($query, 'enable_until', $until);
            oci_bind_by_name($query, 'last_access', $last);
            oci_bind_by_name($query, 'updated', $now);
            oci_bind_by_name($query, 'id', $id);
        }
        $ok = $this->executeQuery($sql, $query);
        if ($ok) {
            if (empty($id)) {
                $platform->setRecordId(intval($pk));
                $platform->created = $time;
            }
            $platform->updated = $time;
        }

        return $ok;
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
        $id = $platform->getRecordId();

// Delete any access token for this consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::ACCESS_TOKEN_TABLE_NAME)}
WHERE (consumer_pk = :id)
EOD;
        $query = oci_parse($this->db, $sql);
        oci_bind_by_name($query, 'id', $id);
        $this->executeQuery($sql, $query);

// Delete any nonce values for this consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::NONCE_TABLE_NAME)}
WHERE (consumer_pk = :id)
EOD;
        $query = oci_parse($this->db, $sql);
        oci_bind_by_name($query, 'id', $id);
        $this->executeQuery($sql, $query);

// Delete any outstanding share keys for resource links for this consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)}
WHERE resource_link_pk IN (
  SELECT resource_link_pk
  FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
  WHERE (consumer_pk = :id)
)
EOD;
        $query = oci_parse($this->db, $sql);
        oci_bind_by_name($query, 'id', $id);
        $this->executeQuery($sql, $query);

// Delete any outstanding share keys for resource links for contexts in this consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)}
WHERE resource_link_pk IN (
  SELECT resource_link_pk
  FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl
    INNER JOIN {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c ON (rl.context_pk = c.context_pk)
  WHERE (c.consumer_pk = :id)
)
EOD;
        $query = oci_parse($this->db, $sql);
        oci_bind_by_name($query, 'id', $id);
        $this->executeQuery($sql, $query);

// Delete any users in resource links for this consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)}
WHERE resource_link_pk IN (
  SELECT resource_link_pk
  FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
  WHERE (consumer_pk = :id)
)
EOD;
        $query = oci_parse($this->db, $sql);
        oci_bind_by_name($query, 'id', $id);
        $this->executeQuery($sql, $query);

// Delete any users in resource links for contexts in this consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)}
WHERE resource_link_pk IN (
  SELECT resource_link_pk
  FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl
    INNER JOIN {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c ON (rl.context_pk = c.context_pk)
  WHERE (c.consumer_pk = :id)
)
EOD;
        $query = oci_parse($this->db, $sql);
        oci_bind_by_name($query, 'id', $id);
        $this->executeQuery($sql, $query);

// Update any resource links for which this consumer is acting as a primary resource link
        $sql = <<< EOD
UPDATE {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
SET primary_resource_link_pk = NULL, share_approved = NULL
WHERE primary_resource_link_pk IN (
  SELECT resource_link_pk
  FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
  WHERE (consumer_pk = :id)
)
EOD;
        $query = oci_parse($this->db, $sql);
        oci_bind_by_name($query, 'id', $id);
        $this->executeQuery($sql, $query);

// Update any resource links for contexts in which this consumer is acting as a primary resource link
        $sql = <<< EOD
UPDATE {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
SET primary_resource_link_pk = NULL, share_approved = NULL
WHERE primary_resource_link_pk IN (
  SELECT rl.resource_link_pk
  FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl
    INNER JOIN {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c ON (rl.context_pk = c.context_pk)
  WHERE (c.consumer_pk = :id)
)
EOD;
        $query = oci_parse($this->db, $sql);
        oci_bind_by_name($query, 'id', $id);
        $this->executeQuery($sql, $query);

// Delete any resource links for this consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
WHERE (consumer_pk = :id)
EOD;
        $query = oci_parse($this->db, $sql);
        oci_bind_by_name($query, 'id', $id);
        $this->executeQuery($sql, $query);

// Delete any resource links for contexts in this consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
WHERE context_pk IN (
  SELECT context_pk
  FROM {$this->dbTableName(static::CONTEXT_TABLE_NAME)}
  WHERE (consumer_pk = :id)
)
EOD;
        $query = oci_parse($this->db, $sql);
        oci_bind_by_name($query, 'id', $id);
        $this->executeQuery($sql, $query);

// Delete any contexts for this consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::CONTEXT_TABLE_NAME)}
WHERE (consumer_pk = :id)
EOD;
        $query = oci_parse($this->db, $sql);
        oci_bind_by_name($query, 'id', $id);
        $this->executeQuery($sql, $query);

// Delete consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::PLATFORM_TABLE_NAME)}
WHERE (consumer_pk = :id)
EOD;
        $query = oci_parse($this->db, $sql);
        oci_bind_by_name($query, 'id', $id);
        $ok = $this->executeQuery($sql, $query);

        if ($ok) {
            $platform->initialize();
        }

        return $ok;
    }

    /**
     * Load platform objects.
     *
     * @return Platform[]  Array of all defined Platform objects
     */
    public function getPlatforms(): array
    {
        $platforms = [];

        $sql = <<< EOD
SELECT consumer_pk, name, consumer_key, secret, platform_id, client_id, deployment_id, public_key,
  lti_version, signature_method, consumer_name, consumer_version, consumer_guid, profile, tool_proxy, settings,
  protected, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::PLATFORM_TABLE_NAME)}
ORDER BY name
EOD;
        $query = oci_parse($this->db, $sql);
        $ok = ($query !== false);

        if ($ok) {
            $ok = $this->executeQuery($sql, $query);
        }

        if ($ok) {
            while ($row = oci_fetch_assoc($query)) {
                $row = array_change_key_case($row);
                $platform = new Platform($this);
                $platform->setRecordId(intval($row['consumer_pk']));
                $platform->name = $row['name'];
                $platform->setKey($row['consumer_key']);
                $platform->secret = $row['secret'];
                $platform->platformId = $row['platform_id'];
                $platform->clientId = $row['client_id'];
                $platform->deploymentId = $row['deployment_id'];
                $platform->rsaKey = $row['public_key'];
                $platform->ltiVersion = $row['lti_version'];
                $platform->signatureMethod = $row['signature_method'];
                $platform->consumerName = $row['consumer_name'];
                $platform->consumerVersion = $row['consumer_version'];
                $platform->consumerGuid = $row['consumer_guid'];
                $platform->profile = Util::json_decode($row['profile']);
                $platform->toolProxy = $row['tool_proxy'];
                $settingsValue = $row['settings']->load();
                if (is_string($settingsValue)) {
                    $settings = Util::json_decode($settingsValue, true);
                    if (!is_array($settings)) {
                        $settings = @unserialize($settingsValue);  // check for old serialized setting
                    }
                    if (!is_array($settings)) {
                        $settings = [];
                    }
                } else {
                    $settings = [];
                }
                $platform->setSettings($settings);
                $platform->protected = (intval($row['protected']) === 1);
                $platform->enabled = (intval($row['enabled']) === 1);
                $platform->enableFrom = null;
                if (!is_null($row['enable_from'])) {
                    $platform->enableFrom = strtotime($row['enable_from']);
                }
                $platform->enableUntil = null;
                if (!is_null($row['enable_until'])) {
                    $platform->enableUntil = strtotime($row['enable_until']);
                }
                $platform->lastAccess = null;
                if (!is_null($row['last_access'])) {
                    $platform->lastAccess = strtotime($row['last_access']);
                }
                $platform->created = strtotime($row['created']);
                $platform->updated = strtotime($row['updated']);
                $this->fixPlatformSettings($platform, false);
                $platforms[] = $platform;
            }
        }

        return $platforms;
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
        $ok = false;
        if (!is_null($context->getRecordId())) {
            $sql = <<< EOD
SELECT context_pk, consumer_pk, title, lti_context_id, type, settings, created, updated
FROM {$this->dbTableName(static::CONTEXT_TABLE_NAME)}
WHERE (context_pk = :id)
EOD;
            $query = oci_parse($this->db, $sql);
            $id = $context->getRecordId();
            oci_bind_by_name($query, 'id', $id);
        } else {
            $sql = <<< EOD
SELECT context_pk, consumer_pk, title, lti_context_id, type, settings, created, updated
FROM {$this->dbTableName(static::CONTEXT_TABLE_NAME)}
WHERE (consumer_pk = :cid) AND (lti_context_id = :ctx)
EOD;
            $query = oci_parse($this->db, $sql);
            $id = $context->getPlatform()->getRecordId();
            oci_bind_by_name($query, 'cid', $id);
            oci_bind_by_name($query, 'ctx', $context->ltiContextId);
        }
        $ok = $this->executeQuery($sql, $query);
        if ($ok) {
            $row = oci_fetch_assoc($query);
            $ok = ($row !== false);
        }
        if ($ok) {
            $row = array_change_key_case($row);
            $context->setRecordId(intval($row['context_pk']));
            $context->setPlatformId(intval($row['consumer_pk']));
            $context->title = $row['title'];
            $context->ltiContextId = $row['lti_context_id'];
            $context->type = $row['type'];
            $settingsValue = $row['settings']->load();
            if (is_string($settingsValue)) {
                $settings = Util::json_decode($settingsValue, true);
                if (!is_array($settings)) {
                    $settings = @unserialize($settingsValue);  // check for old serialized setting
                }
                if (!is_array($settings)) {
                    $settings = [];
                }
            } else {
                $settings = [];
            }
            $context->setSettings($settings);
            $context->created = strtotime($row['created']);
            $context->updated = strtotime($row['updated']);
        }

        return $ok;
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
        $time = time();
        $now = date("{$this->dateFormat} {$this->timeFormat}", $time);
        $settingsValue = json_encode($context->getSettings());
        $id = $context->getRecordId();
        $consumer_pk = $context->getPlatform()->getRecordId();
        if (empty($id)) {
            $pk = null;
            $sql = <<< EOD
INSERT INTO {$this->dbTableName(static::CONTEXT_TABLE_NAME)} (
  consumer_pk, title, lti_context_id, type, settings, created, updated)
VALUES (:cid, :title, :ctx, :type, :settings, :created, :updated)
RETURNING context_pk INTO :pk
EOD;
            $query = oci_parse($this->db, $sql);
            oci_bind_by_name($query, 'cid', $consumer_pk);
            oci_bind_by_name($query, 'title', $context->title);
            oci_bind_by_name($query, 'ctx', $context->ltiContextId);
            oci_bind_by_name($query, 'type', $context->type);
            oci_bind_by_name($query, 'settings', $settingsValue);
            oci_bind_by_name($query, 'created', $now);
            oci_bind_by_name($query, 'updated', $now);
            oci_bind_by_name($query, 'pk', $pk);
        } else {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::CONTEXT_TABLE_NAME)}
SET title = :title, lti_context_id = :ctx, type = :type, settings = :settings, updated = :updated
WHERE (consumer_pk = :cid) AND (context_pk = :ctxid)
EOD;
            $query = oci_parse($this->db, $sql);
            oci_bind_by_name($query, 'title', $context->title);
            oci_bind_by_name($query, 'ctx', $context->ltiContextId);
            oci_bind_by_name($query, 'type', $context->type);
            oci_bind_by_name($query, 'settings', $settingsValue);
            oci_bind_by_name($query, 'updated', $now);
            oci_bind_by_name($query, 'cid', $consumer_pk);
            oci_bind_by_name($query, 'ctxid', $id);
        }
        $ok = $this->executeQuery($sql, $query);
        if ($ok) {
            if (empty($id)) {
                $context->setRecordId(intval($pk));
                $context->created = $time;
            }
            $context->updated = $time;
        }

        return $ok;
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
        $id = $context->getRecordId();

// Delete any outstanding share keys for resource links for this context
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)}
WHERE resource_link_pk IN (
  SELECT resource_link_pk FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
  WHERE (context_pk = :id)
)
EOD;
        $query = oci_parse($this->db, $sql);
        oci_bind_by_name($query, 'id', $id);
        $this->executeQuery($sql, $query);

// Delete any users in resource links for this context
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)}
WHERE resource_link_pk IN (
  SELECT resource_link_pk
  FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
  WHERE (context_pk = :id)
}
EOD;
        $query = oci_parse($this->db, $sql);
        oci_bind_by_name($query, 'id', $id);
        $this->executeQuery($sql, $query);

// Update any resource links for which this context is acting as a primary resource link
        $sql = <<< EOD
UPDATE {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
SET primary_resource_link_pk = NULL, share_approved = NULL
WHERE primary_resource_link_pk IN (
  SELECT resource_link_pk
  FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
  WHERE (context_pk = :id)
)
EOD;
        $query = oci_parse($this->db, $sql);
        oci_bind_by_name($query, 'id', $id);
        $this->executeQuery($sql, $query);

// Delete any resource links for this context
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
WHERE (context_pk = :id)
EOD;
        $query = oci_parse($this->db, $sql);
        oci_bind_by_name($query, 'id', $id);
        $this->executeQuery($sql, $query);

// Delete context
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::CONTEXT_TABLE_NAME)}
WHERE (context_pk = :id)
EOD;
        $query = oci_parse($this->db, $sql);
        oci_bind_by_name($query, 'id', $id);
        $ok = $this->executeQuery($sql, $query);

        if ($ok) {
            $context->initialize();
        }

        return $ok;
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
        if (!is_null($resourceLink->getRecordId())) {
            $sql = <<< EOD
SELECT resource_link_pk, context_pk, consumer_pk, title, lti_resource_link_id, settings,
  primary_resource_link_pk, share_approved, created, updated
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
WHERE (resource_link_pk = :id)
EOD;
            $query = oci_parse($this->db, $sql);
            $id = $resourceLink->getRecordId();
            oci_bind_by_name($query, 'id', $id);
        } elseif (!is_null($resourceLink->getContext())) {
            $sql = <<< EOD
SELECT r.resource_link_pk, r.context_pk, r.consumer_pk, r.title, r.lti_resource_link_id, r.settings, r.primary_resource_link_pk,
  r.share_approved, r.created, r.updated
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} r
WHERE (r.lti_resource_link_id = :rlid) AND ((r.context_pk = :id1) OR (r.consumer_pk IN (
  SELECT c.consumer_pk
  FROM {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c
  WHERE (c.context_pk = :id2)
)))
EOD;
            $query = oci_parse($this->db, $sql);
            $rlid = $resourceLink->getId();
            oci_bind_by_name($query, 'rlid', $rlid);
            $id = $resourceLink->getContext()->getRecordId();
            oci_bind_by_name($query, 'id1', $id);
            oci_bind_by_name($query, 'id2', $id);
        } else {
            $sql = <<< EOD
SELECT r.resource_link_pk, r.context_pk, r.consumer_pk, r.title, r.lti_resource_link_id, r.settings, r.primary_resource_link_pk,
  r.share_approved, r.created, r.updated
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} r
  LEFT OUTER JOIN {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c ON (r.context_pk = c.context_pk)
WHERE ((r.consumer_pk = :id1) OR (c.consumer_pk = :id2)) AND (lti_resource_link_id = :rlid)
EOD;
            $query = oci_parse($this->db, $sql);
            $id1 = $resourceLink->getPlatform()->getRecordId();
            oci_bind_by_name($query, 'id1', $id1);
            $id2 = $resourceLink->getPlatform()->getRecordId();
            oci_bind_by_name($query, 'id2', $id2);
            $id = $resourceLink->getId();
            oci_bind_by_name($query, 'rlid', $id);
        }
        $ok = $this->executeQuery($sql, $query);
        if ($ok) {
            $row = oci_fetch_assoc($query);
            $ok = ($row !== false);
        }

        if ($ok) {
            $row = array_change_key_case($row);
            $resourceLink->setRecordId(intval($row['resource_link_pk']));
            if (!is_null($row['context_pk'])) {
                $resourceLink->setContextId(intval($row['context_pk']));
            } else {
                $resourceLink->setContextId(null);
            }
            if (!is_null($row['consumer_pk'])) {
                $resourceLink->setPlatformId(intval($row['consumer_pk']));
            } else {
                $resourceLink->setPlatformId(null);
            }
            $resourceLink->title = $row['title'];
            $resourceLink->ltiResourceLinkId = $row['lti_resource_link_id'];
            $settings = $row['settings']->load();
            $settingsValue = $row['settings']->load();
            if (is_string($settingsValue)) {
                $settings = Util::json_decode($settingsValue, true);
                if (!is_array($settings)) {
                    $settings = @unserialize($settingsValue);  // check for old serialized setting
                }
                if (!is_array($settings)) {
                    $settings = [];
                }
            } else {
                $settings = [];
            }
            $resourceLink->setSettings($settings);
            if (!is_null($row['primary_resource_link_pk'])) {
                $resourceLink->primaryResourceLinkId = intval($row['primary_resource_link_pk']);
            } else {
                $resourceLink->primaryResourceLinkId = null;
            }
            $resourceLink->shareApproved = (is_null($row['share_approved'])) ? null : (intval($row['share_approved']) === 1);
            $resourceLink->created = strtotime($row['created']);
            $resourceLink->updated = strtotime($row['updated']);
        }

        return $ok;
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
        if (is_null($resourceLink->shareApproved)) {
            $approved = null;
        } elseif ($resourceLink->shareApproved) {
            $approved = 1;
        } else {
            $approved = 0;
        }
        $time = time();
        $now = date("{$this->dateFormat} {$this->timeFormat}", $time);
        $settingsValue = json_encode($resourceLink->getSettings());
        if (!is_null($resourceLink->getContext())) {
            $consumerId = null;
            $contextId = $resourceLink->getContext()->getRecordId();
        } elseif (!is_null($resourceLink->getContextId())) {
            $consumerId = null;
            $contextId = $resourceLink->getContextId();
        } else {
            $consumerId = $resourceLink->getPlatform()->getRecordId();
            $contextId = null;
        }
        if (empty($resourceLink->primaryResourceLinkId)) {
            $primaryResourceLinkId = null;
        } else {
            $primaryResourceLinkId = $resourceLink->primaryResourceLinkId;
        }
        $id = $resourceLink->getRecordId();
        if (empty($id)) {
            $pk = null;
            $sql = <<< EOD
INSERT INTO {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} (
  consumer_pk, context_pk, lti_resource_link_id, settings, primary_resource_link_pk, share_approved, created, updated)
VALUES (:cid, :ctx, :rlid, :settings, :prlid, :share_approved, :created, :updated)
RETURNING resource_link_pk INTO :pk
EOD;
            $query = oci_parse($this->db, $sql);
            oci_bind_by_name($query, 'cid', $consumerId);
            oci_bind_by_name($query, 'ctx', $contextId);
            $rlid = $resourceLink->getId();
            oci_bind_by_name($query, 'rlid', $rlid);
            oci_bind_by_name($query, 'settings', $settingsValue);
            oci_bind_by_name($query, 'prlid', $primaryResourceLinkId);
            oci_bind_by_name($query, 'share_approved', $approved);
            oci_bind_by_name($query, 'created', $now);
            oci_bind_by_name($query, 'updated', $now);
            oci_bind_by_name($query, 'pk', $pk);
        } elseif (!is_null($contextId)) {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
SET consumer_pk = NULL, context_pk = :ctx, lti_resource_link_id = :rlid, settings = :settings,
  primary_resource_link_pk = :prlid, share_approved = :share_approved, updated = :updated
WHERE (resource_link_pk = :id)
EOD;
            $query = oci_parse($this->db, $sql);
            oci_bind_by_name($query, 'ctx', $contextId);
            $rlid = $resourceLink->getId();
            oci_bind_by_name($query, 'rlid', $rlid);
            oci_bind_by_name($query, 'settings', $settingsValue);
            oci_bind_by_name($query, 'prlid', $primaryResourceLinkId);
            oci_bind_by_name($query, 'share_approved', $approved);
            oci_bind_by_name($query, 'updated', $now);
            oci_bind_by_name($query, 'id', $id);
        } else {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
SET context_pk = NULL, lti_resource_link_id = :rlid, settings = :settings, primary_resource_link_pk = :prlid,
  share_approved = :share_approved, updated = :updated
WHERE (consumer_pk = :cid) AND (resource_link_pk = :id)
EOD;
            $query = oci_parse($this->db, $sql);
            $rlid = $resourceLink->getId();
            oci_bind_by_name($query, 'rlid', $rlid);
            oci_bind_by_name($query, 'settings', $settingsValue);
            oci_bind_by_name($query, 'prlid', $primaryResourceLinkId);
            oci_bind_by_name($query, 'share_approved', $approved);
            oci_bind_by_name($query, 'updated', $now);
            oci_bind_by_name($query, 'cid', $consumerId);
            oci_bind_by_name($query, 'id', $id);
        }
        $ok = $this->executeQuery($sql, $query);
        if ($ok) {
            if (empty($id)) {
                $resourceLink->setRecordId(intval($pk));
                $resourceLink->created = $time;
            }
            $resourceLink->updated = $time;
        }

        return $ok;
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
        $id = $resourceLink->getRecordId();

// Delete any outstanding share keys for resource links for this consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)}
WHERE (resource_link_pk = :id)
EOD;
        $query = oci_parse($this->db, $sql);
        oci_bind_by_name($query, 'id', $id);
        $ok = $this->executeQuery($sql, $query);

// Delete users
        if ($ok) {
            $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)}
WHERE (resource_link_pk = :id)
EOD;
            $query = oci_parse($this->db, $sql);
            oci_bind_by_name($query, 'id', $id);
            $ok = $this->executeQuery($sql, $query);
        }

// Update any resource links for which this is the primary resource link
        if ($ok) {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
SET primary_resource_link_pk = NULL
WHERE (primary_resource_link_pk = :id)
EOD;
            $query = oci_parse($this->db, $sql);
            oci_bind_by_name($query, 'id', $id);
            $ok = $this->executeQuery($sql, $query);
        }

// Delete resource link
        if ($ok) {
            $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
WHERE (resource_link_pk = :id)
EOD;
            $query = oci_parse($this->db, $sql);
            oci_bind_by_name($query, 'id', $id);
            $ok = $this->executeQuery($sql, $query);
        }

        if ($ok) {
            $resourceLink->initialize();
        }

        return $ok;
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
     * @return UserResult[] Array of UserResult objects
     */
    public function getUserResultSourcedIDsResourceLink(ResourceLink $resourceLink, bool $localOnly, ?IdScope $idScope): array
    {
        $id = $resourceLink->getRecordId();
        $userResults = [];

        if ($localOnly) {
            $sql = <<< EOD
SELECT u.user_result_pk, u.lti_result_sourcedid, u.lti_user_id, u.created, u.updated
FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)} u
  INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl ON (u.resource_link_pk = rl.resource_link_pk)
WHERE (rl.resource_link_pk = :id) AND (rl.primary_resource_link_pk IS NULL)
EOD;
            $query = oci_parse($this->db, $sql);
            oci_bind_by_name($query, 'id', $id);
        } else {
            $sql = <<< EOD
SELECT u.user_result_pk, u.lti_result_sourcedid, u.lti_user_id, u.created, u.updated
FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)} u
  INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl ON (u.resource_link_pk = rl.resource_link_pk)
WHERE ((rl.resource_link_pk = :id) AND (rl.primary_resource_link_pk IS NULL)) OR
  ((rl.primary_resource_link_pk = :pid) AND (share_approved = 1))
EOD;
            $query = oci_parse($this->db, $sql);
            oci_bind_by_name($query, 'id', $id);
            oci_bind_by_name($query, 'pid', $id);
        }
        if ($this->executeQuery($sql, $query)) {
            while ($row = oci_fetch_assoc($query)) {
                $row = array_change_key_case($row);
                $userResult = LTI\UserResult::fromRecordId(intval($row['user_result_pk']), $resourceLink->getDataConnector());
//                $userResult->setRecordId(intval($row['user_result_pk']));
                $userResult->ltiResultSourcedId = $row['lti_result_sourcedid'];
                $userResult->created = strtotime($row['created']);
                $userResult->updated = strtotime($row['updated']);
                if (is_null($idScope)) {
                    $userResults[] = $userResult;
                } else {
                    $userResults[$userResult->getId($idScope)] = $userResult;
                }
            }
        }

        return $userResults;
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
        $id = $resourceLink->getRecordId();

        $shares = [];

        $sql = <<< EOD
SELECT c.consumer_name, r.resource_link_pk, r.title, r.share_approved
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} r
  INNER JOIN {$this->dbTableName(static::PLATFORM_TABLE_NAME)} c ON (r.consumer_pk = c.consumer_pk)
WHERE (r.primary_resource_link_pk = :id1)
UNION
SELECT c2.consumer_name, r2.resource_link_pk, r2.title, r2.share_approved
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} r2
  INNER JOIN {$this->dbTableName(static::CONTEXT_TABLE_NAME)} x ON (r2.context_pk = x.context_pk)
  INNER JOIN {$this->dbTableName(static::PLATFORM_TABLE_NAME)} c2 ON (x.consumer_pk = c2.consumer_pk)
WHERE (r2.primary_resource_link_pk = :id2)
ORDER BY consumer_name, title
EOD;
        $query = oci_parse($this->db, $sql);
        oci_bind_by_name($query, 'id1', $id);
        oci_bind_by_name($query, 'id2', $id);
        if ($this->executeQuery($sql, $query)) {
            while ($row = oci_fetch_assoc($query)) {
                $row = array_change_key_case($row);
                $share = new LTI\ResourceLinkShare();
                $share->consumerName = $row['consumer_name'];
                $share->resourceLinkId = intval($row['resource_link_pk']);
                $share->title = $row['title'];
                $share->approved = (intval($row['share_approved']) === 1);
                $shares[] = $share;
            }
        }

        return $shares;
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
        if (parent::useMemcache()) {
            $ok = parent::loadPlatformNonce($nonce);
        } else {
// Delete any expired nonce values
            $now = date("{$this->dateFormat} {$this->timeFormat}", time());
            $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::NONCE_TABLE_NAME)}
WHERE (expires <= :now)
EOD;
            $query = oci_parse($this->db, $sql);
            oci_bind_by_name($query, 'now', $now);
            $this->executeQuery($sql, $query);

// Load the nonce
            $id = $nonce->getPlatform()->getRecordId();
            $value = $nonce->getValue();
            $sql = <<< EOD
SELECT value T
FROM {$this->dbTableName(static::NONCE_TABLE_NAME)}
WHERE (consumer_pk = :id) AND (value = :value)
EOD;
            $query = oci_parse($this->db, $sql);
            oci_bind_by_name($query, 'id', $id);
            oci_bind_by_name($query, 'value', $value);
            $ok = $this->executeQuery($sql, $query, false);
            if ($ok) {
                $row = oci_fetch_assoc($query);
                if ($row === false) {
                    $ok = false;
                }
            }
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
        if (parent::useMemcache()) {
            $ok = parent::savePlatformNonce($nonce);
        } else {
            $id = $nonce->getPlatform()->getRecordId();
            $value = $nonce->getValue();
            $expires = date("{$this->dateFormat} {$this->timeFormat}", $nonce->expires);
            $sql = <<< EOD
INSERT INTO {$this->dbTableName(static::NONCE_TABLE_NAME)} (consumer_pk, value, expires)
VALUES (:id, :value, :expires)
EOD;
            $query = oci_parse($this->db, $sql);
            oci_bind_by_name($query, 'id', $id);
            oci_bind_by_name($query, 'value', $value);
            oci_bind_by_name($query, 'expires', $expires);
            $ok = $this->executeQuery($sql, $query);
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
        if (parent::useMemcache()) {
            $ok = parent::deletePlatformNonce($nonce);
        } else {
            $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::NONCE_TABLE_NAME)}
WHERE (consumer_pk = :id) AND (value = :value)
EOD;
            $query = oci_parse($this->db, $sql);
            $id = $nonce->getPlatform()->getRecordId();
            oci_bind_by_name($query, 'id', $id);
            $value = $nonce->getValue();
            oci_bind_by_name($query, 'value', $value);
            $ok = $this->executeQuery($sql, $query);
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
        if (parent::useMemcache()) {
            $ok = parent::loadAccessToken($accessToken);
        } else {
            $ok = false;
            $consumer_pk = $accessToken->getPlatform()->getRecordId();
            $sql = <<< EOD
SELECT scopes, token, expires, created, updated
FROM {$this->dbTableName(static::ACCESS_TOKEN_TABLE_NAME)}
WHERE (consumer_pk = :consumer_pk)
EOD;
            $query = oci_parse($this->db, $sql);
            oci_bind_by_name($query, 'consumer_pk', $consumer_pk);
            $this->executeQuery($sql, $query, false);
            if ($this->executeQuery($sql, $query)) {
                $row = oci_fetch_assoc($query);
                if ($row !== false) {
                    $row = array_change_key_case($row);
                    $scopes = Util::json_decode($row['scopes']->load(), true);
                    if (!is_array($scopes)) {
                        $scopes = [];
                    }
                    $accessToken->scopes = $scopes;
                    $accessToken->token = $row['token'];
                    $accessToken->expires = strtotime($row['expires']);
                    $accessToken->created = strtotime($row['created']);
                    $accessToken->updated = strtotime($row['updated']);
                    $ok = true;
                }
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
        if (parent::useMemcache()) {
            $ok = parent::saveAccessToken($accessToken);
        } else {
            $consumer_pk = $accessToken->getPlatform()->getRecordId();
            $scopes = json_encode($accessToken->scopes, JSON_UNESCAPED_SLASHES);
            $token = $accessToken->token;
            $expires = date("{$this->dateFormat} {$this->timeFormat}", $accessToken->expires);
            $time = time();
            $now = date("{$this->dateFormat} {$this->timeFormat}", $time);
            if (empty($accessToken->created)) {
                $sql = <<< EOD
INSERT INTO {$this->dbTableName(static::ACCESS_TOKEN_TABLE_NAME)} (
  consumer_pk, scopes, token, expires, created, updated)
VALUES (:consumer_pk, :scopes, :token, :expires, :created, :updated)
EOD;
                $query = oci_parse($this->db, $sql);
                oci_bind_by_name($query, 'consumer_pk', $consumer_pk);
                oci_bind_by_name($query, 'scopes', $scopes);
                oci_bind_by_name($query, 'token', $token);
                oci_bind_by_name($query, 'expires', $expires);
                oci_bind_by_name($query, 'created', $now);
                oci_bind_by_name($query, 'updated', $now);
            } else {
                $sql = <<< EOD
UPDATE {$this->dbTableName(static::ACCESS_TOKEN_TABLE_NAME)}
SET scopes = :scopes, token = :token, expires = :expires, updated = :updated
WHERE (consumer_pk = :consumer_pk)
EOD;
                $query = oci_parse($this->db, $sql);
                oci_bind_by_name($query, 'scopes', $scopes);
                oci_bind_by_name($query, 'token', $token);
                oci_bind_by_name($query, 'expires', $expires);
                oci_bind_by_name($query, 'updated', $now);
                oci_bind_by_name($query, 'consumer_pk', $consumer_pk);
            }
            $ok = $this->executeQuery($sql, $query);
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
        $ok = false;

// Clear expired share keys
        $now = date("{$this->dateFormat} {$this->timeFormat}", time());
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)}
WHERE (expires <= :now)
EOD;
        $query = oci_parse($this->db, $sql);
        oci_bind_by_name($query, 'now', $now);
        $this->executeQuery($sql, $query);

// Load share key
        $id = $shareKey->getId();
        $sql = <<< EOD
SELECT resource_link_pk, auto_approve, expires
FROM {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)}
WHERE (share_key_id = :id)
EOD;
        $query = oci_parse($this->db, $sql);
        oci_bind_by_name($query, 'id', $id);
        if ($this->executeQuery($sql, $query)) {
            $row = oci_fetch_assoc($query);
            if ($row !== false) {
                $row = array_change_key_case($row);
                $shareKey->resourceLinkId = intval($row['resource_link_pk']);
                $shareKey->autoApprove = ($row['auto_approve'] === 1);
                $shareKey->expires = strtotime($row['expires']);
                $ok = true;
            }
        }

        return $ok;
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
        if ($shareKey->autoApprove) {
            $approve = 1;
        } else {
            $approve = 0;
        }
        $id = $shareKey->getId();
        $expires = date("{$this->dateFormat} {$this->timeFormat}", $shareKey->expires);
        $sql = <<< EOD
INSERT INTO {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)} (
  share_key_id, resource_link_pk, auto_approve, expires)
VALUES (:id, :prlid, :approve, :expires)
EOD;
        $query = oci_parse($this->db, $sql);
        oci_bind_by_name($query, 'id', $id);
        oci_bind_by_name($query, 'prlid', $shareKey->resourceLinkId);
        oci_bind_by_name($query, 'approve', $approve);
        oci_bind_by_name($query, 'expires', $expires);
        $ok = $this->executeQuery($sql, $query);

        return $ok;
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
        $id = $shareKey->getId();
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)}
WHERE (share_key_id = :id)
EOD;
        $query = oci_parse($this->db, $sql);
        oci_bind_by_name($query, 'id', $id);
        $ok = $this->executeQuery($sql, $query);

        if ($ok) {
            $shareKey->initialize();
        }

        return $ok;
    }

###
###  UserResult Result methods
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
        $ok = false;
        if (!is_null($userResult->getRecordId())) {
            $id = $userResult->getRecordId();
            $sql = <<< EOD
SELECT user_result_pk, resource_link_pk, lti_user_id, lti_result_sourcedid, created, updated
FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)}
WHERE (user_result_pk = :id)
EOD;
            $query = oci_parse($this->db, $sql);
            oci_bind_by_name($query, 'id', $id);
        } else {
            $id = $userResult->getResourceLink()->getRecordId();
            $uid = $userResult->getId(LTI\Enum\IdScope::IdOnly);
            $sql = <<< EOD
SELECT user_result_pk, resource_link_pk, lti_user_id, lti_result_sourcedid, created, updated
FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)}
WHERE (resource_link_pk = :id) AND (lti_user_id = :u_id)
EOD;
            $query = oci_parse($this->db, $sql);
            oci_bind_by_name($query, 'id', $id);
            oci_bind_by_name($query, 'u_id', $uid);
        }
        if ($this->executeQuery($sql, $query)) {
            $row = oci_fetch_assoc($query);
            if ($row !== false) {
                $row = array_change_key_case($row);
                $userResult->setRecordId(intval($row['user_result_pk']));
                $userResult->setResourceLinkId(intval($row['resource_link_pk']));
                $userResult->ltiUserId = $row['lti_user_id'];
                $userResult->ltiResultSourcedId = $row['lti_result_sourcedid'];
                $userResult->created = strtotime($row['created']);
                $userResult->updated = strtotime($row['updated']);
                $ok = true;
            }
        }

        return $ok;
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
        $time = time();
        $now = date("{$this->dateFormat} {$this->timeFormat}", $time);
        if (is_null($userResult->created)) {
            $pk = null;
            $sql = <<< EOD
INSERT INTO {$this->dbTableName(static::USER_RESULT_TABLE_NAME)} (
  resource_link_pk, lti_user_id, lti_result_sourcedid, created, updated)
VALUES (:rlid, :u_id, :sourcedid, :created, :updated)
RETURNING user_result_pk INTO :pk
EOD;
            $query = oci_parse($this->db, $sql);
            $rlid = $userResult->getResourceLink()->getRecordId();
            oci_bind_by_name($query, 'rlid', $rlid);
            $uid = $userResult->getId(LTI\Enum\IdScope::IdOnly);
            oci_bind_by_name($query, 'u_id', $uid);
            $sourcedid = $userResult->ltiResultSourcedId;
            oci_bind_by_name($query, 'sourcedid', $sourcedid);
            oci_bind_by_name($query, 'created', $now);
            oci_bind_by_name($query, 'updated', $now);
            oci_bind_by_name($query, 'pk', $pk);
        } else {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::USER_RESULT_TABLE_NAME)}
SET lti_result_sourcedid = :sourcedid, updated = :updated
WHERE (user_result_pk = :id)
EOD;
            $query = oci_parse($this->db, $sql);
            $sourcedid = $userResult->ltiResultSourcedId;
            oci_bind_by_name($query, 'sourcedid', $sourcedid);
            oci_bind_by_name($query, 'updated', $now);
            $id = $userResult->getRecordId();
            oci_bind_by_name($query, 'id', $id);
        }
        $ok = $this->executeQuery($sql, $query);
        if ($ok) {
            if (is_null($userResult->created)) {
                $userResult->setRecordId(intval($pk));
                $userResult->created = $time;
            }
            $userResult->updated = $time;
        }

        return $ok;
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
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)}
WHERE (user_result_pk = :id)
EOD;
        $query = oci_parse($this->db, $sql);
        $id = $userResult->getRecordId();
        oci_bind_by_name($query, 'id', $id);
        $ok = $this->executeQuery($sql, $query);

        if ($ok) {
            $userResult->initialize();
        }

        return $ok;
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
        $ok = false;
        if (!is_null($tool->getRecordId())) {
            $sql = <<< EOD
SELECT tool_pk, name, consumer_key, secret, message_url, initiate_login_url, redirection_uris, public_key,
  lti_version, signature_method, settings, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::TOOL_TABLE_NAME)}
WHERE (tool_pk = :id)
EOD;
            $query = oci_parse($this->db, $sql);
            $id = $tool->getRecordId();
            oci_bind_by_name($query, 'id', $id);
        } elseif (!empty($tool->initiateLoginUrl)) {
            $sql = <<< EOD
SELECT tool_pk, name, consumer_key, secret, message_url, initiate_login_url, redirection_uris, public_key,
  lti_version, signature_method, settings, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::TOOL_TABLE_NAME)}
WHERE (initiate_login_url = :initiate_login_url)
EOD;
            $query = oci_parse($this->db, $sql);
            oci_bind_by_name($query, 'initiate_login_url', $tool->initiateLoginUrl);
        } else {
            $sql = <<< EOD
SELECT tool_pk, name, consumer_key, secret, message_url, initiate_login_url, redirection_uris, public_key,
  lti_version, signature_method, settings, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::TOOL_TABLE_NAME)}
WHERE (consumer_key = :key)
EOD;
            $query = oci_parse($this->db, $sql);
            $consumerKey = $tool->getKey();
            oci_bind_by_name($query, 'key', $consumerKey);
        }
        $ok = $this->executeQuery($sql, $query);
        if ($ok) {
            $row = oci_fetch_assoc($query);
            $ok = ($row !== false);
        }
        if ($ok) {
            $row = array_change_key_case($row);
            $tool->setRecordId(intval($row['tool_pk']));
            $tool->name = $row['name'];
            $tool->setkey($row['consumer_key']);
            $tool->secret = $row['secret'];
            $tool->messageUrl = $row['message_url'];
            $tool->initiateLoginUrl = $row['initiate_login_url'];
            $redirectionUrisValue = $row['redirection_uris']->load();
            if (is_string($redirectionUrisValue)) {
                $redirectionUris = Util::json_decode($redirectionUrisValue, true);
                if (!is_array($redirectionUris)) {
                    $redirectionUris = [];
                }
            } else {
                $redirectionUris = [];
            }
            $tool->redirectionUris = $redirectionUris;
            $tool->rsaKey = $row['public_key'];
            $tool->ltiVersion = $row['lti_version'];
            $tool->signatureMethod = $row['signature_method'];
            $settingsValue = $row['settings']->load();
            if (is_string($settingsValue)) {
                $settings = Util::json_decode($settingsValue, true);
                if (!is_array($settings)) {
                    $settings = [];
                }
            } else {
                $settings = [];
            }
            $tool->setSettings($settings);
            $tool->enabled = (intval($row['enabled']) === 1);
            $tool->enableFrom = null;
            if (!is_null($row['enable_from'])) {
                $tool->enableFrom = strtotime($row['enable_from']);
            }
            $tool->enableUntil = null;
            if (!is_null($row['enable_until'])) {
                $tool->enableUntil = strtotime($row['enable_until']);
            }
            $tool->lastAccess = null;
            if (!is_null($row['last_access'])) {
                $tool->lastAccess = strtotime($row['last_access']);
            }
            $tool->created = strtotime($row['created']);
            $tool->updated = strtotime($row['updated']);
            $this->fixToolSettings($tool, false);
            $ok = true;
        }

        return $ok;
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
        $id = $tool->getRecordId();
        $consumerKey = $tool->getKey();
        $enabled = ($tool->enabled) ? 1 : 0;
        $redirectionUrisValue = json_encode($tool->redirectionUris);
        $this->fixToolSettings($tool, true);
        $settingsValue = json_encode($tool->getSettings());
        $this->fixToolSettings($tool, false);
        $time = time();
        $now = date("{$this->dateFormat} {$this->timeFormat}", $time);
        $from = null;
        if (!is_null($tool->enableFrom)) {
            $from = date("{$this->dateFormat} {$this->timeFormat}", $tool->enableFrom);
        }
        $until = null;
        if (!is_null($tool->enableUntil)) {
            $until = date("{$this->dateFormat} {$this->timeFormat}", $tool->enableUntil);
        }
        $last = null;
        if (!is_null($tool->lastAccess)) {
            $last = date($this->dateFormat, $tool->lastAccess);
        }
        if (empty($id)) {
            $pk = null;
            $sql = <<< EOD
INSERT INTO {$this->dbTableName(static::TOOL_TABLE_NAME)} (
  name, consumer_key, secret, message_url, initiate_login_url, redirection_uris, public_key,
  lti_version, signature_method, settings, enabled, enable_from, enable_until, last_access, created, updated)
VALUES (:name, :key, :secret, :message_url, :initiate_login_url, :redirection_uris, :public_key,
  :lti_version, :signature_method, :settings, :enabled, :enable_from, :enable_until, :last_access, :created, :updated)
RETURNING tool_pk INTO :pk
EOD;
            $query = oci_parse($this->db, $sql);
            oci_bind_by_name($query, 'name', $tool->name);
            oci_bind_by_name($query, 'key', $consumerKey);
            oci_bind_by_name($query, 'secret', $tool->secret);
            oci_bind_by_name($query, 'message_url', $tool->messageUrl);
            oci_bind_by_name($query, 'initiate_login_url', $tool->initiateLoginUrl);
            oci_bind_by_name($query, 'redirection_uris', $redirectionUrisValue);
            oci_bind_by_name($query, 'public_key', $tool->rsaKey);
            oci_bind_by_name($query, 'lti_version', $tool->ltiVersion);
            oci_bind_by_name($query, 'signature_method', $tool->signatureMethod);
            oci_bind_by_name($query, 'settings', $settingsValue);
            oci_bind_by_name($query, 'enabled', $enabled);
            oci_bind_by_name($query, 'enable_from', $from);
            oci_bind_by_name($query, 'enable_until', $until);
            oci_bind_by_name($query, 'last_access', $last);
            oci_bind_by_name($query, 'created', $now);
            oci_bind_by_name($query, 'updated', $now);
            oci_bind_by_name($query, 'pk', $pk);
        } else {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::TOOL_TABLE_NAME)}
SET name = :name, consumer_key = :key, secret= :secret, message_url = :message_url, initiate_login_url = :initiate_login_url,
  redirection_uris = :redirection_uris, public_key = :public_key,
  lti_version = :lti_version, signature_method = :signature_method, settings = :settings,
  enabled = :enabled, enable_from = :enable_from, enable_until = :enable_until, last_access = :last_access, updated = :updated
WHERE (tool_pk = :id)
EOD;
            $query = oci_parse($this->db, $sql);
            oci_bind_by_name($query, 'name', $tool->name);
            oci_bind_by_name($query, 'key', $consumerKey);
            oci_bind_by_name($query, 'secret', $tool->secret);
            oci_bind_by_name($query, 'message_url', $tool->messageUrl);
            oci_bind_by_name($query, 'initiate_login_url', $tool->initiateLoginUrl);
            oci_bind_by_name($query, 'redirection_uris', $redirectionUrisValue);
            oci_bind_by_name($query, 'public_key', $tool->rsaKey);
            oci_bind_by_name($query, 'lti_version', $tool->ltiVersion);
            oci_bind_by_name($query, 'signature_method', $tool->signatureMethod);
            oci_bind_by_name($query, 'settings', $settingsValue);
            oci_bind_by_name($query, 'enabled', $enabled);
            oci_bind_by_name($query, 'enable_from', $from);
            oci_bind_by_name($query, 'enable_until', $until);
            oci_bind_by_name($query, 'last_access', $last);
            oci_bind_by_name($query, 'updated', $now);
            oci_bind_by_name($query, 'id', $id);
        }
        $ok = $this->executeQuery($sql, $query);
        if ($ok) {
            if (empty($id)) {
                $tool->setRecordId(intval($pk));
                $tool->created = $time;
            }
            $tool->updated = $time;
        }

        return $ok;
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
        $id = $tool->getRecordId();

        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::TOOL_TABLE_NAME)}
WHERE (tool_pk = :id)
EOD;
        $query = oci_parse($this->db, $sql);
        oci_bind_by_name($query, 'id', $id);
        $ok = $this->executeQuery($sql, $query);

        if ($ok) {
            $tool->initialize();
        }

        return $ok;
    }

    /**
     * Load tool objects.
     *
     * @return Tool[]  Array of all defined Tool objects
     */
    public function getTools(): array
    {
        $tools = [];

        $sql = <<< EOD
SELECT tool_pk, name, consumer_key, secret, message_url, initiate_login_url, redirection_uris, public_key,
  lti_version, signature_method, settings, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::TOOL_TABLE_NAME)}
ORDER BY name
EOD;
        $query = oci_parse($this->db, $sql);
        $ok = ($query !== false);

        if ($ok) {
            $ok = $this->executeQuery($sql, $query);
        }

        if ($ok) {
            while ($row = oci_fetch_assoc($query)) {
                $row = array_change_key_case($row);
                $tool = new Tool($this);
                $tool->setRecordId(intval($row['tool_pk']));
                $tool->name = $row['name'];
                $tool->setkey($row['consumer_key']);
                $tool->secret = $row['secret'];
                $tool->messageUrl = $row['message_url'];
                $tool->initiateLoginUrl = $row['initiate_login_url'];
                $redirectionUrisValue = $row['redirection_uris']->load();
                if (is_string($redirectionUrisValue)) {
                    $redirectionUris = Util::json_decode($redirectionUrisValue, true);
                    if (!is_array($redirectionUris)) {
                        $redirectionUris = [];
                    }
                } else {
                    $redirectionUris = [];
                }
                $tool->redirectionUris = $redirectionUris;
                $tool->rsaKey = $row['public_key'];
                $tool->ltiVersion = $row['lti_version'];
                $tool->signatureMethod = $row['signature_method'];
                $settingsValue = $row['settings']->load();
                if (is_string($settingsValue)) {
                    $settings = Util::json_decode($settingsValue, true);
                    if (!is_array($settings)) {
                        $settings = [];
                    }
                } else {
                    $settings = [];
                }
                $tool->setSettings($settings);
                $tool->enabled = (intval($row['enabled']) === 1);
                $tool->enableFrom = null;
                if (!is_null($row['enable_from'])) {
                    $tool->enableFrom = strtotime($row['enable_from']);
                }
                $tool->enableUntil = null;
                if (!is_null($row['enable_until'])) {
                    $tool->enableUntil = strtotime($row['enable_until']);
                }
                $tool->lastAccess = null;
                if (!is_null($row['last_access'])) {
                    $tool->lastAccess = strtotime($row['last_access']);
                }
                $tool->created = strtotime($row['created']);
                $tool->updated = strtotime($row['updated']);
                $this->fixToolSettings($tool, false);
                $tools[] = $tool;
            }
        }

        return $tools;
    }

###
###  Other methods
###

    /**
     * Execute a database query.
     *
     * Info and debug messages are generated.
     *
     * @param string   $sql          SQL statement
     * @param resource $query        SQL query
     * @param bool     $reportError  True if errors are to be reported
     *
     * @return bool  True if the query was successful
     */
    private function executeQuery(string $sql, mixed $query, bool $reportError = true): bool
    {
        $ok = @oci_execute($query);
        if (!$ok && $reportError) {
            Util::logError($sql . $this->errorInfoToString(oci_error($query)));
        } else {
            Util::logDebug($sql);
        }

        return $ok;
    }

    /**
     * Extract error information into a string.
     *
     * @param array $errorInfo  Array of error information
     *
     * @return string  Error message.
     */
    private function errorInfoToString(array $errorInfo): string
    {
        if (is_array($errorInfo) && !empty($errorInfo)) {
            $errors = PHP_EOL . "Error {$errorInfo['code']}: {$errorInfo['message']} (offset {$errorInfo['offset']})";
        } else {
            $errors = '';
        }

        return $errors;
    }

}
