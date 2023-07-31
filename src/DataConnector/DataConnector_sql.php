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
 * Class to represent an LTI Data Connector for MS SQL Server
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
###
#    NB This class assumes that a MS SQL Server connection has already been opened to the appropriate schema
###


class DataConnector_sql extends DataConnector
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
        $ok = false;
        $allowMultiple = false;
        if (!is_null($platform->getRecordId())) {
            $sql = <<< EOD
SELECT consumer_pk, name, consumer_key, secret, platform_id, client_id, deployment_id, public_key,
  lti_version, signature_method, consumer_name, consumer_version, consumer_guid, profile, tool_proxy, settings,
  protected, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::PLATFORM_TABLE_NAME)}
WHERE (consumer_pk = ?)
EOD;
            $params = [
                $platform->getRecordId()
            ];
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
                $params = [
                    $platform->platformId
                ];
            } elseif (empty($platform->deploymentId)) {
                $allowMultiple = true;
                $sql = <<< EOD
SELECT consumer_pk, name, consumer_key, secret, platform_id, client_id, deployment_id, public_key,
  lti_version, signature_method, consumer_name, consumer_version, consumer_guid, profile, tool_proxy, settings,
  protected, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::PLATFORM_TABLE_NAME)}
WHERE (platform_id = ?) AND (client_id = ?)
EOD;
                $params = [
                    $platform->platformId,
                    $platform->clientId
                ];
            } else {
                $sql = <<< EOD
SELECT consumer_pk, name, consumer_key, secret, platform_id, client_id, deployment_id, public_key,
  lti_version, signature_method, consumer_name, consumer_version, consumer_guid, profile, tool_proxy, settings,
  protected, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::PLATFORM_TABLE_NAME)}
WHERE (platform_id = ?) AND (client_id = ?) AND (deployment_id = ?)
EOD;
                $params = [
                    $platform->platformId,
                    $platform->clientId,
                    $platform->deploymentId
                ];
            }
        } else {
            $sql = <<< EOD
SELECT consumer_pk, name, consumer_key, secret, platform_id, client_id, deployment_id, public_key,
  lti_version, signature_method, consumer_name, consumer_version, consumer_guid, profile, tool_proxy, settings,
  protected, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::PLATFORM_TABLE_NAME)}
WHERE (consumer_key = ?)
EOD;
            $params = [
                $platform->getKey()
            ];
        }
        $rsConsumer = $this->executeQuery($sql, $params);
        if ($rsConsumer) {
            $row = sqlsrv_fetch_object($rsConsumer);
            if ($row && ($allowMultiple || !sqlsrv_fetch_object($rsConsumer))) {
                $platform->setRecordId(intval($row->consumer_pk));
                $platform->name = $row->name;
                $platform->setkey($row->consumer_key);
                $platform->secret = $row->secret;
                $platform->platformId = $row->platform_id;
                $platform->clientId = $row->client_id;
                $platform->deploymentId = $row->deployment_id;
                $platform->rsaKey = $row->public_key;
                $platform->ltiVersion = LtiVersion::tryFrom($row->lti_version ?? '');
                $platform->signatureMethod = $row->signature_method;
                $platform->consumerName = $row->consumer_name;
                $platform->consumerVersion = $row->consumer_version;
                $platform->consumerGuid = $row->consumer_guid;
                $platform->profile = Util::jsonDecode($row->profile);
                $platform->toolProxy = $row->tool_proxy;
                $settings = Util::jsonDecode($row->settings, true);
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
                    $platform->enableFrom = date_timestamp_get($row->enable_from);
                }
                $platform->enableUntil = null;
                if (!is_null($row->enable_until)) {
                    $platform->enableUntil = date_timestamp_get($row->enable_until);
                }
                $platform->lastAccess = null;
                if (!is_null($row->last_access)) {
                    $platform->lastAccess = date_timestamp_get($row->last_access);
                }
                $platform->created = date_timestamp_get($row->created);
                $platform->updated = date_timestamp_get($row->updated);
                $this->fixPlatformSettings($platform, false);
                $ok = true;
            }
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
        $profile = (!empty($platform->profile)) ? json_encode($platform->profile) : null;
        $this->fixPlatformSettings($platform, true);
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
            $sql = <<< EOD
INSERT INTO {$this->dbTableName(static::PLATFORM_TABLE_NAME)} (
  consumer_key, name, secret, platform_id, client_id, deployment_id, public_key,
  lti_version, signature_method, consumer_name, consumer_version, consumer_guid, profile, tool_proxy, settings,
  protected, enabled, enable_from, enable_until, last_access, created, updated)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
EOD;
            $params = [
                $platform->getKey(),
                $platform->name,
                $platform->secret,
                $platform->platformId,
                $platform->clientId,
                $platform->deploymentId,
                $platform->rsaKey,
                $platform->ltiVersion ? $platform->ltiVersion->value : '',
                $platform->signatureMethod,
                $platform->consumerName,
                $platform->consumerVersion,
                $platform->consumerGuid,
                $profile,
                $platform->toolProxy,
                json_encode($platform->getSettings()),
                $platform->protected,
                $platform->enabled,
                $from,
                $until,
                $last,
                $now,
                $now
            ];
        } else {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::PLATFORM_TABLE_NAME)}
SET consumer_key = ?, name = ?, secret= ?, platform_id = ?, client_id = ?, deployment_id = ?, public_key = ?,
  lti_version = ?, signature_method = ?, consumer_name = ?, consumer_version = ?, consumer_guid = ?, profile = ?, tool_proxy = ?, settings = ?,
  protected = ?, enabled = ?, enable_from = ?, enable_until = ?, last_access = ?, updated = ?
WHERE (consumer_pk = ?)
EOD;
            $params = [
                $platform->getKey(),
                $platform->name,
                $platform->secret,
                $platform->platformId,
                $platform->clientId,
                $platform->deploymentId,
                $platform->rsaKey,
                $platform->ltiVersion ? $platform->ltiVersion->value : '',
                $platform->signatureMethod,
                $platform->consumerName,
                $platform->consumerVersion,
                $platform->consumerGuid,
                $profile,
                $platform->toolProxy,
                json_encode($platform->getSettings()),
                $platform->protected,
                $platform->enabled,
                $from,
                $until,
                $last,
                $now,
                $platform->getRecordId()
            ];
        }
        $ok = $this->executeQuery($sql, $params) !== false;
        if ($ok) {
            if (empty($id)) {
                $platform->setRecordId($this->insert_id());
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
// Delete any access token value for this consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::ACCESS_TOKEN_TABLE_NAME)}
WHERE (consumer_pk = ?)
EOD;
        $params = [
            $platform->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Delete any nonce values for this consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::NONCE_TABLE_NAME)}
WHERE (consumer_pk = ?)
EOD;
        $params = [
            $platform->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Delete any outstanding share keys for resource links for this consumer
        $sql = <<< EOD
DELETE sk
FROM {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)} sk
  INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl ON (sk.resource_link_pk = rl.resource_link_pk)
WHERE (rl.consumer_pk = ?)
EOD;
        $params = [
            $platform->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Delete any outstanding share keys for resource links for contexts in this consumer
        $sql = <<< EOD
DELETE sk
FROM {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)} sk
  INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl ON (sk.resource_link_pk = rl.resource_link_pk)
  INNER JOIN {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c ON (rl.context_pk = c.context_pk)
WHERE (c.consumer_pk = ?)
EOD;
        $params = [
            $platform->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Delete any users in resource links for this consumer
        $sql = <<< EOD
DELETE u
FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)} u
  INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl ON (u.resource_link_pk = rl.resource_link_pk)
WHERE (rl.consumer_pk = ?)
EOD;
        $params = [
            $platform->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Delete any users in resource links for contexts in this consumer
        $sql = <<< EOD
DELETE u
FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)} u
  INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl ON (u.resource_link_pk = rl.resource_link_pk)
  INNER JOIN {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c ON (rl.context_pk = c.context_pk)
WHERE (c.consumer_pk = ?)
EOD;
        $params = [
            $platform->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Update any resource links for which this consumer is acting as a primary resource link
        $sql = <<< EOD
UPDATE prl
SET prl.primary_resource_link_pk = NULL, prl.share_approved = NULL
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} prl
INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl ON (prl.primary_resource_link_pk = rl.resource_link_pk)
WHERE (rl.consumer_pk = ?)
EOD;
        $params = [
            $platform->getRecordId()
        ];
        $this->executeQuery($sql, $params) !== false;

// Update any resource links for contexts in which this consumer is acting as a primary resource link
        $sql = <<< EOD
UPDATE prl
SET prl.primary_resource_link_pk = NULL, prl.share_approved = NULL
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} prl
  INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl ON (prl.primary_resource_link_pk = rl.resource_link_pk)
  INNER JOIN {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c ON (rl.context_pk = c.context_pk)
WHERE (c.consumer_pk = ?)
EOD;
        $params = [
            $platform->getRecordId()
        ];
        $this->executeQuery($sql, $params) !== false;

// Delete any resource links for this consumer
        $sql = <<< EOD
DELETE rl
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl
WHERE (rl.consumer_pk = ?)
EOD;
        $params = [
            $platform->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Delete any resource links for contexts in this consumer
        $sql = <<< EOD
DELETE rl
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl
  INNER JOIN {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c ON (rl.context_pk = c.context_pk)
WHERE (c.consumer_pk = ?)
EOD;
        $params = [
            $platform->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Delete any contexts for this consumer
        $sql = <<< EOD
DELETE c
FROM {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c
WHERE (c.consumer_pk = ?)
EOD;
        $params = [
            $platform->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Delete consumer
        $sql = <<< EOD
DELETE c
FROM {$this->dbTableName(static::PLATFORM_TABLE_NAME)} c
WHERE (c.consumer_pk = ?)
EOD;
        $params = [
            $platform->getRecordId()
        ];
        $ok = $this->executeQuery($sql, $params) !== false;

        if ($ok) {
            $platform->initialize();
        }

        return $ok;
    }

    /**
     * Load all platforms from the database.
     *
     * @return Platform[]    An array of the Platform objects
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
        $rsConsumers = $this->executeQuery($sql);
        if ($rsConsumers) {
            while ($row = sqlsrv_fetch_object($rsConsumers)) {
                $platform = new Platform($this);
                $platform->setRecordId(intval($row->consumer_pk));
                $platform->name = $row->name;
                $platform->setKey($row->consumer_key);
                $platform->secret = $row->secret;
                $platform->platformId = $row->platform_id;
                $platform->clientId = $row->client_id;
                $platform->deploymentId = $row->deployment_id;
                $platform->rsaKey = $row->public_key;
                $platform->ltiVersion = LtiVersion::tryFrom($row->lti_version ?? '');
                $platform->signatureMethod = $row->signature_method;
                $platform->consumerName = $row->consumer_name;
                $platform->consumerVersion = $row->consumer_version;
                $platform->consumerGuid = $row->consumer_guid;
                $platform->profile = Util::jsonDecode($row->profile);
                $platform->toolProxy = $row->tool_proxy;
                $settings = Util::jsonDecode($row->settings, true);
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
                    $platform->enableFrom = date_timestamp_get($row->enable_from);
                }
                $platform->enableUntil = null;
                if (!is_null($row->enable_until)) {
                    $platform->enableUntil = date_timestamp_get($row->enable_until);
                }
                $platform->lastAccess = null;
                if (!is_null($row->last_access)) {
                    $platform->lastAccess = date_timestamp_get($row->last_access);
                }
                $platform->created = date_timestamp_get($row->created);
                $platform->updated = date_timestamp_get($row->updated);
                $this->fixPlatformSettings($platform, false);
                $platforms[] = $platform;
            }
            sqlsrv_free_stmt($rsConsumers);
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
WHERE (context_pk = ?)
EOD;
            $params = [
                $context->getRecordId()
            ];
        } else {
            $sql = <<< EOD
SELECT context_pk, consumer_pk, title, lti_context_id, type, settings, created, updated
FROM {$this->dbTableName(static::CONTEXT_TABLE_NAME)}
WHERE (consumer_pk = ?) AND (lti_context_id = ?)
EOD;
            $params = [
                $context->getPlatform()->getRecordId(),
                $context->ltiContextId
            ];
        }
        $rsContext = $this->executeQuery($sql, $params);
        if ($rsContext) {
            $row = sqlsrv_fetch_object($rsContext);
            if ($row) {
                $context->setRecordId(intval($row->context_pk));
                $context->setPlatformId(intval($row->consumer_pk));
                $context->title = $row->title;
                $context->ltiContextId = $row->lti_context_id;
                $context->type = $row->type;
                $settings = Util::jsonDecode($row->settings, true);
                if (!is_array($settings)) {
                    $settings = @unserialize($row->settings);  // check for old serialized setting
                }
                if (!is_array($settings)) {
                    $settings = [];
                }
                $context->setSettings($settings);
                $context->created = date_timestamp_get($row->created);
                $context->updated = date_timestamp_get($row->updated);
                $ok = true;
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
        $id = $context->getRecordId();
        if (empty($id)) {
            $sql = <<< EOD
INSERT INTO {$this->dbTableName(static::CONTEXT_TABLE_NAME)} (
  consumer_pk, title, lti_context_id, type, settings, created, updated)
VALUES (?, ?, ?, ?, ?, ?, ?)
EOD;
            $params = [
                $context->getPlatform()->getRecordId(),
                $context->title,
                $context->ltiContextId,
                $context->type,
                json_encode($context->getSettings()),
                $now,
                $now
            ];
        } else {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::CONTEXT_TABLE_NAME)}
SET title = ?, lti_context_id = ?, type = ?, settings = ?, updated = ?
WHERE (consumer_pk = ?) AND (context_pk = ?)
EOD;
            $params = [
                $context->title,
                $context->ltiContextId,
                $context->type,
                json_encode($context->getSettings()),
                $now,
                $context->getPlatform()->getRecordId(),
                $id
            ];
        }
        $ok = $this->executeQuery($sql, $params) !== false;
        if ($ok) {
            if (empty($id)) {
                $context->setRecordId($this->insert_id());
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
// Delete any outstanding share keys for resource links for this context
        $sql = <<< EOD
DELETE sk
FROM {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)} sk
INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl ON (sk.resource_link_pk = rl.resource_link_pk)
WHERE (rl.context_pk = ?)
EOD;
        $params = [
            $context->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Delete any users in resource links for this context
        $sql = <<< EOD
DELETE u
FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)} u
  INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl ON (u.resource_link_pk = rl.resource_link_pk)
WHERE (rl.context_pk = ?)
EOD;
        $params = [
            $context->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Update any resource links for which this context is acting as a primary resource link
        $sql = <<< EOD
UPDATE prl
SET prl.primary_resource_link_pk = NULL, prl.share_approved = NULL
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} prl
  INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl ON (prl.primary_resource_link_pk = rl.resource_link_pk)
WHERE (rl.context_pk = ?)
EOD;
        $params = [
            $context->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Delete any resource links for this context
        $sql = <<< EOD
DELETE rl
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl
WHERE (rl.context_pk = ?)
EOD;
        $params = [
            $context->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Delete context
        $sql = <<< EOD
DELETE c
FROM {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c
WHERE (c.context_pk = ?)
EOD;
        $params = [
            $context->getRecordId()
        ];
        $ok = $this->executeQuery($sql, $params) !== false;

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
        $ok = false;
        if (!is_null($resourceLink->getRecordId())) {
            $sql = <<< EOD
SELECT resource_link_pk, context_pk, consumer_pk, title, lti_resource_link_id, settings, primary_resource_link_pk,
  share_approved, created, updated
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
WHERE (resource_link_pk = ?)
EOD;
            $params = [
                $resourceLink->getRecordId()
            ];
        } elseif (!is_null($resourceLink->getContext())) {
            $sql = <<< EOD
SELECT r.resource_link_pk, r.context_pk, r.consumer_pk, r.title, r.lti_resource_link_id, r.settings, r.primary_resource_link_pk, r.share_approved, r.created, r.updated
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} r
WHERE (r.lti_resource_link_id = ?) AND ((r.context_pk = ?) OR (r.consumer_pk IN (
  SELECT c.consumer_pk
  FROM {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c
  WHERE (c.context_pk = ?)
)))
EOD;
            $params = [
                $resourceLink->getId(),
                $resourceLink->getContext()->getRecordId(),
                $resourceLink->getContext()->getRecordId()
            ];
        } else {
            $sql = <<< EOD
SELECT r.resource_link_pk, r.context_pk, r.consumer_pk, r.title, r.lti_resource_link_id, r.settings, r.primary_resource_link_pk,
  r.share_approved, r.created, r.updated
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} r
  LEFT OUTER JOIN {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c ON (r.context_pk = c.context_pk)
WHERE ((r.consumer_pk = ?) OR (c.consumer_pk = ?)) AND (lti_resource_link_id = ?)
EOD;
            $params = [
                $resourceLink->getPlatform()->getRecordId(),
                $resourceLink->getPlatform()->getRecordId(),
                $resourceLink->getId()
            ];
        }
        $rsResourceLink = $this->executeQuery($sql, $params);
        if ($rsResourceLink) {
            $row = sqlsrv_fetch_object($rsResourceLink);
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
                $settings = Util::jsonDecode($row->settings, true);
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
                $resourceLink->created = date_timestamp_get($row->created);
                $resourceLink->updated = date_timestamp_get($row->updated);
                $ok = true;
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
        $time = time();
        $now = date("{$this->dateFormat} {$this->timeFormat}", $time);
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
        if (empty($id)) {
            $sql = <<< EOD
INSERT INTO {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} (
  consumer_pk, context_pk, title, lti_resource_link_id, settings,
  primary_resource_link_pk, share_approved, created, updated)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
EOD;
            $params = [
                $consumerId,
                $contextId,
                $resourceLink->title,
                $resourceLink->getId(),
                json_encode($resourceLink->getSettings()),
                $resourceLink->primaryResourceLinkId,
                $resourceLink->shareApproved,
                $now,
                $now
            ];
        } elseif (!is_null($contextId)) {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
SET consumer_pk = ?, title = ?, lti_resource_link_id = ?, settings = ?,
  primary_resource_link_pk = ?, share_approved = ?, updated = ?
WHERE (context_pk = ?) AND (resource_link_pk = ?)
EOD;
            $params = [
                $consumerId,
                $resourceLink->title,
                $resourceLink->getId(),
                json_encode($resourceLink->getSettings()),
                $resourceLink->primaryResourceLinkId,
                $resourceLink->shareApproved,
                $now,
                $contextId,
                $id
            ];
        } else {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
SET context_pk = NULL, title = ?, lti_resource_link_id = ?, settings = ?,
  primary_resource_link_pk = ?, share_approved = ?, updated = ?
WHERE (consumer_pk = ?) AND (resource_link_pk = ?)
EOD;
            $params = [
                $resourceLink->title,
                $resourceLink->getId(),
                json_encode($resourceLink->getSettings()),
                $resourceLink->primaryResourceLinkId,
                $resourceLink->shareApproved,
                $now,
                $consumerId,
                $id
            ];
        }
        $ok = $this->executeQuery($sql, $params) !== false;
        if ($ok) {
            if (empty($id)) {
                $resourceLink->setRecordId($this->insert_id());
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
// Delete any outstanding share keys for resource links for this consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)}
WHERE (resource_link_pk = ?)
EOD;
        $params = [
            $resourceLink->getRecordId()
        ];
        $ok = $this->executeQuery($sql, $params) !== false;

// Delete users
        if ($ok) {
            $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)}
WHERE (resource_link_pk = ?)
EOD;
            $params = [
                $resourceLink->getRecordId()
            ];
            $ok = $this->executeQuery($sql, $params) !== false;
        }

// Update any resource links for which this is the primary resource link
        if ($ok) {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
SET primary_resource_link_pk = NULL
WHERE (primary_resource_link_pk = ?)
EOD;
            $params = [
                $resourceLink->getRecordId()
            ];
            $ok = $this->executeQuery($sql, $params) !== false;
        }

// Delete resource link
        if ($ok) {
            $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
WHERE (resource_link_pk = ?)
EOD;
            $params = [
                $resourceLink->getRecordId()
            ];
            $ok = $this->executeQuery($sql, $params) !== false;
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
        $userResults = [];

        if ($localOnly) {
            $sql = <<< EOD
SELECT u.user_result_pk, u.lti_result_sourcedid, u.lti_user_id, u.created, u.updated
FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)} AS u
  INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} AS rl ON (u.resource_link_pk = rl.resource_link_pk)
WHERE (rl.resource_link_pk = ?) AND (rl.primary_resource_link_pk IS NULL)
EOD;
            $params = [
                $resourceLink->getRecordId()
            ];
        } else {
            $sql = <<< EOD
SELECT u.user_result_pk, u.lti_result_sourcedid, u.lti_user_id, u.created, u.updated
FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)} AS u
  INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} AS rl ON (u.resource_link_pk = rl.resource_link_pk)
WHERE ((rl.resource_link_pk = ?) AND (rl.primary_resource_link_pk IS NULL)) OR
  ((rl.primary_resource_link_pk = ?) AND (share_approved = 1))
EOD;
            $params = [
                $resourceLink->getRecordId(),
                $resourceLink->getRecordId()
            ];
        }
        $rsUser = $this->executeQuery($sql, $params);
        if ($rsUser) {
            while ($row = sqlsrv_fetch_object($rsUser)) {
                $userResult = LTI\UserResult::fromResourceLink($resourceLink, $row->lti_user_id);
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
        $shares = [];

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
        $params = [
            $resourceLink->getRecordId(),
            $resourceLink->getRecordId()
        ];
        $rsShare = $this->executeQuery($sql, $params);
        if ($rsShare) {
            while ($row = sqlsrv_fetch_object($rsShare)) {
                $share = new LTI\ResourceLinkShare();
                $share->consumerName = $row->consumer_name;
                $share->resourceLinkId = intval($row->resource_link_pk);
                $share->title = $row->title;
                $share->approved = (intval($row->share_approved) === 1);
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
            $ok = false;

// Delete any expired nonce values
            $now = date("{$this->dateFormat} {$this->timeFormat}", time());
            $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::NONCE_TABLE_NAME)}
WHERE (expires <= ?)
EOD;
            $params = [
                $now
            ];
            $this->executeQuery($sql, $params);

// Load the nonce
            $sql = <<< EOD
SELECT value AS T
FROM {$this->dbTableName(static::NONCE_TABLE_NAME)}
WHERE (consumer_pk = ?) AND (value = ?)
EOD;
            $params = [
                $nonce->getPlatform()->getRecordId(),
                $nonce->getValue()
            ];
            $rsNonce = $this->executeQuery($sql, $params, false);
            if ($rsNonce) {
                if (sqlsrv_fetch_object($rsNonce)) {
                    $ok = true;
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
            $expires = date("{$this->dateFormat} {$this->timeFormat}", $nonce->expires);
            $sql = <<< EOD
INSERT INTO {$this->dbTableName(static::NONCE_TABLE_NAME)} (consumer_pk, value, expires)
VALUES (?, ?, ?)
EOD;
            $params = [
                $nonce->getPlatform()->getRecordId(),
                $nonce->getValue(), $expires
            ];
            $ok = $this->executeQuery($sql, $params) !== false;
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
            $params = [
                $nonce->getPlatform()->getRecordId(),
                $nonce->getValue()
            ];
            $ok = $this->executeQuery($sql, $params) !== false;
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
WHERE (consumer_pk = ?)
EOD;
            $params = [
                $consumer_pk
            ];
            $rsAccessToken = $this->executeQuery($sql, $params, false);
            if ($rsAccessToken) {
                $row = sqlsrv_fetch_object($rsAccessToken);
                if ($row) {
                    $scopes = Util::jsonDecode($row->scopes, true);
                    if (!is_array($scopes)) {
                        $scopes = [];
                    }
                    $accessToken->scopes = $scopes;
                    $accessToken->token = $row->token;
                    $accessToken->expires = date_timestamp_get($row->expires);
                    $accessToken->created = date_timestamp_get($row->created);
                    $accessToken->updated = date_timestamp_get($row->updated);
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
VALUES (?, ?, ?, ?, ?, ?)
EOD;
                $params = [
                    $consumer_pk,
                    $scopes,
                    $token,
                    $expires,
                    $now,
                    $now
                ];
            } else {
                $sql = <<< EOD
UPDATE {$this->dbTableName(static::ACCESS_TOKEN_TABLE_NAME)}
SET scopes = ?, token = ?, expires = ?, updated = ?
WHERE (consumer_pk = ?)
EOD;
                $params = [
                    $scopes,
                    $token,
                    $expires,
                    $now,
                    $consumer_pk
                ];
            }
            $ok = $this->executeQuery($sql, $params) !== false;
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
WHERE (expires <= ?)
EOD;
        $params = [
            $now
        ];
        $this->executeQuery($sql, $params);

// Load share key
        $id = $shareKey->getId();
        $sql = <<< EOD
SELECT resource_link_pk, auto_approve, expires
FROM {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)}
WHERE share_key_id = ?
EOD;
        $params = [
            $id
        ];
        $rsShareKey = $this->executeQuery($sql, $params);
        if ($rsShareKey) {
            $row = sqlsrv_fetch_object($rsShareKey);
            if ($row) {
                $shareKey->resourceLinkId = intval($row->resource_link_pk);
                $shareKey->autoApprove = (intval($row->auto_approve) === 1);
                $shareKey->expires = date_timestamp_get($row->expires);
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
        $expires = date("{$this->dateFormat} {$this->timeFormat}", $shareKey->expires);
        $sql = <<< EOD
INSERT INTO {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)} (
  share_key_id, resource_link_pk, auto_approve, expires)
VALUES (?, ?, ?, ?)
EOD;
        $params = [
            $shareKey->getId(),
            $shareKey->resourceLinkId,
            $shareKey->autoApprove,
            $expires
        ];
        $ok = $this->executeQuery($sql, $params) !== false;

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
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)}
WHERE (share_key_id = ?)
EOD;
        $params = [
            $shareKey->getId()
        ];
        $ok = $this->executeQuery($sql, $params) !== false;

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
        $ok = false;
        if (!is_null($userResult->getRecordId())) {
            $sql = <<< EOD
SELECT user_result_pk, resource_link_pk, lti_user_id, lti_result_sourcedid, created, updated
FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)}
WHERE (user_result_pk = ?)
EOD;
            $params = [
                $userResult->getRecordId()
            ];
        } else {
            $sql = <<< EOD
SELECT user_result_pk, resource_link_pk, lti_user_id, lti_result_sourcedid, created, updated
FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)}
WHERE (resource_link_pk = ?) AND (lti_user_id = ?)
EOD;
            $params = [
                $userResult->getResourceLink()->getRecordId(),
                $userResult->getId(LTI\Enum\IdScope::IdOnly)
            ];
        }
        $rsUserResult = $this->executeQuery($sql, $params);
        if ($rsUserResult) {
            $row = sqlsrv_fetch_object($rsUserResult);
            if ($row) {
                $userResult->setRecordId(intval($row->user_result_pk));
                $userResult->setResourceLinkId(intval($row->resource_link_pk));
                $userResult->ltiUserId = $row->lti_user_id;
                $userResult->ltiResultSourcedId = $row->lti_result_sourcedid;
                $userResult->created = date_timestamp_get($row->created);
                $userResult->updated = date_timestamp_get($row->updated);
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
            $sql = <<< EOD
INSERT INTO {$this->dbTableName(static::USER_RESULT_TABLE_NAME)} (
  resource_link_pk, lti_user_id, lti_result_sourcedid, created, updated)
VALUES (?, ?, ?, ?, ?)
EOD;
            $params = [
                $userResult->getResourceLink()->getRecordId(),
                $userResult->getId(LTI\Enum\IdScope::IdOnly),
                $userResult->ltiResultSourcedId,
                $now,
                $now
            ];
        } else {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::USER_RESULT_TABLE_NAME)}
SET lti_result_sourcedid = ?, updated = ?
WHERE (user_result_pk = ?)
EOD;
            $params = [
                $userResult->ltiResultSourcedId,
                $now,
                $userResult->getRecordId()
            ];
        }
        $ok = $this->executeQuery($sql, $params) !== false;
        if ($ok) {
            if (is_null($userResult->created)) {
                $userResult->setRecordId($this->insert_id());
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
WHERE (user_result_pk = ?)
EOD;
        $params = [
            $userResult->getRecordId()
        ];
        $ok = $this->executeQuery($sql, $params) !== false;

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
WHERE (tool_pk = ?)
EOD;
            $params = [
                $tool->getRecordId()
            ];
        } elseif (!empty($tool->initiateLoginUrl)) {
            $sql = <<< EOD
SELECT tool_pk, name, consumer_key, secret, message_url, initiate_login_url, redirection_uris, public_key,
  lti_version, signature_method, settings, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::TOOL_TABLE_NAME)}
WHERE (initiate_login_url = ?)
EOD;
            $params = [
                $tool->initiateLoginUrl
            ];
        } else {
            $sql = <<< EOD
SELECT tool_pk, name, consumer_key, secret, message_url, initiate_login_url, redirection_uris, public_key,
  lti_version, signature_method, settings, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::TOOL_TABLE_NAME)}
WHERE (consumer_key = ?)
EOD;
            $params = [
                $tool->getKey()
            ];
        }
        $rsTool = $this->executeQuery($sql, $params);
        if ($rsTool) {
            $row = sqlsrv_fetch_object($rsTool);
            if ($row) {
                $tool->setRecordId(intval($row->tool_pk));
                $tool->name = $row->name;
                $tool->setkey($row->consumer_key);
                $tool->secret = $row->secret;
                $tool->messageUrl = $row->message_url;
                $tool->initiateLoginUrl = $row->initiate_login_url;
                $tool->redirectionUris = Util::jsonDecode($row->redirection_uris, true);
                if (!is_array($tool->redirectionUris)) {
                    $tool->redirectionUris = [];
                }
                $tool->rsaKey = $row->public_key;
                $tool->ltiVersion = LtiVersion::tryFrom($row->lti_version ?? '');
                $tool->signatureMethod = $row->signature_method;
                $settings = Util::jsonDecode($row->settings, true);
                if (!is_array($settings)) {
                    $settings = [];
                }
                $tool->setSettings($settings);
                $tool->enabled = (intval($row->enabled) === 1);
                $tool->enableFrom = null;
                if (!is_null($row->enable_from)) {
                    $tool->enableFrom = date_timestamp_get($row->enable_from);
                }
                $tool->enableUntil = null;
                if (!is_null($row->enable_until)) {
                    $tool->enableUntil = date_timestamp_get($row->enable_until);
                }
                $tool->lastAccess = null;
                if (!is_null($row->last_access)) {
                    $tool->lastAccess = date_timestamp_get($row->last_access);
                }
                $tool->created = date_timestamp_get($row->created);
                $tool->updated = date_timestamp_get($row->updated);
                $this->fixToolSettings($tool, false);
                $ok = true;
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
        $this->fixToolSettings($tool, true);
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
            $sql = <<< EOD
INSERT INTO {$this->dbTableName(static::TOOL_TABLE_NAME)} (
  name, consumer_key, secret, message_url, initiate_login_url, redirection_uris, public_key,
  lti_version, signature_method, settings, enabled, enable_from, enable_until, last_access, created, updated)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
EOD;
            $params = [
                $tool->name,
                $tool->getKey(),
                $tool->secret,
                $tool->messageUrl,
                $tool->initiateLoginUrl,
                json_encode($tool->redirectionUris),
                $tool->rsaKey,
                $tool->ltiVersion ? $tool->ltiVersion->value : '',
                $tool->signatureMethod,
                json_encode($tool->getSettings()),
                $tool->enabled,
                $from,
                $until,
                $last,
                $now,
                $now
            ];
        } else {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::TOOL_TABLE_NAME)}
SET name = ?, consumer_key = ?, secret= ?, message_url = ?, initiate_login_url = ?, redirection_uris = ?, public_key = ?,
  lti_version = ?, signature_method = ?, settings = ?, enabled = ?, enable_from = ?, enable_until = ?, last_access = ?, updated = ?
WHERE (tool_pk = ?)
EOD;
            $params = [
                $tool->name,
                $tool->getKey(),
                $tool->secret,
                $tool->messageUrl,
                $tool->initiateLoginUrl,
                json_encode($tool->redirectionUris),
                $tool->rsaKey,
                $tool->ltiVersion ? $tool->ltiVersion->value : '',
                $tool->signatureMethod,
                json_encode($tool->getSettings()),
                $tool->enabled,
                $from,
                $until,
                $last,
                $now,
                $tool->getRecordId()
            ];
        }
        $ok = $this->executeQuery($sql, $params) !== false;
        if ($ok) {
            if (empty($id)) {
                $tool->setRecordId($this->insert_id());
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
        $sql = <<< EOD
DELETE t
FROM {$this->dbTableName(static::TOOL_TABLE_NAME)} t
WHERE (t.tool_pk = ?)
EOD;
        $params = [
            $tool->getRecordId()
        ];
        $ok = $this->executeQuery($sql, $params) !== false;

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
        $rsTools = $this->executeQuery($sql);
        if ($rsTools) {
            while ($row = sqlsrv_fetch_object($rsTools)) {
                $tool = new Tool($this);
                $tool->setRecordId(intval($row->tool_pk));
                $tool->name = $row->name;
                $tool->setkey($row->consumer_key);
                $tool->secret = $row->secret;
                $tool->messageUrl = $row->message_url;
                $tool->initiateLoginUrl = $row->initiate_login_url;
                $tool->redirectionUris = Util::jsonDecode($row->redirection_uris, true);
                if (!is_array($tool->redirectionUris)) {
                    $tool->redirectionUris = [];
                }
                $tool->rsaKey = $row->public_key;
                $tool->ltiVersion = LtiVersion::tryFrom($row->lti_version ?? '');
                $tool->signatureMethod = $row->signature_method;
                $settings = Util::jsonDecode($row->settings, true);
                if (!is_array($settings)) {
                    $settings = [];
                }
                $tool->setSettings($settings);
                $tool->enabled = (intval($row->enabled) === 1);
                $tool->enableFrom = null;
                if (!is_null($row->enable_from)) {
                    $tool->enableFrom = date_timestamp_get($row->enable_from);
                }
                $tool->enableUntil = null;
                if (!is_null($row->enable_until)) {
                    $tool->enableUntil = date_timestamp_get($row->enable_until);
                }
                $tool->lastAccess = null;
                if (!is_null($row->last_access)) {
                    $tool->lastAccess = date_timestamp_get($row->last_access);
                }
                $tool->created = date_timestamp_get($row->created);
                $tool->updated = date_timestamp_get($row->updated);
                $this->fixToolSettings($tool, false);
                $tools[] = $tool;
            }
            sqlsrv_free_stmt($rsTools);
        }

        return $tools;
    }

###
###  Other methods
###

    /**
     * Get the ID for the last record inserted into a table.
     *
     * @return int  Id of last inserted record
     */
    private function insert_id(): int
    {
        $id = 0;
        $sql = 'SELECT @@IDENTITY AS insid;';
        $rsId = $this->executeQuery($sql);
        if ($rsId) {
            sqlsrv_fetch($rsId);
            $id = sqlsrv_get_field($rsId, 0, SQLSRV_PHPTYPE_INT);
        }

        return $id;
    }

    /**
     * Execute a database query.
     *
     * Info and debug messages are generated.
     *
     * @param string $sql          SQL statement
     * @param array  $params       Array of SQL parameter values
     * @param bool   $reportError  True if errors are to be reported
     *
     * @return mixed  The result resource or true, or false on error.
     */
    private function executeQuery(string $sql, array $params = [], bool $reportError = true): mixed
    {
        $res = sqlsrv_query($this->db, $sql, $params);
        if (($res === false) && $reportError) {
            Util::logError($sql . $this->errorListToString(sqlsrv_errors()));
        } else {
            Util::logDebug($sql);
        }

        return $res;
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
                $errors .= PHP_EOL . "{$sep}{$error['code']}/{$error['SQLSTATE']}: {$error['message']}";
                $sep = '  ';
            }
        }

        return $errors;
    }

}
