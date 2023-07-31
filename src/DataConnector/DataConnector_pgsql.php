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
 * Class to represent an LTI Data Connector for PostgreSQL
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
###
#    NB This class assumes that a PostgreSQL connection has already been opened to the appropriate schema
###


class DataConnector_pgsql extends DataConnector
{
###
###  Platform methods
###

    /**
     * Load platform object.
     *
     * @param Platform $platform Platform object
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
WHERE (consumer_pk = $1)
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
WHERE (platform_id = $1)
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
WHERE (platform_id = $1) AND (client_id = $2)
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
WHERE (platform_id = $1) AND (client_id = $2) AND (deployment_id = $3)
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
WHERE (consumer_key = $1)
EOD;
            $params = [
                $platform->getKey()
            ];
        }
        $rsConsumer = $this->executeQuery($sql, $params);
        if ($rsConsumer) {
            $row = pg_fetch_object($rsConsumer);
            if ($row && ($allowMultiple || !pg_fetch_object($rsConsumer))) {
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
                $platform->protected = ($row->protected === 't');
                $platform->enabled = ($row->enabled === 't');
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
VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18, $19, $20, $21, $21)
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
                $platform->protected ? 't' : 'f',
                $platform->enabled ? 't' : 'f',
                $from,
                $until,
                $last,
                $now
            ];
        } else {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::PLATFORM_TABLE_NAME)} SET
  consumer_key = $1, name = $2, secret = $3, platform_id = $4, client_id = $5, deployment_id = $6, public_key = $7,
  lti_version = $8, signature_method = $9, consumer_name = $10, consumer_version = $11, consumer_guid = $12,
  profile = $13, tool_proxy = $14, settings = $15,
  protected = $16, enabled = $17, enable_from = $18, enable_until = $19, last_access = $20, updated = $21
WHERE (consumer_pk = $22)
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
                $platform->protected ? 't' : 'f',
                $platform->enabled ? 't' : 'f',
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
WHERE (consumer_pk = $1)
EOD;
        $params = [
            $platform->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Delete any nonce values for this consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::NONCE_TABLE_NAME)}
WHERE (consumer_pk = $1)
EOD;
        $params = [
            $platform->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Delete any outstanding share keys for resource links for this consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)}
WHERE resource_link_pk IN (
  SELECT resource_link_pk
  FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
  WHERE (consumer_pk = $1)
)
EOD;
        $params = [
            $platform->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Delete any outstanding share keys for resource links for contexts in this consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)}
WHERE resource_link_pk IN (
  SELECT resource_link_pk
  FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl
    INNER JOIN {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c ON (rl.context_pk = c.context_pk)
  WHERE (c.consumer_pk = $1)
)
EOD;
        $params = [
            $platform->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Delete any users in resource links for this consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)}
WHERE resource_link_pk IN (
  SELECT resource_link_pk
  FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
  WHERE (consumer_pk = $1)
)
EOD;
        $params = [
            $platform->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Delete any users in resource links for contexts in this consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)}
WHERE resource_link_pk IN (
  SELECT resource_link_pk
  FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl
    INNER JOIN {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c ON (rl.context_pk = c.context_pk)
  WHERE (c.consumer_pk = $1)
)
EOD;
        $params = [
            $platform->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Update any resource links for which this consumer is acting as a primary resource link
        $sql = <<< EOD
UPDATE {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
SET primary_resource_link_pk = NULL, share_approved = NULL
WHERE primary_resource_link_pk IN (
  SELECT resource_link_pk FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
  WHERE (consumer_pk = $1)
)
EOD;
        $params = [
            $platform->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Update any resource links for contexts in which this consumer is acting as a primary resource link
        $sql = <<< EOD
UPDATE {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
SET primary_resource_link_pk = NULL, share_approved = NULL
WHERE primary_resource_link_pk IN (
  SELECT rl.resource_link_pk FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} rl
    INNER JOIN {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c ON (rl.context_pk = c.context_pk)
  WHERE (c.consumer_pk = $1)
)
EOD;
        $params = [
            $platform->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Delete any resource links for this consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
WHERE (consumer_pk = $1)
EOD;
        $params = [
            $platform->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Delete any resource links for contexts in this consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
WHERE context_pk IN (
  SELECT context_pk
  FROM {$this->dbTableName(static::CONTEXT_TABLE_NAME)}
  WHERE (consumer_pk = $1)
)
EOD;
        $params = [
            $platform->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Delete any contexts for this consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::CONTEXT_TABLE_NAME)}
WHERE (consumer_pk = $1)
EOD;
        $params = [
            $platform->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Delete consumer
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::PLATFORM_TABLE_NAME)}
WHERE (consumer_pk = $1)
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
        $rsConsumers = $this->executeQuery($sql);
        if ($rsConsumers) {
            while ($row = pg_fetch_object($rsConsumers)) {
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
            pg_free_result($rsConsumers);
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
WHERE (context_pk = $1)
EOD;
            $params = [
                $context->getRecordId()
            ];
        } else {
            $sql = <<< EOD
SELECT context_pk, consumer_pk, title, lti_context_id, type, settings, created, updated
FROM {$this->dbTableName(static::CONTEXT_TABLE_NAME)}
WHERE (consumer_pk = $1) AND (lti_context_id = $2)
EOD;
            $params = [
                $context->getPlatform()->getRecordId(),
                $context->ltiContextId
            ];
        }
        $rsContext = $this->executeQuery($sql, $params);
        if ($rsContext) {
            $row = pg_fetch_object($rsContext);
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
                $context->created = strtotime($row->created);
                $context->updated = strtotime($row->updated);
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
        $consumer_pk = $context->getPlatform()->getRecordId();
        if (empty($id)) {
            $sql = <<< EOD
INSERT INTO {$this->dbTableName(static::CONTEXT_TABLE_NAME)} (
  consumer_pk, title, lti_context_id, type, settings, created, updated)
VALUES ($1, $2, $3, $4, $5, $6, $6)
EOD;
            $params = [
                $consumer_pk,
                $context->title,
                $context->ltiContextId,
                $context->type,
                json_encode($context->getSettings()),
                $now
            ];
        } else {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::CONTEXT_TABLE_NAME)}
SET title =  $1, lti_context_id = $2, type = $3, settings = $4, updated = $5
WHERE (consumer_pk = $6) AND (context_pk = $7)
EOD;
            $params = [
                $context->title,
                $context->ltiContextId,
                $context->type,
                json_encode($context->getSettings()),
                $now,
                $consumer_pk,
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
DELETE FROM {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)}
WHERE resource_link_pk IN (
  SELECT resource_link_pk
  FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
  WHERE (context_pk = $1)
)
EOD;
        $params = [
            $context->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Delete any users in resource links for this context
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)}
WHERE resource_link_pk IN (
  SELECT resource_link_pk
  FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
  WHERE (context_pk = $1)
)
EOD;
        $params = [
            $context->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Update any resource links for which this context is acting as a primary resource link
        $sql = <<< EOD
UPDATE {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
SET primary_resource_link_pk = NULL, share_approved = NULL
WHERE primary_resource_link_pk IN (
  SELECT resource_link_pk
  FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
  WHERE (context_pk = $1)
)
EOD;
        $params = [
            $context->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Delete any resource links for this context
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
WHERE (context_pk = $1)
EOD;
        $params = [
            $context->getRecordId()
        ];
        $this->executeQuery($sql, $params);

// Delete context
        $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::CONTEXT_TABLE_NAME)}
WHERE (context_pk = $1)
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
SELECT resource_link_pk, context_pk, consumer_pk, title, lti_resource_link_id, settings,
  primary_resource_link_pk, share_approved, created, updated
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
WHERE (resource_link_pk = $1)
EOD;
            $params = [
                $resourceLink->getRecordId()
            ];
        } elseif (!is_null($resourceLink->getContext())) {
            $sql = <<< EOD
SELECT r.resource_link_pk, r.context_pk, r.consumer_pk, r.title, r.lti_resource_link_id, r.settings,
  r.primary_resource_link_pk, r.share_approved, r.created, r.updated
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} r
WHERE (r.lti_resource_link_id = $1) AND ((r.context_pk = $2) OR (r.consumer_pk IN (
  SELECT c.consumer_pk
  FROM {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c
  WHERE (c.context_pk = $2)
)))
EOD;
            $params = [
                $resourceLink->getId(),
                $resourceLink->getContext()->getRecordId()
            ];
        } else {
            $sql = <<< EOD
SELECT r.resource_link_pk, r.context_pk, r.consumer_pk, r.title, r.lti_resource_link_id, r.settings,
  r.primary_resource_link_pk, r.share_approved, r.created, r.updated
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} r
  LEFT OUTER JOIN {$this->dbTableName(static::CONTEXT_TABLE_NAME)} c ON (r.context_pk = c.context_pk)
WHERE ((r.consumer_pk = $1) OR (c.consumer_pk = $1)) AND (lti_resource_link_id = $2)
EOD;
            $params = [
                $resourceLink->getPlatform()->getRecordId(),
                $resourceLink->getId()
            ];
        }
        $rsResourceLink = $this->executeQuery($sql, $params);
        if ($rsResourceLink) {
            $row = pg_fetch_object($rsResourceLink);
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
                $resourceLink->created = strtotime($row->created);
                $resourceLink->updated = strtotime($row->updated);
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
VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $8)
EOD;
            $params = [
                $consumerId,
                $contextId,
                $resourceLink->title,
                $resourceLink->getId(),
                json_encode($resourceLink->getSettings()),
                $resourceLink->primaryResourceLinkId,
                $resourceLink->shareApproved,
                $now
            ];
        } elseif (!is_null($contextId)) {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)}
SET consumer_pk = $1, title = $2, lti_resource_link_id = $3, settings = $4,
  primary_resource_link_pk = $5, share_approved = $6, updated = $7
WHERE (context_pk = $8) AND (resource_link_pk = $9)
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
SET context_pk = NULL, title = $1, lti_resource_link_id = $2, settings = $3,
  primary_resource_link_pk = $4, share_approved = $5, updated = $6
WHERE (consumer_pk = $7) AND (resource_link_pk = $8)
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
WHERE (resource_link_pk = $1)
EOD;
        $params = [
            $resourceLink->getRecordId()
        ];
        $ok = $this->executeQuery($sql, $params) !== false;

// Delete users
        if ($ok) {
            $sql = <<< EOD
DELETE FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)}
WHERE (resource_link_pk = $1)
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
WHERE (primary_resource_link_pk = $1)
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
WHERE (resource_link_pk = $1)
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
WHERE (rl.resource_link_pk = $1) AND (rl.primary_resource_link_pk IS NULL)
EOD;
            $params = [
                $resourceLink->getRecordId()
            ];
        } else {
            $sql = <<< EOD
SELECT u.user_result_pk, u.lti_result_sourcedid, u.lti_user_id, u.created, u.updated
FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)} AS u
  INNER JOIN {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} AS rl ON (u.resource_link_pk = rl.resource_link_pk)
WHERE ((rl.resource_link_pk = $1) AND (rl.primary_resource_link_pk IS NULL)) OR
  ((rl.primary_resource_link_pk = $1) AND share_approved)
EOD;
            $params = [
                $resourceLink->getRecordId()
            ];
        }
        $rsUser = $this->executeQuery($sql, $params);
        if ($rsUser) {
            while ($row = pg_fetch_object($rsUser)) {
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
WHERE (r.primary_resource_link_pk = $1)
UNION
SELECT c2.consumer_name, r2.resource_link_pk, r2.title, r2.share_approved
FROM {$this->dbTableName(static::RESOURCE_LINK_TABLE_NAME)} AS r2
  INNER JOIN {$this->dbTableName(static::CONTEXT_TABLE_NAME)} AS x ON (r2.context_pk = x.context_pk)
  INNER JOIN {$this->dbTableName(static::PLATFORM_TABLE_NAME)} AS c2 ON (x.consumer_pk = c2.consumer_pk)
WHERE (r2.primary_resource_link_pk = $1)
ORDER BY consumer_name, title
EOD;
        $params = [
            $resourceLink->getRecordId()
        ];
        $rsShare = $this->executeQuery($sql, $params);
        if ($rsShare) {
            while ($row = pg_fetch_object($rsShare)) {
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
WHERE (expires <= $1)
EOD;
            $params = [
                $now
            ];
            $this->executeQuery($sql, $params);

// Load the nonce
            $sql = <<< EOD
SELECT value AS T
FROM {$this->dbTableName(static::NONCE_TABLE_NAME)}
WHERE (consumer_pk = $1) AND (value = $2)
EOD;
            $params = [
                $nonce->getPlatform()->getRecordId(),
                $nonce->getValue()
            ];
            $rsNonce = $this->executeQuery($sql, $params, false);
            if ($rsNonce) {
                if (pg_fetch_object($rsNonce)) {
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
VALUES ($1, $2, $3)
EOD;
            $params = [
                $nonce->getPlatform()->getRecordId(),
                $nonce->getValue(),
                $expires
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
WHERE (consumer_pk = $1) AND (value = $2)
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
WHERE (consumer_pk = $1)
EOD;
            $params = [
                $consumer_pk
            ];
            $rsAccessToken = $this->executeQuery($sql, $params, false);
            if ($rsAccessToken) {
                $row = pg_fetch_object($rsAccessToken);
                if ($row) {
                    $scopes = Util::jsonDecode($row->scopes, true);
                    if (!is_array($scopes)) {
                        $scopes = [];
                    }
                    $accessToken->scopes = $scopes;
                    $accessToken->token = $row->token;
                    $accessToken->expires = strtotime($row->expires);
                    $accessToken->created = strtotime($row->created);
                    $accessToken->updated = strtotime($row->updated);
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
            $expires = date("{$this->dateFormat} {$this->timeFormat}", $accessToken->expires);
            $time = time();
            $now = date("{$this->dateFormat} {$this->timeFormat}", $time);
            if (empty($accessToken->created)) {
                $sql = <<< EOD
INSERT INTO {$this->dbTableName(static::ACCESS_TOKEN_TABLE_NAME)} (consumer_pk, scopes, token, expires, created, updated)
VALUES ($1, $2, $3, $4, $5, $5)
EOD;
                $params = [
                    $consumer_pk,
                    json_encode($accessToken->scopes, JSON_UNESCAPED_SLASHES),
                    $accessToken->token,
                    $expires,
                    $now
                ];
            } else {
                $sql = <<< EOD
UPDATE {$this->dbTableName(static::ACCESS_TOKEN_TABLE_NAME)}
SET scopes = $1, token = $2, expires = $3, updated = $4
WHERE (consumer_pk = $5)
EOD;
                $params = [
                    json_encode($accessToken->scopes, JSON_UNESCAPED_SLASHES),
                    $accessToken->token,
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
WHERE (expires <= $1)
EOD;
        $params = [
            $now
        ];
        $this->executeQuery($sql, $params);

// Load share key
        $sql = <<< EOD
SELECT resource_link_pk, auto_approve, expires
FROM {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)}
WHERE (share_key_id = $1)
EOD;
        $params = [
            $shareKey->getId()
        ];
        $rsShareKey = $this->executeQuery($sql, $params);
        if ($rsShareKey) {
            $row = pg_fetch_object($rsShareKey);
            if ($row) {
                $shareKey->resourceLinkId = intval($row->resource_link_pk);
                $shareKey->autoApprove = (intval($row->auto_approve) === 1);
                $shareKey->expires = strtotime($row->expires);
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
INSERT INTO {$this->dbTableName(static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME)}
  (share_key_id, resource_link_pk, auto_approve, expires)
VALUES ($1, $2, $3, $4)
EOD;
        $params = [
            $shareKey->getId(),
            $shareKey->resourceLinkId,
            $shareKey->autoApprove ? 't' : 'f',
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
WHERE (share_key_id = $1)
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
WHERE (user_result_pk = $1)
EOD;
            $params = [
                $userResult->getRecordId()
            ];
        } else {
            $sql = <<< EOD
SELECT user_result_pk, resource_link_pk, lti_user_id, lti_result_sourcedid, created, updated
FROM {$this->dbTableName(static::USER_RESULT_TABLE_NAME)}
WHERE (resource_link_pk = $1) AND (lti_user_id = $2)
EOD;
            $params = [
                $userResult->getResourceLink()->getRecordId(),
                $userResult->getId(LTI\Enum\IdScope::IdOnly)
            ];
        }
        $rsUserResult = $this->executeQuery($sql, $params);
        if ($rsUserResult) {
            $row = pg_fetch_object($rsUserResult);
            if ($row) {
                $userResult->setRecordId(intval($row->user_result_pk));
                $userResult->setResourceLinkId(intval($row->resource_link_pk));
                $userResult->ltiUserId = $row->lti_user_id;
                $userResult->ltiResultSourcedId = $row->lti_result_sourcedid;
                $userResult->created = strtotime($row->created);
                $userResult->updated = strtotime($row->updated);
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
VALUES($1, $2, $3, $4, $4)
EOD;
            $params = [
                $userResult->getResourceLink()->getRecordId(),
                $userResult->getId(LTI\Enum\IdScope::IdOnly),
                $userResult->ltiResultSourcedId,
                $now
            ];
        } else {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::USER_RESULT_TABLE_NAME)}
SET lti_result_sourcedid = $1, updated = $2
WHERE (user_result_pk = $3)
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
WHERE (user_result_pk = $1)
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
WHERE (tool_pk = $1)
EOD;
            $params = [
                $tool->getRecordId()
            ];
        } elseif (!empty($tool->initiateLoginUrl)) {
            $sql = <<< EOD
SELECT tool_pk, name, consumer_key, secret, message_url, initiate_login_url, redirection_uris, public_key,
  lti_version, signature_method, settings, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::TOOL_TABLE_NAME)}
WHERE (initiate_login_url = $1)
EOD;
            $params = [
                $tool->initiateLoginUrl
            ];
        } else {
            $sql = <<< EOD
SELECT tool_pk, name, consumer_key, secret, message_url, initiate_login_url, redirection_uris, public_key,
  lti_version, signature_method, settings, enabled, enable_from, enable_until, last_access, created, updated
FROM {$this->dbTableName(static::TOOL_TABLE_NAME)}
WHERE (consumer_key = $1)
EOD;
            $params = [
                $tool->getKey()
            ];
        }
        $rsTool = $this->executeQuery($sql, $params);
        if ($rsTool) {
            $row = pg_fetch_object($rsTool);
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
                $tool->enabled = ($row->enabled === 't');
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
VALUES($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $15)
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
                $tool->enabled ? 't' : 'f',
                $from,
                $until,
                $last,
                $now
            ];
        } else {
            $sql = <<< EOD
UPDATE {$this->dbTableName(static::TOOL_TABLE_NAME)}
SET name = $1, consumer_key = $2, secret = $3, message_url = $4, initiate_login_url = $5, redirection_uris = $6, public_key = $7,
  lti_version = $8, signature_method = $9, settings = $10, enabled = $11, enable_from = $12, enable_until = $13, last_access = $14, updated = $15
WHERE (tool_pk = $16)
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
                $tool->enabled ? 't' : 'f',
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
DELETE FROM {$this->dbTableName(static::TOOL_TABLE_NAME)}
WHERE (tool_pk = $1)
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
            while ($row = pg_fetch_object($rsTools)) {
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
            pg_free_result($rsTools);
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
        $rsId = $this->executeQuery('SELECT lastval();');
        $row = pg_fetch_row($rsId);
        return intval($row[0]);
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
     * @return mixed  The result resource, or false on error.
     */
    private function executeQuery(string $sql, array $params = [], bool $reportError = true): mixed
    {
        $res = @pg_query_params($this->db, $sql, $params);
        if (($res === false) && $reportError) {
            Util::logError($sql . PHP_EOL . 'Error: ' . pg_last_error($this->db));
        } else {
            Util::logDebug($sql);
        }

        return $res;
    }

}
