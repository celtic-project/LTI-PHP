<?php

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
use ceLTIc\LTI\Util;

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


class DataConnector_pg extends DataConnector
{
###
###  Platform methods
###

    /**
     * Load platform object.
     *
     * @param Platform $platform Platform object
     *
     * @return bool    True if the platform object was successfully loaded
     */
    public function loadPlatform($platform)
    {
        $ok = false;
        $allowMultiple = false;
        if (!is_null($platform->getRecordId())) {
            $sql = sprintf('SELECT consumer_pk, name, consumer_key, secret, ' .
                'platform_id, client_id, deployment_id, public_key, ' .
                'lti_version, signature_method, consumer_name, consumer_version, consumer_guid, ' .
                'profile, tool_proxy, settings, protected, enabled, ' .
                'enable_from, enable_until, last_access, created, updated ' .
                "FROM {$this->dbTableNamePrefix}" . static::PLATFORM_TABLE_NAME . ' ' .
                'WHERE consumer_pk = %d', $platform->getRecordId());
        } elseif (!empty($platform->platformId)) {
            if (empty($platform->clientId)) {
                $allowMultiple = true;
                $sql = sprintf('SELECT consumer_pk, name, consumer_key, secret, ' .
                    'platform_id, client_id, deployment_id, public_key, ' .
                    'lti_version, signature_method, consumer_name, consumer_version, consumer_guid, ' .
                    'profile, tool_proxy, settings, protected, enabled, ' .
                    'enable_from, enable_until, last_access, created, updated ' .
                    "FROM {$this->dbTableNamePrefix}" . static::PLATFORM_TABLE_NAME . ' ' .
                    'WHERE (platform_id = %s) ', $this->escape($platform->platformId));
            } elseif (empty($platform->deploymentId)) {
                $allowMultiple = true;
                $sql = sprintf('SELECT consumer_pk, name, consumer_key, secret, ' .
                    'platform_id, client_id, deployment_id, public_key, ' .
                    'lti_version, signature_method, consumer_name, consumer_version, consumer_guid, ' .
                    'profile, tool_proxy, settings, protected, enabled, ' .
                    'enable_from, enable_until, last_access, created, updated ' .
                    "FROM {$this->dbTableNamePrefix}" . static::PLATFORM_TABLE_NAME . ' ' .
                    'WHERE (platform_id = %s) AND (client_id = %s)', $this->escape($platform->platformId),
                    $this->escape($platform->clientId));
            } else {
                $sql = sprintf('SELECT consumer_pk, name, consumer_key, secret, ' .
                    'platform_id, client_id, deployment_id, public_key, ' .
                    'lti_version, signature_method, consumer_name, consumer_version, consumer_guid, ' .
                    'profile, tool_proxy, settings, protected, enabled, ' .
                    'enable_from, enable_until, last_access, created, updated ' .
                    "FROM {$this->dbTableNamePrefix}" . static::PLATFORM_TABLE_NAME . ' ' .
                    'WHERE (platform_id = %s) AND (client_id = %s) AND (deployment_id = %s)', $this->escape($platform->platformId),
                    $this->escape($platform->clientId), $this->escape($platform->deploymentId));
            }
        } else {
            $sql = sprintf('SELECT consumer_pk, name, consumer_key, secret, ' .
                'platform_id, client_id, deployment_id, public_key, ' .
                'lti_version, signature_method, consumer_name, consumer_version, consumer_guid, ' .
                'profile, tool_proxy, settings, protected, enabled, ' .
                'enable_from, enable_until, last_access, created, updated ' .
                "FROM {$this->dbTableNamePrefix}" . static::PLATFORM_TABLE_NAME . ' ' .
                "WHERE consumer_key = %s", $this->escape($platform->getKey()));
        }
        $rsConsumer = $this->executeQuery($sql);
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
                $platform->ltiVersion = $row->lti_version;
                $platform->signatureMethod = $row->signature_method;
                $platform->consumerName = $row->consumer_name;
                $platform->consumerVersion = $row->consumer_version;
                $platform->consumerGuid = $row->consumer_guid;
                $platform->profile = json_decode($row->profile);
                $platform->toolProxy = $row->tool_proxy;
                $settings = json_decode($row->settings, true);
                if (!is_array($settings)) {
                    $settings = @unserialize($row->settings);  // check for old serialized setting
                }
                if (!is_array($settings)) {
                    $settings = array();
                }
                $platform->setSettings($settings);
                $platform->protected = $row->protected;
                $platform->enabled = $row->enabled;
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
     * @param Platform $platform Platform object
     *
     * @return bool    True if the platform object was successfully saved
     */
    public function savePlatform($platform)
    {
        $id = $platform->getRecordId();
        $protected = ($platform->protected) ? 'true' : 'false';
        $enabled = ($platform->enabled) ? 'true' : 'false';
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
            $sql = sprintf("INSERT INTO {$this->dbTableNamePrefix}" . static::PLATFORM_TABLE_NAME . ' (consumer_key, name, secret, ' .
                'platform_id, client_id, deployment_id, public_key, ' .
                'lti_version, signature_method, consumer_name, consumer_version, consumer_guid, ' .
                'profile, tool_proxy, settings, protected, enabled, ' .
                'enable_from, enable_until, last_access, created, updated) ' .
                'VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)',
                $this->escape($platform->getKey()), $this->escape($platform->name), $this->escape($platform->secret),
                $this->escape($platform->platformId), $this->escape($platform->clientId), $this->escape($platform->deploymentId),
                $this->escape($platform->rsaKey), $this->escape($platform->ltiVersion), $this->escape($platform->signatureMethod),
                $this->escape($platform->consumerName), $this->escape($platform->consumerVersion),
                $this->escape($platform->consumerGuid), $this->escape($profile), $this->escape($platform->toolProxy),
                $this->escape($settingsValue), $protected, $enabled, $this->escape($from), $this->escape($until),
                $this->escape($last), $this->escape($now), $this->escape($now));
        } else {
            $sql = sprintf("UPDATE {$this->dbTableNamePrefix}" . static::PLATFORM_TABLE_NAME . ' SET ' .
                'consumer_key = %s, name = %s, secret = %s, ' .
                'platform_id = %s, client_id = %s, deployment_id = %s, public_key = %s, ' .
                'lti_version = %s, signature_method = %s, ' .
                'consumer_name = %s, consumer_version = %s, consumer_guid = %s, ' .
                'profile = %s, tool_proxy = %s, settings = %s, ' .
                'protected = %s, enabled = %s, enable_from = %s, enable_until = %s, last_access = %s, updated = %s ' .
                'WHERE consumer_pk = %d', $this->escape($platform->getKey()), $this->escape($platform->name),
                $this->escape($platform->secret), $this->escape($platform->platformId), $this->escape($platform->clientId),
                $this->escape($platform->deploymentId), $this->escape($platform->rsaKey), $this->escape($platform->ltiVersion),
                $this->escape($platform->signatureMethod), $this->escape($platform->consumerName),
                $this->escape($platform->consumerVersion), $this->escape($platform->consumerGuid), $this->escape($profile),
                $this->escape($platform->toolProxy), $this->escape($settingsValue), $protected, $enabled, $this->escape($from),
                $this->escape($until), $this->escape($last), $this->escape($now), $platform->getRecordId());
        }
        $ok = $this->executeQuery($sql);
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
     * @param Platform $platform Platform object
     *
     * @return bool    True if the platform object was successfully deleted
     */
    public function deletePlatform($platform)
    {
// Delete any access token value for this consumer
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::ACCESS_TOKEN_TABLE_NAME . ' WHERE consumer_pk = %d',
            $platform->getRecordId());
        $this->executeQuery($sql);

// Delete any nonce values for this consumer
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::NONCE_TABLE_NAME . ' WHERE consumer_pk = %d',
            $platform->getRecordId());
        $this->executeQuery($sql);

// Delete any outstanding share keys for resource links for this consumer
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . ' ' .
            "WHERE resource_link_pk IN (SELECT resource_link_pk FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
            'WHERE consumer_pk = %d)', $platform->getRecordId());
        $this->executeQuery($sql);

// Delete any outstanding share keys for resource links for contexts in this consumer
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . ' ' .
            "WHERE resource_link_pk IN (SELECT resource_link_pk FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' rl ' .
            "INNER JOIN {$this->dbTableNamePrefix}" . static::CONTEXT_TABLE_NAME . ' c ON rl.context_pk = c.context_pk WHERE c.consumer_pk = %d)',
            $platform->getRecordId());
        $this->executeQuery($sql);

// Delete any users in resource links for this consumer
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::USER_RESULT_TABLE_NAME . ' ' .
            "WHERE resource_link_pk IN (SELECT resource_link_pk FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
            'WHERE consumer_pk = %d)', $platform->getRecordId());
        $this->executeQuery($sql);

// Delete any users in resource links for contexts in this consumer
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::USER_RESULT_TABLE_NAME . ' ' .
            "WHERE resource_link_pk IN (SELECT resource_link_pk FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' rl ' .
            "INNER JOIN {$this->dbTableNamePrefix}" . static::CONTEXT_TABLE_NAME . ' c ON rl.context_pk = c.context_pk WHERE c.consumer_pk = %d)',
            $platform->getRecordId());
        $this->executeQuery($sql);

// Update any resource links for which this consumer is acting as a primary resource link
        $sql = sprintf("UPDATE {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
            'SET primary_resource_link_pk = NULL, share_approved = NULL ' .
            'WHERE primary_resource_link_pk IN ' .
            "(SELECT resource_link_pk FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
            'WHERE consumer_pk = %d)', $platform->getRecordId());
        $ok = $this->executeQuery($sql);

// Update any resource links for contexts in which this consumer is acting as a primary resource link
        $sql = sprintf("UPDATE {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
            'SET primary_resource_link_pk = NULL, share_approved = NULL ' .
            'WHERE primary_resource_link_pk IN ' .
            "(SELECT rl.resource_link_pk FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' rl ' .
            "INNER JOIN {$this->dbTableNamePrefix}" . static::CONTEXT_TABLE_NAME . ' c ON rl.context_pk = c.context_pk ' .
            'WHERE c.consumer_pk = %d)', $platform->getRecordId());
        $ok = $this->executeQuery($sql);

// Delete any resource links for this consumer
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
            'WHERE consumer_pk = %d', $platform->getRecordId());
        $this->executeQuery($sql);

// Delete any resource links for contexts in this consumer
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
            'WHERE context_pk IN (' .
            "SELECT context_pk FROM {$this->dbTableNamePrefix}" . static::CONTEXT_TABLE_NAME . ' ' . 'WHERE consumer_pk = %d)',
            $platform->getRecordId());
        $this->executeQuery($sql);

// Delete any contexts for this consumer
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::CONTEXT_TABLE_NAME . ' ' .
            'WHERE consumer_pk = %d', $platform->getRecordId());
        $this->executeQuery($sql);

// Delete consumer
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::PLATFORM_TABLE_NAME . ' ' .
            'WHERE consumer_pk = %d', $platform->getRecordId());
        $ok = $this->executeQuery($sql);

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
    public function getPlatforms()
    {
        $platforms = array();

        $sql = 'SELECT consumer_pk, consumer_key, name, secret, ' .
            'platform_id, client_id, deployment_id, public_key, ' .
            'lti_version, signature_method, consumer_name, consumer_version, consumer_guid, ' .
            'profile, tool_proxy, settings, protected, enabled, ' .
            'enable_from, enable_until, last_access, created, updated ' .
            "FROM {$this->dbTableNamePrefix}" . static::PLATFORM_TABLE_NAME . ' ' .
            'ORDER BY name';
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
                $platform->ltiVersion = $row->lti_version;
                $platform->signatureMethod = $row->signature_method;
                $platform->consumerName = $row->consumer_name;
                $platform->consumerVersion = $row->consumer_version;
                $platform->consumerGuid = $row->consumer_guid;
                $platform->profile = json_decode($row->profile);
                $platform->toolProxy = $row->tool_proxy;
                $settings = json_decode($row->settings, true);
                if (!is_array($settings)) {
                    $settings = @unserialize($row->settings);  // check for old serialized setting
                }
                if (!is_array($settings)) {
                    $settings = array();
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
     * @param Context $context Context object
     *
     * @return bool    True if the context object was successfully loaded
     */
    public function loadContext($context)
    {
        $ok = false;
        if (!is_null($context->getRecordId())) {
            $sql = sprintf('SELECT context_pk, consumer_pk, title, lti_context_id, type, settings, created, updated ' .
                "FROM {$this->dbTableNamePrefix}" . static::CONTEXT_TABLE_NAME . ' ' .
                'WHERE (context_pk = %d)', $context->getRecordId());
        } else {
            $sql = sprintf('SELECT context_pk, consumer_pk, title, lti_context_id, type, settings, created, updated ' .
                "FROM {$this->dbTableNamePrefix}" . static::CONTEXT_TABLE_NAME . ' ' .
                'WHERE (consumer_pk = %d) AND (lti_context_id = %s)', $context->getPlatform()->getRecordId(),
                $this->escape($context->ltiContextId));
        }
        $rsContext = $this->executeQuery($sql);
        if ($rsContext) {
            $row = pg_fetch_object($rsContext);
            if ($row) {
                $context->setRecordId(intval($row->context_pk));
                $context->setPlatformId(intval($row->consumer_pk));
                $context->title = $row->title;
                $context->ltiContextId = $row->lti_context_id;
                $context->type = $row->type;
                $settings = json_decode($row->settings, true);
                if (!is_array($settings)) {
                    $settings = @unserialize($row->settings);  // check for old serialized setting
                }
                if (!is_array($settings)) {
                    $settings = array();
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
     * @param Context $context Context object
     *
     * @return bool    True if the context object was successfully saved
     */
    public function saveContext($context)
    {
        $time = time();
        $now = date("{$this->dateFormat} {$this->timeFormat}", $time);
        $settingsValue = json_encode($context->getSettings());
        $id = $context->getRecordId();
        $consumer_pk = $context->getPlatform()->getRecordId();
        if (empty($id)) {
            $sql = sprintf("INSERT INTO {$this->dbTableNamePrefix}" . static::CONTEXT_TABLE_NAME . ' (consumer_pk, title, ' .
                'lti_context_id, type, settings, created, updated) ' .
                'VALUES (%d, %s, %s, %s, %s, %s, %s)', $consumer_pk, $this->escape($context->title),
                $this->escape($context->ltiContextId), $this->escape($context->type), $this->escape($settingsValue),
                $this->escape($now), $this->escape($now));
        } else {
            $sql = sprintf("UPDATE {$this->dbTableNamePrefix}" . static::CONTEXT_TABLE_NAME . ' SET ' .
                'title =  %s, lti_context_id = %s, type = %s, settings = %s, ' .
                'updated = %s' .
                'WHERE (consumer_pk = %d) AND (context_pk = %d)', $this->escape($context->title),
                $this->escape($context->ltiContextId), $this->escape($context->type), $this->escape($settingsValue),
                $this->escape($now), $consumer_pk, $id);
        }
        $ok = $this->executeQuery($sql);
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
     * @param Context $context Context object
     *
     * @return bool    True if the Context object was successfully deleted
     */
    public function deleteContext($context)
    {
// Delete any outstanding share keys for resource links for this context
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . ' ' .
            "WHERE resource_link_pk IN (SELECT resource_link_pk FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
            'WHERE context_pk = %d)', $context->getRecordId());
        $this->executeQuery($sql);

// Delete any users in resource links for this context
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::USER_RESULT_TABLE_NAME . ' ' .
            "WHERE resource_link_pk IN (SELECT resource_link_pk FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
            'WHERE context_pk = %d)', $context->getRecordId());
        $this->executeQuery($sql);

// Update any resource links for which this consumer is acting as a primary resource link
        $sql = sprintf("UPDATE {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
            'SET primary_resource_link_pk = null, share_approved = null ' .
            'WHERE primary_resource_link_pk IN ' .
            "(SELECT resource_link_pk FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' WHERE context_pk = %d)',
            $context->getRecordId());
        $ok = $this->executeQuery($sql);

// Delete any resource links for this consumer
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
            'WHERE context_pk = %d', $context->getRecordId());
        $this->executeQuery($sql);

// Delete context
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::CONTEXT_TABLE_NAME . ' ', 'WHERE context_pk = %d',
            $context->getRecordId());
        $ok = $this->executeQuery($sql);
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
     * @param ResourceLink $resourceLink ResourceLink object
     *
     * @return bool    True if the resource link object was successfully loaded
     */
    public function loadResourceLink($resourceLink)
    {
        $ok = false;
        if (!is_null($resourceLink->getRecordId())) {
            $sql = sprintf('SELECT resource_link_pk, context_pk, consumer_pk, title, lti_resource_link_id, settings, primary_resource_link_pk, share_approved, created, updated ' .
                "FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
                'WHERE (resource_link_pk = %d)', $resourceLink->getRecordId());
        } elseif (!is_null($resourceLink->getContext())) {
            $sql = sprintf('SELECT r.resource_link_pk, r.context_pk, r.consumer_pk, r.title, r.lti_resource_link_id, r.settings, r.primary_resource_link_pk, r.share_approved, r.created, r.updated ' .
                "FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' r ' .
                'WHERE (r.lti_resource_link_id = %s) AND ((r.context_pk = %d) OR (r.consumer_pk IN (' .
                'SELECT c.consumer_pk ' .
                "FROM {$this->dbTableNamePrefix}" . static::CONTEXT_TABLE_NAME . ' c ' .
                'WHERE (c.context_pk = %d))))', $this->escape($resourceLink->getId()), $resourceLink->getContext()->getRecordId(),
                $resourceLink->getContext()->getRecordId());
        } else {
            $sql = sprintf('SELECT r.resource_link_pk, r.context_pk, r.consumer_pk, r.title, r.lti_resource_link_id, r.settings, r.primary_resource_link_pk, r.share_approved, r.created, r.updated ' .
                "FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' r LEFT OUTER JOIN ' .
                $this->dbTableNamePrefix . static::CONTEXT_TABLE_NAME . ' c ON r.context_pk = c.context_pk ' .
                ' WHERE ((r.consumer_pk = %d) OR (c.consumer_pk = %d)) AND (lti_resource_link_id = %s)',
                $resourceLink->getPlatform()->getRecordId(), $resourceLink->getPlatform()->getRecordId(),
                $this->escape($resourceLink->getId()));
        }
        $rsResourceLink = $this->executeQuery($sql);
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
                $settings = json_decode($row->settings, true);
                if (!is_array($settings)) {
                    $settings = @unserialize($row->settings);  // check for old serialized setting
                }
                if (!is_array($settings)) {
                    $settings = array();
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
     * @param ResourceLink $resourceLink ResourceLink object
     *
     * @return bool    True if the resource link object was successfully saved
     */
    public function saveResourceLink($resourceLink)
    {
        if (is_null($resourceLink->shareApproved)) {
            $approved = 'NULL';
        } elseif ($resourceLink->shareApproved) {
            $approved = 'true';
        } else {
            $approved = 'false';
        }
        if (empty($resourceLink->primaryResourceLinkId)) {
            $primaryResourceLinkId = 'NULL';
        } else {
            $primaryResourceLinkId = strval($resourceLink->primaryResourceLinkId);
        }
        $time = time();
        $now = date("{$this->dateFormat} {$this->timeFormat}", $time);
        $settingsValue = json_encode($resourceLink->getSettings());
        if (!is_null($resourceLink->getContext())) {
            $consumerId = 'NULL';
            $contextId = strval($resourceLink->getContext()->getRecordId());
        } elseif (!is_null($resourceLink->getContextId())) {
            $consumerId = 'NULL';
            $contextId = strval($resourceLink->getContextId());
        } else {
            $consumerId = strval($resourceLink->getPlatform()->getRecordId());
            $contextId = 'NULL';
        }
        $id = $resourceLink->getRecordId();
        if (empty($id)) {
            $sql = sprintf("INSERT INTO {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' (consumer_pk, context_pk, ' .
                'title, lti_resource_link_id, settings, primary_resource_link_pk, share_approved, created, updated) ' .
                'VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)', $consumerId, $contextId, $this->escape($resourceLink->title),
                $this->escape($resourceLink->getId()), $this->escape($settingsValue), $primaryResourceLinkId, $approved,
                $this->escape($now), $this->escape($now));
        } elseif ($contextId !== 'NULL') {
            $sql = sprintf("UPDATE {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' SET ' .
                'consumer_pk = %s, title = %s, lti_resource_link_id = %s, settings = %s, ' .
                'primary_resource_link_pk = %s, share_approved = %s, updated = %s ' .
                'WHERE (context_pk = %s) AND (resource_link_pk = %d)', $consumerId, $this->escape($resourceLink->title),
                $this->escape($resourceLink->getId()), $this->escape($settingsValue), $primaryResourceLinkId, $approved,
                $this->escape($now), $contextId, $id);
        } else {
            $sql = sprintf("UPDATE {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' SET ' .
                'context_pk = NULL, title = %s, lti_resource_link_id = %s, settings = %s, ' .
                'primary_resource_link_pk = %s, share_approved = %s, updated = %s ' .
                'WHERE (consumer_pk = %s) AND (resource_link_pk = %d)', $this->escape($resourceLink->title),
                $this->escape($resourceLink->getId()), $this->escape($settingsValue), $primaryResourceLinkId, $approved,
                $this->escape($now), $consumerId, $id);
        }
        $ok = $this->executeQuery($sql);
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
     * @param ResourceLink $resourceLink ResourceLink object
     *
     * @return bool    True if the resource link object was successfully deleted
     */
    public function deleteResourceLink($resourceLink)
    {
// Delete any outstanding share keys for resource links for this consumer
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . ' ' .
            'WHERE (resource_link_pk = %d)', $resourceLink->getRecordId());
        $ok = $this->executeQuery($sql);

// Delete users
        if ($ok) {
            $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::USER_RESULT_TABLE_NAME . ' ' .
                'WHERE (resource_link_pk = %d)', $resourceLink->getRecordId());
            $ok = $this->executeQuery($sql);
        }

// Update any resource links for which this is the primary resource link
        if ($ok) {
            $sql = sprintf("UPDATE {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
                'SET primary_resource_link_pk = NULL ' .
                'WHERE (primary_resource_link_pk = %d)', $resourceLink->getRecordId());
            $ok = $this->executeQuery($sql);
        }

// Delete resource link
        if ($ok) {
            $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
                'WHERE (resource_link_pk = %s)', $resourceLink->getRecordId());
            $ok = $this->executeQuery($sql);
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
     * @param ResourceLink $resourceLink      Resource link object
     * @param bool        $localOnly True if only users within the resource link are to be returned (excluding users sharing this resource link)
     * @param int         $idScope     Scope value to use for user IDs
     *
     * @return UserResult[] Array of UserResult objects
     */
    public function getUserResultSourcedIDsResourceLink($resourceLink, $localOnly, $idScope)
    {
        $userResults = array();

        if ($localOnly) {
            $sql = sprintf('SELECT u.user_result_pk, u.lti_result_sourcedid, u.lti_user_id, u.created, u.updated ' .
                "FROM {$this->dbTableNamePrefix}" . static::USER_RESULT_TABLE_NAME . ' AS u ' .
                "INNER JOIN {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' AS rl ' .
                'ON u.resource_link_pk = rl.resource_link_pk ' .
                "WHERE (rl.resource_link_pk = %d) AND (rl.primary_resource_link_pk IS NULL)", $resourceLink->getRecordId());
        } else {
            $sql = sprintf('SELECT u.user_result_pk, u.lti_result_sourcedid, u.lti_user_id, u.created, u.updated ' .
                "FROM {$this->dbTableNamePrefix}" . static::USER_RESULT_TABLE_NAME . ' AS u ' .
                "INNER JOIN {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' AS rl ' .
                'ON u.resource_link_pk = rl.resource_link_pk ' .
                'WHERE ((rl.resource_link_pk = %d) AND (rl.primary_resource_link_pk IS NULL)) OR ' .
                '((rl.primary_resource_link_pk = %d) AND share_approved)', $resourceLink->getRecordId(),
                $resourceLink->getRecordId());
        }
        $rsUser = $this->executeQuery($sql);
        if ($rsUser) {
            while ($row = pg_fetch_object($rsUser)) {
                $userresult = LTI\UserResult::fromResourceLink($resourceLink, $row->lti_user_id);
                if (is_null($idScope)) {
                    $userResults[] = $userresult;
                } else {
                    $userResults[$userresult->getId($idScope)] = $userresult;
                }
            }
        }

        return $userResults;
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
        $shares = array();

        $sql = sprintf('SELECT c.consumer_name, r.resource_link_pk, r.title, r.share_approved ' .
            "FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' AS r ' .
            "INNER JOIN {$this->dbTableNamePrefix}" . static::PLATFORM_TABLE_NAME . ' AS c ON r.consumer_pk = c.consumer_pk ' .
            'WHERE (r.primary_resource_link_pk = %d) ' .
            'UNION ' .
            'SELECT c2.consumer_name, r2.resource_link_pk, r2.title, r2.share_approved ' .
            "FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' AS r2 ' .
            "INNER JOIN {$this->dbTableNamePrefix}" . static::CONTEXT_TABLE_NAME . ' AS x ON r2.context_pk = x.context_pk ' .
            "INNER JOIN {$this->dbTableNamePrefix}" . static::PLATFORM_TABLE_NAME . ' AS c2 ON x.consumer_pk = c2.consumer_pk ' .
            'WHERE (r2.primary_resource_link_pk = %d) ' .
            'ORDER BY consumer_name, title', $resourceLink->getRecordId(), $resourceLink->getRecordId());
        $rsShare = $this->executeQuery($sql);
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
     * @param PlatformNonce $nonce Nonce object
     *
     * @return bool    True if the nonce object was successfully loaded
     */
    public function loadPlatformNonce($nonce)
    {
        if (parent::useMemcache()) {
            $ok = parent::loadPlatformNonce($nonce);
        } else {
            $ok = false;

// Delete any expired nonce values
            $now = date("{$this->dateFormat} {$this->timeFormat}", time());
            $sql = "DELETE FROM {$this->dbTableNamePrefix}" . static::NONCE_TABLE_NAME . " WHERE expires <= '{$now}'";
            $this->executeQuery($sql);

// Load the nonce
            $sql = sprintf("SELECT value AS T FROM {$this->dbTableNamePrefix}" . static::NONCE_TABLE_NAME . ' WHERE (consumer_pk = %d) AND (value = %s)',
                $nonce->getPlatform()->getRecordId(), $this->escape($nonce->getValue()));
            $rsNonce = $this->executeQuery($sql, false);
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
     * @param PlatformNonce $nonce Nonce object
     *
     * @return bool    True if the nonce object was successfully saved
     */
    public function savePlatformNonce($nonce)
    {
        if (parent::useMemcache()) {
            $ok = parent::savePlatformNonce($nonce);
        } else {
            $expires = date("{$this->dateFormat} {$this->timeFormat}", $nonce->expires);
            $sql = sprintf("INSERT INTO {$this->dbTableNamePrefix}" . static::NONCE_TABLE_NAME . " (consumer_pk, value, expires) VALUES (%d, %s, %s)",
                $nonce->getPlatform()->getRecordId(), $this->escape($nonce->getValue()), $this->escape($expires));
            $ok = $this->executeQuery($sql);
        }

        return $ok;
    }

    /**
     * Delete nonce object.
     *
     * @param PlatformNonce $nonce Nonce object
     *
     * @return bool    True if the nonce object was successfully deleted
     */
    public function deletePlatformNonce($nonce)
    {
        if (parent::useMemcache()) {
            $ok = parent::deletePlatformNonce($nonce);
        } else {
            $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::NONCE_TABLE_NAME . ' WHERE (consumer_pk = %d) AND (value = %s)',
                $nonce->getPlatform()->getRecordId(), $this->escape($nonce->getValue()));
            $ok = $this->executeQuery($sql);
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
     * @return bool    True if the nonce object was successfully loaded
     */
    public function loadAccessToken($accessToken)
    {
        if (parent::useMemcache()) {
            $ok = parent::loadAccessToken($accessToken);
        } else {
            $ok = false;
            $consumer_pk = $accessToken->getPlatform()->getRecordId();
            $sql = sprintf('SELECT scopes, token, expires, created, updated ' .
                "FROM {$this->dbTableNamePrefix}" . static::ACCESS_TOKEN_TABLE_NAME . ' ' .
                'WHERE (consumer_pk = %d)', $consumer_pk);
            $rsAccessToken = $this->executeQuery($sql, false);
            if ($rsAccessToken) {
                $row = pg_fetch_object($rsAccessToken);
                if ($row) {
                    $scopes = json_decode($row->scopes, true);
                    if (!is_array($scopes)) {
                        $scopes = array();
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
     * @return bool    True if the access token object was successfully saved
     */
    public function saveAccessToken($accessToken)
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
                $sql = sprintf("INSERT INTO {$this->dbTableNamePrefix}" . static::ACCESS_TOKEN_TABLE_NAME . ' ' .
                    '(consumer_pk, scopes, token, expires, created, updated) ' .
                    'VALUES (%d, %s, %s, %s, %s, %s)', $consumer_pk, $this->escape($scopes), $this->escape($token),
                    $this->escape($expires), $this->escape($now), $this->escape($now));
            } else {
                $sql = sprintf('UPDATE ' . $this->dbTableNamePrefix . static::ACCESS_TOKEN_TABLE_NAME . ' ' .
                    'SET scopes = %s, token = %s, expires = %s, updated = %s WHERE consumer_pk = %d', $this->escape($scopes),
                    $this->escape($token), $this->escape($expires), $this->escape($now), $consumer_pk);
            }
            $ok = $this->executeQuery($sql);
        }

        return $ok;
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
        $ok = false;

// Clear expired share keys
        $now = date("{$this->dateFormat} {$this->timeFormat}", time());
        $sql = "DELETE FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . " WHERE expires <= '{$now}'";
        $this->executeQuery($sql);

// Load share key
        $id = pg_escape_string($this->db, $shareKey->getId());
        $sql = 'SELECT resource_link_pk, auto_approve, expires ' .
            "FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . ' ' .
            "WHERE share_key_id = '{$id}'";
        $rsShareKey = $this->executeQuery($sql);
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
     * @param ResourceLinkShareKey $shareKey Resource link share key object
     *
     * @return bool    True if the resource link share key object was successfully saved
     */
    public function saveResourceLinkShareKey($shareKey)
    {
        if ($shareKey->autoApprove) {
            $approve = 'true';
        } else {
            $approve = 'false';
        }
        $expires = date("{$this->dateFormat} {$this->timeFormat}", $shareKey->expires);
        $sql = sprintf("INSERT INTO {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . ' ' .
            '(share_key_id, resource_link_pk, auto_approve, expires) ' .
            "VALUES (%s, %d, {$approve}, '{$expires}')", $this->escape($shareKey->getId()), $shareKey->resourceLinkId);
        $ok = $this->executeQuery($sql);

        return $ok;
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
        $sql = "DELETE FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . " WHERE share_key_id = '{$shareKey->getId()}'";

        $ok = $this->executeQuery($sql);

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
     * @param UserResult $userresult UserResult object
     *
     * @return bool    True if the user object was successfully loaded
     */
    public function loadUserResult($userresult)
    {
        $ok = false;
        if (!is_null($userresult->getRecordId())) {
            $sql = sprintf('SELECT user_result_pk, resource_link_pk, lti_user_id, lti_result_sourcedid, created, updated ' .
                "FROM {$this->dbTableNamePrefix}" . static::USER_RESULT_TABLE_NAME . ' ' .
                'WHERE (user_result_pk = %d)', $userresult->getRecordId());
        } else {
            $sql = sprintf('SELECT user_result_pk, resource_link_pk, lti_user_id, lti_result_sourcedid, created, updated ' .
                "FROM {$this->dbTableNamePrefix}" . static::USER_RESULT_TABLE_NAME . ' ' .
                'WHERE (resource_link_pk = %d) AND (lti_user_id = %s)', $userresult->getResourceLink()->getRecordId(),
                $this->escape($userresult->getId(LTI\Tool::ID_SCOPE_ID_ONLY)));
        }
        $rsUserResult = $this->executeQuery($sql);
        if ($rsUserResult) {
            $row = pg_fetch_object($rsUserResult);
            if ($row) {
                $userresult->setRecordId(intval($row->user_result_pk));
                $userresult->setResourceLinkId(intval($row->resource_link_pk));
                $userresult->ltiUserId = $row->lti_user_id;
                $userresult->ltiResultSourcedId = $row->lti_result_sourcedid;
                $userresult->created = strtotime($row->created);
                $userresult->updated = strtotime($row->updated);
                $ok = true;
            }
        }

        return $ok;
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
        $time = time();
        $now = date("{$this->dateFormat} {$this->timeFormat}", $time);
        if (is_null($userresult->created)) {
            $sql = sprintf("INSERT INTO {$this->dbTableNamePrefix}" . static::USER_RESULT_TABLE_NAME . ' (resource_link_pk, ' .
                'lti_user_id, lti_result_sourcedid, created, updated) ' .
                'VALUES (%d, %s, %s, %s, %s)', $userresult->getResourceLink()->getRecordId(),
                $this->escape($userresult->getId(LTI\Tool::ID_SCOPE_ID_ONLY)), $this->escape($userresult->ltiResultSourcedId),
                $this->escape($now), $this->escape($now));
        } else {
            $sql = sprintf("UPDATE {$this->dbTableNamePrefix}" . static::USER_RESULT_TABLE_NAME . ' ' .
                'SET lti_result_sourcedid = %s, updated = %s ' .
                'WHERE (user_result_pk = %d)', $this->escape($userresult->ltiResultSourcedId), $this->escape($now),
                $userresult->getRecordId());
        }
        $ok = $this->executeQuery($sql);
        if ($ok) {
            if (is_null($userresult->created)) {
                $userresult->setRecordId($this->insert_id());
                $userresult->created = $time;
            }
            $userresult->updated = $time;
        }

        return $ok;
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
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::USER_RESULT_TABLE_NAME . ' ' .
            'WHERE (user_result_pk = %d)', $userresult->getRecordId());
        $ok = $this->executeQuery($sql);

        if ($ok) {
            $userresult->initialize();
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
     * @return bool    True if the tool object was successfully loaded
     */
    public function loadTool($tool)
    {
        $ok = false;
        if (!is_null($tool->getRecordId())) {
            $sql = sprintf('SELECT tool_pk, name, consumer_key, secret, ' .
                'message_url, initiate_login_url, redirection_uris, public_key, ' .
                'lti_version, signature_method, settings, enabled, ' .
                'enable_from, enable_until, last_access, created, updated ' .
                "FROM {$this->dbTableNamePrefix}" . static::TOOL_TABLE_NAME . ' ' .
                'WHERE tool_pk = %d', $tool->getRecordId());
        } elseif (!empty($tool->initiateLoginUrl)) {
            $sql = sprintf('SELECT tool_pk, name, consumer_key, secret, ' .
                'message_url, initiate_login_url, redirection_uris, public_key, ' .
                'lti_version, signature_method, settings, enabled, ' .
                'enable_from, enable_until, last_access, created, updated ' .
                "FROM {$this->dbTableNamePrefix}" . static::TOOL_TABLE_NAME . ' ' .
                'WHERE initiate_login_url = %s', $this->escape($tool->initiateLoginUrl));
        } else {
            $sql = sprintf('SELECT tool_pk, name, consumer_key, secret, ' .
                'message_url, initiate_login_url, redirection_uris, public_key, ' .
                'lti_version, signature_method, settings, enabled, ' .
                'enable_from, enable_until, last_access, created, updated ' .
                "FROM {$this->dbTableNamePrefix}" . static::TOOL_TABLE_NAME . ' ' .
                'WHERE consumer_key = %s', $this->escape($tool->getKey()));
        }
        $rsTool = $this->executeQuery($sql);
        if ($rsTool) {
            $row = pg_fetch_object($rsTool);
            if ($row) {
                $tool->setRecordId(intval($row->tool_pk));
                $tool->name = $row->name;
                $tool->setkey($row->consumer_key);
                $tool->secret = $row->secret;
                $tool->messageUrl = $row->message_url;
                $tool->initiateLoginUrl = $row->initiate_login_url;
                $tool->redirectionUris = json_decode($row->redirection_uris, true);
                if (!is_array($tool->redirectionUris)) {
                    $tool->redirectionUris = array();
                }
                $tool->rsaKey = $row->public_key;
                $tool->ltiVersion = $row->lti_version;
                $tool->signatureMethod = $row->signature_method;
                $settings = json_decode($row->settings, true);
                if (!is_array($settings)) {
                    $settings = array();
                }
                $tool->setSettings($settings);
                $tool->enabled = $row->enabled;
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
     * @return bool    True if the tool object was successfully saved
     */
    public function saveTool($tool)
    {
        $id = $tool->getRecordId();
        $enabled = ($tool->enabled) ? 'true' : 'false';
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
            $sql = sprintf("INSERT INTO {$this->dbTableNamePrefix}" . static::TOOL_TABLE_NAME . ' (name, consumer_key, secret, ' .
                'message_url, initiate_login_url, redirection_uris, public_key, ' .
                'lti_version, signature_method, settings, enabled, enable_from, enable_until, ' .
                'last_access, created, updated) ' .
                'VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)', $this->escape($tool->name),
                $this->escape($tool->getKey()), $this->escape($tool->secret), $this->escape($tool->messageUrl),
                $this->escape($tool->initiateLoginUrl), $this->escape($redirectionUrisValue), $this->escape($tool->rsaKey),
                $this->escape($tool->ltiVersion), $this->escape($tool->signatureMethod), $this->escape($settingsValue), $enabled,
                $this->escape($from), $this->escape($until), $this->escape($last), $this->escape($now), $this->escape($now));
        } else {
            $sql = sprintf("UPDATE {$this->dbTableNamePrefix}" . static::TOOL_TABLE_NAME . ' SET ' .
                'name = %s, consumer_key = %s, secret = %s, ' .
                'message_url = %s, initiate_login_url = %s, redirection_uris = %s, public_key = %s, ' .
                'lti_version = %s, signature_method = %s, settings = %s, enabled = %s, enable_from = %s, enable_until = %s, ' .
                'last_access = %s, updated = %s ' .
                'WHERE tool_pk = %d', $this->escape($tool->name), $this->escape($tool->getKey()), $this->escape($tool->secret),
                $this->escape($tool->messageUrl), $this->escape($tool->initiateLoginUrl), $this->escape($redirectionUrisValue),
                $this->escape($tool->rsaKey), $this->escape($tool->ltiVersion), $this->escape($tool->signatureMethod),
                $this->escape($settingsValue), $enabled, $this->escape($from), $this->escape($until), $this->escape($last),
                $this->escape($now), $tool->getRecordId());
        }
        $ok = $this->executeQuery($sql);
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
     * @return bool    True if the tool object was successfully deleted
     */
    public function deleteTool($tool)
    {
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::TOOL_TABLE_NAME . ' ' .
            'WHERE tool_pk = %d', $tool->getRecordId());
        $ok = $this->executeQuery($sql);

        if ($ok) {
            $tool->initialize();
        }

        return $ok;
    }

    /**
     * Load tool objects.
     *
     * @return Tool[] Array of all defined Tool objects
     */
    public function getTools()
    {
        $tools = array();

        $sql = 'SELECT tool_pk, name, consumer_key, secret, ' .
            'message_url, initiate_login_url, redirection_uris, public_key, ' .
            'lti_version, signature_method, settings, enabled, ' .
            'enable_from, enable_until, last_access, created, updated ' .
            "FROM {$this->dbTableNamePrefix}" . static::TOOL_TABLE_NAME . ' ' .
            'ORDER BY name';
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
                $tool->redirectionUris = json_decode($row->redirection_uris, true);
                if (!is_array($tool->redirectionUris)) {
                    $tool->redirectionUris = array();
                }
                $tool->rsaKey = $row->public_key;
                $tool->ltiVersion = $row->lti_version;
                $tool->signatureMethod = $row->signature_method;
                $settings = json_decode($row->settings, true);
                if (!is_array($settings)) {
                    $settings = array();
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
                    $platform->lastAccess = strtotime($row->last_access);
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
    private function insert_id()
    {
        $rsId = $this->executeQuery('SELECT lastval();');
        $row = pg_fetch_row($rsId);
        return intval($row[0]);
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
        if (is_null($value)) {
            $value = 'null';
        } else {
            $value = pg_escape_string($this->db, $value);
            if ($addQuotes) {
                $value = "'{$value}'";
            }
        }

        return $value;
    }

    /**
     * Execute a database query.
     *
     * Info and debug messages are generated.
     *
     * @param string $sql          SQL statement
     * @param bool   $reportError  True if errors are to be reported
     *
     * @return resource|bool  The result resource, or false on error.
     */
    private function executeQuery($sql, $reportError = true)
    {
        $res = pg_query($this->db, $sql);
        if (($res === false) && $reportError) {
            Util::logError($sql . PHP_EOL . 'Error: ' . pg_last_error($this->db));
        } else {
            Util::logDebug($sql);
        }

        return $res;
    }

}
