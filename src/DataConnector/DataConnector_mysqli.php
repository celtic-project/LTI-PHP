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
use ceLTIc\LTI\Enum\LtiVersion;
use ceLTIc\LTI\Enum\IdScope;

/**
 * Class to represent an LTI Data Connector for MySQLi
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
###
#    NB This class assumes that a MySQLi connection has already been opened to the appropriate schema
###


class DataConnector_mysqli extends DataConnector
{
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
        $id = $platform->getRecordId();
        if (!is_null($id)) {
            $sql = <<< EOD
SELECT consumer_pk, name, consumer_key, secret, platform_id, client_id, deployment_id, public_key,
  lti_version, signature_method, consumer_name, consumer_version, consumer_guid, profile, tool_proxy, settings,
  protected, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::PLATFORM_TABLE_NAME)}
WHERE consumer_pk = ?
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $id);
        } elseif (!empty($platform->platformId)) {
            if (empty($platform->clientId)) {
                $allowMultiple = true;
                $sql = <<< EOD
SELECT consumer_pk, name, consumer_key, secret, platform_id, client_id, deployment_id, public_key,
  lti_version, signature_method, consumer_name, consumer_version, consumer_guid, profile, tool_proxy, settings,
  protected, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::PLATFORM_TABLE_NAME)}
WHERE (platform_id = ?)
EOD;
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('s', $platform->platformId);
            } elseif (empty($platform->deploymentId)) {
                $allowMultiple = true;
                $sql = <<< EOD
SELECT consumer_pk, name, consumer_key, secret, platform_id, client_id, deployment_id, public_key,
  lti_version, signature_method, consumer_name, consumer_version, consumer_guid, profile, tool_proxy, settings,
  protected, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::PLATFORM_TABLE_NAME)}
WHERE (platform_id = ?) AND (client_id = ?)
EOD;
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('ss', $platform->platformId, $platform->clientId);
            } else {
                $sql = <<< EOD
SELECT consumer_pk, name, consumer_key, secret, platform_id,client_id,  deployment_id, public_key,
  lti_version, signature_method, consumer_name, consumer_version, consumer_guid, profile, tool_proxy, settings,
  protected, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::PLATFORM_TABLE_NAME)}
WHERE (platform_id = ?) AND (client_id = ?) AND (deployment_id = ?)
EOD;
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('sss', $platform->platformId, $platform->clientId, $platform->deploymentId);
            }
        } else {
            $key = $platform->getKey();
            $sql = <<< EOD
SELECT consumer_pk, name, consumer_key, secret, platform_id, client_id, deployment_id, public_key,
  lti_version, signature_method, consumer_name, consumer_version, consumer_guid, profile, tool_proxy, settings,
  protected, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::PLATFORM_TABLE_NAME)}
WHERE consumer_key = ?
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('s', $key);
        }
        $ok = $this->executeQuery($sql, $stmt);
        if ($ok) {
            $rsConsumer = $stmt->get_result();
            $ok = $rsConsumer !== false;
            if ($ok) {
                $row = $rsConsumer->fetch_object();
                $ok = $row && ($allowMultiple || is_null($rsConsumer->fetch_object()));
            }
        }
        if ($ok) {
            $platform->setRecordId(intval($row->consumer_pk));
            $platform->name = $row->name;
            $platform->setkey($row->consumer_key);
            $platform->secret = $row->secret;
            $platform->platformId = $row->platform_id;
            $platform->clientId = $row->client_id;
            $platform->deploymentId = $row->deployment_id;
            $platform->rsaKey = $row->public_key;
            $platform->ltiVersion = LtiVersion::tryFrom($row->lti_version);
            $platform->signatureMethod = $row->signature_method;
            $platform->consumerName = $row->consumer_name;
            $platform->consumerVersion = $row->consumer_version;
            $platform->consumerGuid = $row->consumer_guid;
            $platform->profile = Util::json_decode($row->profile);
            $platform->toolProxy = $row->tool_proxy;
            $settings = Util::json_decode($row->settings, true);
            if (!is_array($settings)) {
                $settings = @unserialize($row->settings);  // check for old serialized setting
            }
            if (!is_array($settings)) {
                $settings = [];
            }
            $platform->setSettings($settings);
            $platform->protected = (intval($row->protected) === 1);
            $platform->enabled = (intval($row->enabled) === 1);
            $platform->enableFrom = null;
            if (!is_null($row->enable_from)) {
                $platform->enableFrom = strtotime($row->enable_from);
            }
            $platform->enableUntil = null;
            if (!is_null($row->enable_until)) {
                $platform->enableUntil = strtotime($row->enable_until);
            }
            $platform->lastAccess = null;
            if (!is_null($row->last_access)) {
                $platform->lastAccess = strtotime($row->last_access);
            }
            $platform->created = strtotime($row->created);
            $platform->updated = strtotime($row->updated);
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
        $key = $platform->getKey();
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
        $ltiVersion = $platform->ltiVersion ? $platform->ltiVersion->value : '';
        if (empty($id)) {
            $sql = <<< EOD
INSERT INTO {$this->dbTableName(static::PLATFORM_TABLE_NAME)} (
  consumer_key, name, secret, platform_id, client_id, deployment_id, public_key,
  lti_version, signature_method, consumer_name, consumer_version, consumer_guid, profile, tool_proxy, settings,
  protected, enabled, enable_from, enable_until, last_access, created, updated
)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('sssssssssssssssiisssss', $key, $platform->name, $platform->secret, $platform->platformId,
                $platform->clientId, $platform->deploymentId, $platform->rsaKey, $ltiVersion, $platform->signatureMethod,
                $platform->consumerName, $platform->consumerVersion, $platform->consumerGuid, $profile, $platform->toolProxy,
                $settingsValue, $protected, $enabled, $from, $until, $last, $now, $now);
        } else {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::PLATFORM_TABLE_NAME)} SET
  consumer_key = ?, name = ?, secret= ?, platform_id = ?, client_id = ?, deployment_id = ?, public_key = ?,
  lti_version = ?, signature_method = ?, consumer_name = ?, consumer_version = ?, consumer_guid = ?,
  profile = ?, tool_proxy = ?, settings = ?, protected = ?, enabled = ?, enable_from = ?, enable_until = ?,
  last_access = ?, updated = ?
WHERE consumer_pk = ?
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('sssssssssssssssiissssi', $key, $platform->name, $platform->secret, $platform->platformId,
                $platform->clientId, $platform->deploymentId, $platform->rsaKey, $ltiVersion, $platform->signatureMethod,
                $platform->consumerName, $platform->consumerVersion, $platform->consumerGuid, $profile, $platform->toolProxy,
                $settingsValue, $protected, $enabled, $from, $until, $last, $now, $id);
        }
        $ok = $this->executeQuery($sql, $stmt);
        if ($ok) {
            if (empty($id)) {
                $platform->setRecordId($this->db->insert_id);
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

// Delete any access token value for this consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::ACCESS_TOKEN_TABLE_NAME)} WHERE consumer_pk = ?
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $this->executeQuery($sql, $stmt);

// Delete any nonce values for this consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::NONCE_TABLE_NAME)} WHERE consumer_pk = ?
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $this->executeQuery($sql, $stmt);

// Delete any outstanding share keys for resource links for this consumer
        $sql = <<< EOD
DELETE sk
FROM {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)} sk
  INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl ON sk.resource_link_pk = rl.resource_link_pk
WHERE rl.consumer_pk = ?
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $this->executeQuery($sql, $stmt);

// Delete any outstanding share keys for resource links for contexts in this consumer
        $sql = <<< EOD
DELETE sk
FROM {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)} sk
  INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl ON sk.resource_link_pk = rl.resource_link_pk
  INNER JOIN {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c ON rl.context_pk = c.context_pk
WHERE c.consumer_pk = ?
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $this->executeQuery($sql, $stmt);

// Delete any users in resource links for this consumer
        $sql = <<< EOD
DELETE u
FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)} u
  INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl ON u.resource_link_pk = rl.resource_link_pk
WHERE rl.consumer_pk = ?
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $this->executeQuery($sql, $stmt);

// Delete any users in resource links for contexts in this consumer
        $sql = <<< EOD
DELETE u
FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)} u
  INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl ON u.resource_link_pk = rl.resource_link_pk
  INNER JOIN {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c ON rl.context_pk = c.context_pk
WHERE c.consumer_pk = ?
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $this->executeQuery($sql, $stmt);

// Update any resource links for which this consumer is acting as a primary resource link
        $sql = <<< EOD
UPDATE {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} prl
  INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl ON prl.primary_resource_link_pk = rl.resource_link_pk
SET prl.primary_resource_link_pk = NULL, prl.share_approved = NULL
WHERE rl.consumer_pk = ?
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $this->executeQuery($sql, $stmt);

// Update any resource links for contexts in which this consumer is acting as a primary resource link
        $sql = <<< EOD
UPDATE {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} prl
  INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl ON prl.primary_resource_link_pk = rl.resource_link_pk
  INNER JOIN {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c ON rl.context_pk = c.context_pk
SET prl.primary_resource_link_pk = NULL, prl.share_approved = NULL
WHERE c.consumer_pk = ?
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $this->executeQuery($sql, $stmt);

// Delete any resource links for this consumer
        $sql = <<< EOD
DELETE rl
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl
WHERE rl.consumer_pk = ?
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $this->executeQuery($sql, $stmt);

// Delete any resource links for contexts in this consumer
        $sql = <<< EOD
DELETE rl
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl
  INNER JOIN {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c ON rl.context_pk = c.context_pk
WHERE c.consumer_pk = ?
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $this->executeQuery($sql, $stmt);

// Delete any contexts for this consumer
        $sql = <<< EOD
DELETE c
FROM {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c
WHERE c.consumer_pk = ?
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $this->executeQuery($sql, $stmt);

// Delete consumer
        $sql = <<< EOD
DELETE c
FROM {$this->dbTableName(static::PLATFORM_TABLE_NAME)} c
WHERE c.consumer_pk = ?
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $ok = $this->executeQuery($sql, $stmt);

        if ($ok) {
            $platform->initialize();
        }

        return $ok;
    }

    /**
     * Load all platforms from the database.
     *
     * @return Platform[]  An array of the Platform objects
     */
    public function getPlatforms(): array
    {
        $platforms = [];

        $sql = <<< EOD
SELECT consumer_pk, consumer_key, name, secret, platform_id, client_id, deployment_id, public_key,
  lti_version, signature_method, consumer_name, consumer_version, consumer_guid, profile, tool_proxy, settings,
  protected, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::PLATFORM_TABLE_NAME)}
ORDER BY name
EOD;
        $stmt = $this->db->prepare($sql);
        if ($this->executeQuery($sql, $stmt)) {
            $rsConsumers = $stmt->get_result();
            while ($row = $rsConsumers->fetch_object()) {
                $platform = new Platform($this);
                $platform->setRecordId(intval($row->consumer_pk));
                $platform->name = $row->name;
                $platform->setKey($row->consumer_key);
                $platform->secret = $row->secret;
                $platform->platformId = $row->platform_id;
                $platform->clientId = $row->client_id;
                $platform->deploymentId = $row->deployment_id;
                $platform->rsaKey = $row->public_key;
                $platform->ltiVersion = LtiVersion::tryFrom($row->lti_version);
                $platform->signatureMethod = $row->signature_method;
                $platform->consumerName = $row->consumer_name;
                $platform->consumerVersion = $row->consumer_version;
                $platform->consumerGuid = $row->consumer_guid;
                $platform->profile = Util::json_decode($row->profile);
                $platform->toolProxy = $row->tool_proxy;
                $settings = Util::json_decode($row->settings, true);
                if (!is_array($settings)) {
                    $settings = @unserialize($row->settings);  // check for old serialized setting
                }
                if (!is_array($settings)) {
                    $settings = [];
                }
                $platform->setSettings($settings);
                $platform->protected = (intval($row->protected) === 1);
                $platform->enabled = (intval($row->enabled) === 1);
                $platform->enableFrom = null;
                if (!is_null($row->enable_from)) {
                    $platform->enableFrom = strtotime($row->enable_from);
                }
                $platform->enableUntil = null;
                if (!is_null($row->enable_until)) {
                    $platform->enableUntil = strtotime($row->enable_until);
                }
                $platform->lastAccess = null;
                if (!is_null($row->last_access)) {
                    $platform->lastAccess = strtotime($row->last_access);
                }
                $platform->created = strtotime($row->created);
                $platform->updated = strtotime($row->updated);
                $this->fixPlatformSettings($platform, false);
                $platforms[] = $platform;
            }
            $rsConsumers->free_result();
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
        $id = $context->getRecordId();
        if (!is_null($id)) {
            $sql = <<< EOD
SELECT context_pk, consumer_pk, title, lti_context_id, type, settings, created, updated
FROM {$this->dbTableName(static::CONTEXT_TABLE_NAME)}
WHERE (context_pk = ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $id);
        } else {
            $id = $context->getPlatform()->getRecordId();
            $sql = <<< EOD
SELECT context_pk, consumer_pk, title, lti_context_id, type, settings, created, updated
FROM {$this->dbTableName(static::CONTEXT_TABLE_NAME)}
WHERE (consumer_pk = ?) AND (lti_context_id = ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('is', $id, $context->ltiContextId);
        }
        $ok = $this->executeQuery($sql, $stmt);
        if ($ok) {
            $rsContext = $stmt->get_result();
            $row = $rsContext->fetch_object();
            if ($row) {
                $context->setRecordId(intval($row->context_pk));
                $context->setPlatformId(intval($row->consumer_pk));
                $context->title = $row->title;
                $context->ltiContextId = $row->lti_context_id;
                $context->type = $row->type;
                $settings = Util::json_decode($row->settings, true);
                if (!is_array($settings)) {
                    $settings = @unserialize($row->settings);  // check for old serialized setting
                }
                if (!is_array($settings)) {
                    $settings = [];
                }
                $context->setSettings($settings);
                $context->created = strtotime($row->created);
                $context->updated = strtotime($row->updated);
            } else {
                $ok = false;
            }
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
            $sql = <<< EOD
INSERT INTO {$this->dbTableName(static::CONTEXT_TABLE_NAME)} (
  consumer_pk, title, lti_context_id, type, settings, created, updated)
VALUES (?, ?, ?, ?, ?, ?, ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('issssss', $consumer_pk, $context->title, $context->ltiContextId, $context->type, $settingsValue,
                $now, $now);
        } else {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::CONTEXT_TABLE_NAME)} SET
  title = ?, lti_context_id = ?, type = ?, settings = ?, updated = ?
WHERE (consumer_pk = ?) AND (context_pk = ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('sssssii', $context->title, $context->ltiContextId, $context->type, $settingsValue, $now,
                $consumer_pk, $id);
        }
        $ok = $this->executeQuery($sql, $stmt);
        if ($ok) {
            if (empty($id)) {
                $context->setRecordId($this->db->insert_id);
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
DELETE sk
FROM {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)} sk
  INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl ON (sk.resource_link_pk = rl.resource_link_pk)
WHERE rl.context_pk = ?
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $this->executeQuery($sql, $stmt);

// Delete any users in resource links for this context
        $sql = <<< EOD
DELETE u
FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)} u
  INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl ON (u.resource_link_pk = rl.resource_link_pk)
WHERE (rl.context_pk = ?)
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $this->executeQuery($sql, $stmt);

// Update any resource links for which this context is acting as a primary resource link
        $sql = <<< EOD
UPDATE {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} prl
  INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl ON (prl.primary_resource_link_pk = rl.resource_link_pk)
SET prl.primary_resource_link_pk = NULL, prl.share_approved = NULL
WHERE (rl.context_pk = ?)
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $this->executeQuery($sql, $stmt);

// Delete any resource links for this context
        $sql = <<< EOD
DELETE rl
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl
WHERE (rl.context_pk = ?)
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $this->executeQuery($sql, $stmt);

// Delete context
        $sql = <<< EOD
DELETE c
FROM {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c
WHERE (c.context_pk = ?)
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $ok = $this->executeQuery($sql, $stmt);

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
        $id = $resourceLink->getRecordId();
        if (!is_null($id)) {
            $sql = <<< EOD
SELECT resource_link_pk, context_pk, consumer_pk, title, lti_resource_link_id, settings,
  primary_resource_link_pk, share_approved, created, updated
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
WHERE (resource_link_pk = ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $id);
        } elseif (!is_null($resourceLink->getContext())) {
            $rid = $resourceLink->getId();
            $cid = $resourceLink->getContext()->getRecordId();
            $sql = <<< EOD
SELECT r.resource_link_pk, r.context_pk, r.consumer_pk, r.title, r.lti_resource_link_id, r.settings,
  r.primary_resource_link_pk, r.share_approved, r.created, r.updated
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} r
WHERE (r.lti_resource_link_id = ?) AND ((r.context_pk = ?) OR (r.consumer_pk IN (
  SELECT c.consumer_pk
  FROM {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c
  WHERE (c.context_pk = ?)
)))
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('sii', $rid, $cid, $cid);
        } else {
            $id = $resourceLink->getPlatform()->getRecordId();
            $rid = $resourceLink->getId();
            $sql = <<< EOD
SELECT r.resource_link_pk, r.context_pk, r.consumer_pk, r.title, r.lti_resource_link_id, r.settings,
  r.primary_resource_link_pk, r.share_approved, r.created, r.updated
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} r
  LEFT OUTER JOIN {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c ON r.context_pk = c.context_pk
WHERE ((r.consumer_pk = ?) OR (c.consumer_pk = ?)) AND (lti_resource_link_id = ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('iis', $id, $id, $rid);
        }
        $ok = $this->executeQuery($sql, $stmt);
        if ($ok) {
            $rsResourceLink = $stmt->get_result();
            $row = $rsResourceLink->fetch_object();
            if ($row) {
                $resourceLink->setRecordId(intval($row->resource_link_pk));
                if (!is_null($row->context_pk)) {
                    $resourceLink->setContextId(intval($row->context_pk));
                } else {
                    $resourceLink->setContextId(null);
                }
                if (!is_null($row->consumer_pk)) {
                    $resourceLink->setPlatformId(intval($row->consumer_pk));
                } else {
                    $resourceLink->setPlatformId(null);
                }
                $resourceLink->title = $row->title;
                $resourceLink->ltiResourceLinkId = $row->lti_resource_link_id;
                $settings = Util::json_decode($row->settings, true);
                if (!is_array($settings)) {
                    $settings = @unserialize($row->settings);  // check for old serialized setting
                }
                if (!is_array($settings)) {
                    $settings = [];
                }
                $resourceLink->setSettings($settings);
                if (!is_null($row->primary_resource_link_pk)) {
                    $resourceLink->primaryResourceLinkId = intval($row->primary_resource_link_pk);
                } else {
                    $resourceLink->primaryResourceLinkId = null;
                }
                $resourceLink->shareApproved = (is_null($row->share_approved)) ? null : (intval($row->share_approved) === 1);
                $resourceLink->created = strtotime($row->created);
                $resourceLink->updated = strtotime($row->updated);
            } else {
                $ok = false;
            }
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
        if (empty($resourceLink->primaryResourceLinkId)) {
            $primaryResourceLinkId = null;
        } else {
            $primaryResourceLinkId = strval($resourceLink->primaryResourceLinkId);
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
        $id = $resourceLink->getRecordId();
        $rid = $resourceLink->getId();
        if (empty($id)) {
            $sql = <<< EOD
INSERT INTO {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} (
  consumer_pk, context_pk, title, lti_resource_link_id, settings,
  primary_resource_link_pk, share_approved, created, updated)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ssssssiss', $consumerId, $contextId, $resourceLink->title, $rid, $settingsValue,
                $primaryResourceLinkId, $approved, $now, $now);
        } elseif (!is_null($contextId)) {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} SET
  consumer_pk = ?, title = ?, lti_resource_link_id = ?, settings = ?,
  primary_resource_link_pk = ?, share_approved = ?, updated = ?
WHERE (context_pk = ?) AND (resource_link_pk = ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('sssssisii', $consumerId, $resourceLink->title, $rid, $settingsValue, $primaryResourceLinkId,
                $approved, $now, $contextId, $id);
        } else {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} SET
  context_pk = NULL, title = ?, lti_resource_link_id = ?, settings = ?,
  primary_resource_link_pk = ?, share_approved = ?, updated = ?
WHERE (consumer_pk = ?) AND (resource_link_pk = ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('sssiisii', $resourceLink->title, $rid, $settingsValue, $primaryResourceLinkId, $approved, $now,
                $consumerId, $id);
        }
        $ok = $this->executeQuery($sql, $stmt);
        if ($ok) {
            if (empty($id)) {
                $resourceLink->setRecordId($this->db->insert_id);
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
WHERE (resource_link_pk = ?)
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $ok = $this->executeQuery($sql, $stmt);

// Delete users
        if ($ok) {
            $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)}
WHERE (resource_link_pk = ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $id);
            $ok = $this->executeQuery($sql, $stmt);
        }

// Update any resource links for which this is the primary resource link
        if ($ok) {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
SET primary_resource_link_pk = NULL
WHERE (primary_resource_link_pk = ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $id);
            $ok = $this->executeQuery($sql, $stmt);
        }

// Delete resource link
        if ($ok) {
            $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
WHERE (resource_link_pk = ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $id);
            $ok = $this->executeQuery($sql, $stmt);
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
     * @return UserResult[]  Array of UserResult objects
     */
    public function getUserResultSourcedIDsResourceLink(ResourceLink $resourceLink, bool $localOnly, ?IdScope $idScope): array
    {
        $userResults = [];

        $id = $resourceLink->getRecordId();
        if ($localOnly) {
            $sql = <<< EOD
SELECT u.user_result_pk, u.lti_result_sourcedid, u.lti_user_id, u.created, u.updated
FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)} AS u
  INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} AS rl ON (u.resource_link_pk = rl.resource_link_pk)
WHERE (rl.resource_link_pk = ?) AND (rl.primary_resource_link_pk IS NULL)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $id);
        } else {
            $sql = <<< EOD
SELECT u.user_result_pk, u.lti_result_sourcedid, u.lti_user_id, u.created, u.updated
FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)} AS u
  INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} AS rl ON (u.resource_link_pk = rl.resource_link_pk)
WHERE ((rl.resource_link_pk = ?) AND (rl.primary_resource_link_pk IS NULL)) OR
  ((rl.primary_resource_link_pk = ?) AND (share_approved = 1))
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ii', $id, $id);
        }
        if ($this->executeQuery($sql, $stmt)) {
            $rsUser = $stmt->get_result();
            while ($row = $rsUser->fetch_object()) {
                $userResult = LTI\UserResult::fromResourceLink($resourceLink, $row->lti_user_id);
                if (is_null($idScope)) {
                    $userResults[] = $userResult;
                } else {
                    $userResults[$userResult->getId($idScope)] = $userResult;
                }
            }
            $rsUser->free_result();
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
        $shares = [];

        $id = $resourceLink->getRecordId();
        $sql = <<< EOD
SELECT c.consumer_name, r.resource_link_pk, r.title, r.share_approved
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} AS r
  INNER JOIN {$this->dbTableName(static::PLATFORM_TABLE_NAME)} AS c ON (r.consumer_pk = c.consumer_pk)
WHERE (r.primary_resource_link_pk = ?)
UNION
SELECT c2.consumer_name, r2.resource_link_pk, r2.title, r2.share_approved
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} AS r2
  INNER JOIN {$this->dbTableName(static::CONTEXT_TABLE_NAME)} AS x ON (r2.context_pk = x.context_pk)
  INNER JOIN {$this->dbTableName(static::PLATFORM_TABLE_NAME)} AS c2 ON (x.consumer_pk = c2.consumer_pk)
WHERE (r2.primary_resource_link_pk = ?)
ORDER BY consumer_name, title
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $id, $id);
        if ($this->executeQuery($sql, $stmt)) {
            $rsShare = $stmt->get_result();
            while ($row = $rsShare->fetch_object()) {
                $share = new LTI\ResourceLinkShare();
                $share->consumerName = $row->consumer_name;
                $share->resourceLinkId = intval($row->resource_link_pk);
                $share->title = $row->title;
                $share->approved = (intval($row->share_approved) === 1);
                $shares[] = $share;
            }
            $rsShare->free_result();
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
WHERE (expires <= ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('s', $now);
            $this->executeQuery($sql, $stmt);

// Load the nonce
            $id = $nonce->getPlatform()->getRecordId();
            $value = $nonce->getValue();
            $sql = <<< EOD
SELECT value AS T
FROM {$this->dbTableName(static::NONCE_TABLE_NAME)}
WHERE (consumer_pk = ?) AND (value = ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('is', $id, $value);
            $ok = $this->executeQuery($sql, $stmt);
            if ($ok) {
                $rsNonce = $stmt->get_result();
                if (!$rsNonce->fetch_object()) {
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
VALUES (?, ?, ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('iss', $id, $value, $expires);
            $ok = $this->executeQuery($sql, $stmt);
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
WHERE (consumer_pk = ?) AND (value = ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $id = $nonce->getPlatform()->getRecordId();
            $value = $nonce->getValue();
            $stmt->bind_param('is', $id, $value);
            $ok = $this->executeQuery($sql, $stmt);
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
            $consumer_pk = $accessToken->getPlatform()->getRecordId();
            $sql = <<< EOD
SELECT scopes, token, expires, created, updated
FROM {$this->dbTableName(static::ACCESS_TOKEN_TABLE_NAME)}
WHERE (consumer_pk = ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $consumer_pk);
            $ok = $this->executeQuery($sql, $stmt);
            if ($ok) {
                $rsAccessToken = $stmt->get_result();
                $row = $rsAccessToken->fetch_object();
                if ($row) {
                    $scopes = Util::json_decode($row->scopes, true);
                    if (!is_array($scopes)) {
                        $scopes = [];
                    }
                    $accessToken->scopes = $scopes;
                    $accessToken->token = $row->token;
                    $accessToken->expires = strtotime($row->expires);
                    $accessToken->created = strtotime($row->created);
                    $accessToken->updated = strtotime($row->updated);
                } else {
                    $ok = false;
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
VALUES (?, ?, ?, ?, ?, ?)
EOD;
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('isssss', $consumer_pk, $scopes, $token, $expires, $now, $now);
            } else {
                $sql = <<< EOD
UPDATE {$this->dbTableName(static::ACCESS_TOKEN_TABLE_NAME)}
SET scopes = ?, token = ?, expires = ?, updated = ?
WHERE (consumer_pk = ?)
EOD;
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('ssssi', $scopes, $token, $expires, $now, $consumer_pk);
            }
            $ok = $this->executeQuery($sql, $stmt);
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
// Clear expired share keys
        $now = date("{$this->dateFormat} {$this->timeFormat}", time());
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)}
WHERE (expires <= ?)
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $now);
        $this->executeQuery($sql, $stmt);
// Load share key
        $id = $shareKey->getId();
        $sql = <<< EOD
SELECT resource_link_pk, auto_approve, expires
FROM {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)}
WHERE (share_key_id = ?)
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $id);
        $ok = $this->executeQuery($sql, $stmt);
        if ($ok) {
            $rsShareKey = $stmt->get_result();
            $row = $rsShareKey->fetch_object();
            if ($row) {
                $shareKey->resourceLinkId = intval($row->resource_link_pk);
                $shareKey->autoApprove = (intval($row->auto_approve) === 1);
                $shareKey->expires = strtotime($row->expires);
            } else {
                $ok = false;
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
        $id = $shareKey->getId();
        if ($shareKey->autoApprove) {
            $approve = 1;
        } else {
            $approve = 0;
        }
        $expires = date("{$this->dateFormat} {$this->timeFormat}", $shareKey->expires);
        $sql = <<< EOD
INSERT INTO {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)} (
  share_key_id, resource_link_pk, auto_approve, expires)
VALUES (?, ?, ?, ?)
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssis', $id, $shareKey->resourceLinkId, $approve, $expires);
        $ok = $this->executeQuery($sql, $stmt);

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
WHERE (share_key_id = ?)
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $id);
        $ok = $this->executeQuery($sql, $stmt);

        if ($ok) {
            $shareKey->initialize();
        }

        return $ok;
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
        $id = $userResult->getRecordId();
        if (!is_null($id)) {
            $sql = <<< EOD
SELECT user_result_pk, resource_link_pk, lti_user_id, lti_result_sourcedid, created, updated
FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)}
WHERE (user_result_pk = ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $id);
        } else {
            $rid = $userResult->getResourceLink()->getRecordId();
            $uid = $userResult->getId(LTI\Enum\IdScope::IdOnly);
            $sql = <<< EOD
SELECT user_result_pk, resource_link_pk, lti_user_id, lti_result_sourcedid, created, updated
FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)}
WHERE (resource_link_pk = ?) AND (lti_user_id = ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('is', $rid, $uid);
        }
        $ok = $this->executeQuery($sql, $stmt);
        if ($ok) {
            $rsUserResult = $stmt->get_result();
            $row = $rsUserResult->fetch_object();
            if ($row) {
                $userResult->setRecordId(intval($row->user_result_pk));
                $userResult->setResourceLinkId(intval($row->resource_link_pk));
                $userResult->ltiUserId = $row->lti_user_id;
                $userResult->ltiResultSourcedId = $row->lti_result_sourcedid;
                $userResult->created = strtotime($row->created);
                $userResult->updated = strtotime($row->updated);
            } else {
                $ok = false;
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
            $rid = $userResult->getResourceLink()->getRecordId();
            $uid = $userResult->getId(LTI\Enum\IdScope::IdOnly);
            $sql = <<< EOD
INSERT INTO {$this->dbTableName(static::USER_RESULT_TABLE_NAME)} (
  resource_link_pk, lti_user_id, lti_result_sourcedid, created, updated)
VALUES (?, ?, ?, ?, ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('issss', $rid, $uid, $userResult->ltiResultSourcedId, $now, $now);
        } else {
            $id = $userResult->getRecordId();
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::USER_RESULT_TABLE_NAME)}
SET lti_result_sourcedid = ?, updated = ?
WHERE (user_result_pk = ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ssi', $userResult->ltiResultSourcedId, $now, $id);
        }
        $ok = $this->executeQuery($sql, $stmt);
        if ($ok) {
            if (is_null($userResult->created)) {
                $userResult->setRecordId(mysqli_insert_id($this->db));
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
        $id = $userResult->getRecordId();
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)}
WHERE (user_result_pk = ?)
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $ok = $this->executeQuery($sql, $stmt);

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
        $id = $tool->getRecordId();
        if (!is_null($id)) {
            $sql = <<< EOD
SELECT tool_pk, name, consumer_key, secret, message_url, initiate_login_url, redirection_uris, public_key,
  lti_version, signature_method, settings, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::TOOL_TABLE_NAME)}
WHERE (tool_pk = ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $id);
        } elseif (!empty($tool->initiateLoginUrl)) {
            $sql = <<< EOD
SELECT tool_pk, name, consumer_key, secret, message_url, initiate_login_url, redirection_uris, public_key,
  lti_version, signature_method, settings, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::TOOL_TABLE_NAME)}
WHERE (initiate_login_url = ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('s', $tool->initiateLoginUrl);
        } else {
            $key = $tool->getKey();
            $sql = <<< EOD
SELECT tool_pk, name, consumer_key, secret, message_url, initiate_login_url, redirection_uris, public_key,
  lti_version, signature_method, settings, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::TOOL_TABLE_NAME)}
WHERE (consumer_key = ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('s', $key);
        }
        $ok = $this->executeQuery($sql, $stmt);
        if ($ok) {
            $rsTool = $stmt->get_result();
            $row = $rsTool->fetch_object();
            if ($row) {
                $tool->setRecordId(intval($row->tool_pk));
                $tool->name = $row->name;
                $tool->setkey($row->consumer_key);
                $tool->secret = $row->secret;
                $tool->messageUrl = $row->message_url;
                $tool->initiateLoginUrl = $row->initiate_login_url;
                $tool->redirectionUris = Util::json_decode($row->redirection_uris, true);
                if (!is_array($tool->redirectionUris)) {
                    $tool->redirectionUris = [];
                }
                $tool->rsaKey = $row->public_key;
                $tool->ltiVersion = LtiVersion::tryFrom($row->lti_version);
                $tool->signatureMethod = $row->signature_method;
                $settings = Util::json_decode($row->settings, true);
                if (!is_array($settings)) {
                    $settings = [];
                }
                $tool->setSettings($settings);
                $tool->enabled = (intval($row->enabled) === 1);
                $tool->enableFrom = null;
                if (!is_null($row->enable_from)) {
                    $tool->enableFrom = strtotime($row->enable_from);
                }
                $tool->enableUntil = null;
                if (!is_null($row->enable_until)) {
                    $tool->enableUntil = strtotime($row->enable_until);
                }
                $tool->lastAccess = null;
                if (!is_null($row->last_access)) {
                    $tool->lastAccess = strtotime($row->last_access);
                }
                $tool->created = strtotime($row->created);
                $tool->updated = strtotime($row->updated);
                $this->fixToolSettings($tool, false);
            } else {
                $ok = false;
            }
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
        $key = $tool->getKey();
        $ltiVersion = $tool->ltiVersion ? $tool->ltiVersion->value : '';
        if (empty($id)) {
            $sql = <<< EOD
INSERT INTO {$this->dbTableName(static::TOOL_TABLE_NAME)} (
  name, consumer_key, secret, message_url, initiate_login_url, redirection_uris, public_key,
  lti_version, signature_method, settings, enabled, enable_from, enable_until, last_access, created, updated)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ssssssssssisssss', $tool->name, $key, $tool->secret, $tool->messageUrl, $tool->initiateLoginUrl,
                $redirectionUrisValue, $tool->rsaKey, $ltiVersion, $tool->signatureMethod, $settingsValue, $enabled, $from, $until,
                $last, $now, $now);
        } else {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::TOOL_TABLE_NAME)} SET
  name = ?, consumer_key = ?, secret= ?, message_url = ?, initiate_login_url = ?, redirection_uris = ?, public_key = ?,
  lti_version = ?, signature_method = ?, settings = ?, enabled = ?, enable_from = ?, enable_until = ?, last_access = ?, updated = ?
WHERE (tool_pk = ?)
EOD;
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ssssssssssissssi', $tool->name, $key, $tool->secret, $tool->messageUrl, $tool->initiateLoginUrl,
                $redirectionUrisValue, $tool->rsaKey, $ltiVersion, $tool->signatureMethod, $settingsValue, $enabled, $from, $until,
                $last, $now, $id);
        }
        $ok = $this->executeQuery($sql, $stmt);
        if ($ok) {
            if (empty($id)) {
                $tool->setRecordId($this->db->insert_id);
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
DELETE t
FROM {$this->dbTableName(static::TOOL_TABLE_NAME)} t
WHERE (t.tool_pk = ?)
EOD;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $ok = $this->executeQuery($sql, $stmt);

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
        $stmt = $this->db->prepare($sql);
        if ($this->executeQuery($sql, $stmt)) {
            $rsTools = $stmt->get_result();
            while ($row = $rsTools->fetch_object()) {
                $tool = new Tool($this);
                $tool->setRecordId(intval($row->tool_pk));
                $tool->name = $row->name;
                $tool->setkey($row->consumer_key);
                $tool->secret = $row->secret;
                $tool->messageUrl = $row->message_url;
                $tool->initiateLoginUrl = $row->initiate_login_url;
                $tool->redirectionUris = Util::json_decode($row->redirection_uris, true);
                if (!is_array($tool->redirectionUris)) {
                    $tool->redirectionUris = [];
                }
                $tool->rsaKey = $row->public_key;
                $tool->ltiVersion = LtiVersion::tryFrom($row->lti_version);
                $tool->signatureMethod = $row->signature_method;
                $settings = Util::json_decode($row->settings, true);
                if (!is_array($settings)) {
                    $settings = [];
                }
                $tool->setSettings($settings);
                $tool->enabled = (intval($row->enabled) === 1);
                $tool->enableFrom = null;
                if (!is_null($row->enable_from)) {
                    $tool->enableFrom = strtotime($row->enable_from);
                }
                $tool->enableUntil = null;
                if (!is_null($row->enable_until)) {
                    $tool->enableUntil = strtotime($row->enable_until);
                }
                $tool->lastAccess = null;
                if (!is_null($row->last_access)) {
                    $tool->lastAccess = strtotime($row->last_access);
                }
                $tool->created = strtotime($row->created);
                $tool->updated = strtotime($row->updated);
                $this->fixToolSettings($tool, false);
                $tools[] = $tool;
            }
            $rsTools->free_result();
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
     * @param string $sql          SQL statement
     * @param object $stmt         MySQLi statement
     * @param bool   $reportError  True if errors are to be reported
     *
     * @return bool  True if the query was successful.
     */
    private function executeQuery(string $sql, object $stmt, bool $reportError = true): bool
    {
        try {
            $ok = $stmt->execute();
        } catch (\mysqli_sql_exception $e) {
            $ok = false;
        }
        $info = $this->db->info;
        if (!empty($info)) {
            $info = PHP_EOL . $info;
        }
        if (!$ok && $reportError) {
            Util::logError($sql . $info . $this->errorListToString($stmt->error_list));
        } else {
            Util::logDebug($sql . $info);
        }

        return $ok;
    }

    /**
     * Extract error information into a string.
     *
     * @param array $errorList  Array of error information
     *
     * @return string  Error message.
     */
    private function errorListToString(array $errorList): string
    {
        $errors = '';
        if (is_array($errorList) && !empty($errorList)) {
            if (count($errorList) <= 1) {
                $sep = 'Error ';
            } else {
                $sep = 'Errors:' . PHP_EOL . '  ';
            }
            foreach ($errorList as $error) {
                $errors .= PHP_EOL . "{$sep}{$error['errno']}/{$error['sqlstate']}: {$error['error']}";
                $sep = '  ';
            }
        }

        return $errors;
    }

}
