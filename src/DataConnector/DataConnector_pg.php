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
 * Class to represent an LTI Data Connector for PostgreSQL
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @version  3.1.0
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
###
#    NB This class assumes that a MySQLi connection has already been opened to the appropriate schema
###


class DataConnector_pg extends DataConnector
{
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
        $ok = false;
        if (!is_null($consumer->getRecordId())) {
            $sql = sprintf('SELECT consumer_pk, name, consumer_key256, consumer_key, secret, lti_version, ' .
                'signature_method, consumer_name, consumer_version, consumer_guid, ' .
                'profile, tool_proxy, settings, protected, enabled, ' .
                'enable_from, enable_until, last_access, created, updated ' .
                "FROM {$this->dbTableNamePrefix}" . static::CONSUMER_TABLE_NAME . ' ' .
                "WHERE consumer_pk = %d", $consumer->getRecordId());
        } else {
            $key256 = static::getConsumerKey($consumer->getKey());
            $sql = sprintf('SELECT consumer_pk, name, consumer_key256, consumer_key, secret, lti_version, ' .
                'signature_method, consumer_name, consumer_version, consumer_guid, ' .
                'profile, tool_proxy, settings, protected, enabled, ' .
                'enable_from, enable_until, last_access, created, updated ' .
                "FROM {$this->dbTableNamePrefix}" . static::CONSUMER_TABLE_NAME . ' ' .
                "WHERE consumer_key256 = %s", $this->escape($key256));
        }
        $rsConsumer = pg_query($this->db, $sql);
        if ($rsConsumer) {
            while ($row = pg_fetch_object($rsConsumer)) {
                if (empty($key256) || empty($row->consumer_key) || ($consumer->getKey() === $row->consumer_key)) {
                    $consumer->setRecordId(intval($row->consumer_pk));
                    $consumer->name = $row->name;
                    $consumer->setkey(empty($row->consumer_key) ? $row->consumer_key256 : $row->consumer_key);
                    $consumer->secret = $row->secret;
                    $consumer->ltiVersion = $row->lti_version;
                    $consumer->signatureMethod = $row->signature_method;
                    $consumer->consumerName = $row->consumer_name;
                    $consumer->consumerVersion = $row->consumer_version;
                    $consumer->consumerGuid = $row->consumer_guid;
                    $consumer->profile = json_decode($row->profile);
                    $consumer->toolProxy = $row->tool_proxy;
                    $settings = json_decode($row->settings, TRUE);
                    if (!is_array($settings)) {
                        $settings = @unserialize($row->settings);  // check for old serialized setting
                    }
                    if (!is_array($settings)) {
                        $settings = array();
                    }
                    $consumer->setSettings($settings);
                    $consumer->protected = $row->protected;
                    $consumer->enabled = $row->enabled;
                    $consumer->enableFrom = null;
                    if (!is_null($row->enable_from)) {
                        $consumer->enableFrom = strtotime($row->enable_from);
                    }
                    $consumer->enableUntil = null;
                    if (!is_null($row->enable_until)) {
                        $consumer->enableUntil = strtotime($row->enable_until);
                    }
                    $consumer->lastAccess = null;
                    if (!is_null($row->last_access)) {
                        $consumer->lastAccess = strtotime($row->last_access);
                    }
                    $consumer->created = strtotime($row->created);
                    $consumer->updated = strtotime($row->updated);
                    $ok = true;
                    break;
                }
            }
            pg_free_result($rsConsumer);
        }

        return $ok;
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
        $id = $consumer->getRecordId();
        $key = $consumer->getKey();
        $key256 = static::getConsumerKey($key);
        if ($key === $key256) {
            $key = null;
        }
        $protected = ($consumer->protected) ? 'true' : 'false';
        $enabled = ($consumer->enabled) ? 'true' : 'false';
        $profile = (!empty($consumer->profile)) ? json_encode($consumer->profile) : null;
        $settingsValue = json_encode($consumer->getSettings());
        $time = time();
        $now = date("{$this->dateFormat} {$this->timeFormat}", $time);
        $from = null;
        if (!is_null($consumer->enableFrom)) {
            $from = date("{$this->dateFormat} {$this->timeFormat}", $consumer->enableFrom);
        }
        $until = null;
        if (!is_null($consumer->enableUntil)) {
            $until = date("{$this->dateFormat} {$this->timeFormat}", $consumer->enableUntil);
        }
        $last = null;
        if (!is_null($consumer->lastAccess)) {
            $last = date($this->dateFormat, $consumer->lastAccess);
        }
        if (empty($id)) {
            $sql = sprintf("INSERT INTO {$this->dbTableNamePrefix}" . static::CONSUMER_TABLE_NAME . ' (consumer_key256, consumer_key, name, ' .
                'secret, lti_version, signature_method, consumer_name, consumer_version, consumer_guid, profile, ' .
                'tool_proxy, settings, protected, enabled, ' .
                'enable_from, enable_until, last_access, created, updated) ' .
                'VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)', $this->escape($key256),
                $this->escape($key), $this->escape($consumer->name), $this->escape($consumer->secret),
                $this->escape($consumer->ltiVersion), $this->escape($consumer->signatureMethod),
                $this->escape($consumer->consumerName), $this->escape($consumer->consumerVersion),
                $this->escape($consumer->consumerGuid), $this->escape($profile), $this->escape($consumer->toolProxy),
                $this->escape($settingsValue), $protected, $enabled, $this->escape($from), $this->escape($until),
                $this->escape($last), $this->escape($now), $this->escape($now));
        } else {
            $sql = sprintf("UPDATE {$this->dbTableNamePrefix}" . static::CONSUMER_TABLE_NAME . ' SET ' .
                'consumer_key256 = %s, consumer_key = %s, ' .
                'name = %s, secret= %s, lti_version = %s, signature_method = %s, consumer_name = %s, consumer_version = %s, consumer_guid = %s, ' .
                'profile = %s, tool_proxy = %s, settings = %s, ' .
                'protected = %s, enabled = %s, enable_from = %s, enable_until = %s, last_access = %s, updated = %s ' .
                'WHERE consumer_pk = %d', $this->escape($key256), $this->escape($key), $this->escape($consumer->name),
                $this->escape($consumer->secret), $this->escape($consumer->ltiVersion), $this->escape($consumer->signatureMethod),
                $this->escape($consumer->consumerName), $this->escape($consumer->consumerVersion),
                $this->escape($consumer->consumerGuid), $this->escape($profile), $this->escape($consumer->toolProxy),
                $this->escape($settingsValue), $protected, $enabled, $this->escape($from), $this->escape($until),
                $this->escape($last), $this->escape($now), $consumer->getRecordId());
        }
        $ok = pg_query($this->db, $sql);
        if ($ok) {
            if (empty($id)) {
                $consumer->setRecordId($this->insert_id());
                $consumer->created = $time;
            }
            $consumer->updated = $time;
        }

        return $ok;
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
// Delete any nonce values for this consumer
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::NONCE_TABLE_NAME . ' WHERE consumer_pk = %d',
            $consumer->getRecordId());
        pg_query($this->db, $sql);

// Delete any outstanding share keys for resource links for this consumer
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . ' ' .
            "WHERE resource_link_pk IN (SELECT resource_link_pk FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
            'WHERE consumer_pk = %d)', $consumer->getRecordId());
        pg_query($this->db, $sql);

// Delete any outstanding share keys for resource links for contexts in this consumer
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . ' ' .
            "WHERE resource_link_pk IN (SELECT resource_link_pk FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' rl ' .
            "INNER JOIN {$this->dbTableNamePrefix}" . static::CONTEXT_TABLE_NAME . ' c ON rl.context_pk = c.context_pk WHERE c.consumer_pk = %d)',
            $consumer->getRecordId());
        pg_query($this->db, $sql);

// Delete any users in resource links for this consumer
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::USER_RESULT_TABLE_NAME . ' ' .
            "WHERE resource_link_pk IN (SELECT resource_link_pk FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
            'WHERE consumer_pk = %d)', $consumer->getRecordId());
        pg_query($this->db, $sql);

// Delete any users in resource links for contexts in this consumer
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::USER_RESULT_TABLE_NAME . ' ' .
            "WHERE resource_link_pk IN (SELECT resource_link_pk FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' rl ' .
            "INNER JOIN {$this->dbTableNamePrefix}" . static::CONTEXT_TABLE_NAME . ' c ON rl.context_pk = c.context_pk WHERE c.consumer_pk = %d)',
            $consumer->getRecordId());
        pg_query($this->db, $sql);

// Update any resource links for which this consumer is acting as a primary resource link
        $sql = sprintf("UPDATE {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
            'SET primary_resource_link_pk = NULL, share_approved = NULL ' .
            'WHERE primary_resource_link_pk IN ' .
            "(SELECT resource_link_pk FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
            'WHERE consumer_pk = %d)', $consumer->getRecordId());
        $ok = pg_query($this->db, $sql);

// Update any resource links for contexts in which this consumer is acting as a primary resource link
        $sql = sprintf("UPDATE {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
            'SET primary_resource_link_pk = NULL, share_approved = NULL ' .
            'WHERE primary_resource_link_pk IN ' .
            "(SELECT rl.resource_link_pk FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' rl ' .
            "INNER JOIN {$this->dbTableNamePrefix}" . static::CONTEXT_TABLE_NAME . ' c ON rl.context_pk = c.context_pk ' .
            'WHERE c.consumer_pk = %d)', $consumer->getRecordId());
        $ok = pg_query($this->db, $sql);

// Delete any resource links for this consumer
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
            'WHERE consumer_pk = %d', $consumer->getRecordId());
        pg_query($this->db, $sql);

// Delete any resource links for contexts in this consumer
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
            'WHERE context_pk IN (' .
            "SELECT context_pk FROM {$this->dbTableNamePrefix}" . static::CONTEXT_TABLE_NAME . ' ' . 'WHERE consumer_pk = %d)',
            $consumer->getRecordId());
        pg_query($this->db, $sql);

// Delete any contexts for this consumer
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::CONTEXT_TABLE_NAME . ' ' .
            'WHERE consumer_pk = %d', $consumer->getRecordId());
        pg_query($this->db, $sql);

// Delete consumer
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::CONSUMER_TABLE_NAME . ' ' .
            'WHERE consumer_pk = %d', $consumer->getRecordId());
        $ok = pg_query($this->db, $sql);

        if ($ok) {
            $consumer->initialize();
        }

        return $ok;
    }

    /**
     * Load all tool consumers from the database.
     *
     * @return ToolConsumer[]    An array of the ToolConsumer objects
     */
    public function getToolConsumers()
    {
        $consumers = array();

        $sql = 'SELECT consumer_pk, consumer_key256, consumer_key, name, secret, lti_version, ' .
            'signature_method, consumer_name, consumer_version, consumer_guid, ' .
            'profile, tool_proxy, settings, ' .
            'protected, enabled, enable_from, enable_until, last_access, created, updated ' .
            "FROM {$this->dbTableNamePrefix}" . static::CONSUMER_TABLE_NAME . ' ' .
            'ORDER BY name';
        $rsConsumers = pg_query($this->db, $sql);
        if ($rsConsumers) {
            while ($row = pg_fetch_object($rsConsumers)) {
                $key = empty($row->consumer_key) ? $row->consumer_key256 : $row->consumer_key;
                $consumer = new ToolConsumer($key, $this);
                $consumer->setRecordId(intval($row->consumer_pk));
                $consumer->name = $row->name;
                $consumer->secret = $row->secret;
                $consumer->ltiVersion = $row->lti_version;
                $consumer->signatureMethod = $row->signature_method;
                $consumer->consumerName = $row->consumer_name;
                $consumer->consumerVersion = $row->consumer_version;
                $consumer->consumerGuid = $row->consumer_guid;
                $consumer->profile = json_decode($row->profile);
                $consumer->toolProxy = $row->tool_proxy;
                $settings = json_decode($row->settings, TRUE);
                if (!is_array($settings)) {
                    $settings = @unserialize($row->settings);  // check for old serialized setting
                }
                if (!is_array($settings)) {
                    $settings = array();
                }
                $consumer->setSettings($settings);
                $consumer->protected = (intval($row->protected) === 1);
                $consumer->enabled = (intval($row->enabled) === 1);
                $consumer->enableFrom = null;
                if (!is_null($row->enable_from)) {
                    $consumer->enableFrom = strtotime($row->enable_from);
                }
                $consumer->enableUntil = null;
                if (!is_null($row->enable_until)) {
                    $consumer->enableUntil = strtotime($row->enable_until);
                }
                $consumer->lastAccess = null;
                if (!is_null($row->last_access)) {
                    $consumer->lastAccess = strtotime($row->last_access);
                }
                $consumer->created = strtotime($row->created);
                $consumer->updated = strtotime($row->updated);
                $consumers[] = $consumer;
            }
            pg_free_result($rsConsumers);
        }

        return $consumers;
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
                'WHERE (consumer_pk = %d) AND (lti_context_id = %s)', $context->getConsumer()->getRecordId(),
                $this->escape($context->ltiContextId));
        }
        $rs_context = pg_query($this->db, $sql);
        if ($rs_context) {
            $row = pg_fetch_object($rs_context);
            if ($row) {
                $context->setRecordId(intval($row->context_pk));
                $context->setConsumerId(intval($row->consumer_pk));
                $context->title = $row->title;
                $context->ltiContextId = $row->lti_context_id;
                $context->type = $row->type;
                $settings = json_decode($row->settings, TRUE);
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
        $consumer_pk = $context->getConsumer()->getRecordId();
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
        $ok = pg_query($this->db, $sql);
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
        pg_query($this->db, $sql);

// Delete any users in resource links for this context
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::USER_RESULT_TABLE_NAME . ' ' .
            "WHERE resource_link_pk IN (SELECT resource_link_pk FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
            'WHERE context_pk = %d)', $context->getRecordId());
        pg_query($this->db, $sql);

// Update any resource links for which this consumer is acting as a primary resource link
        $sql = sprintf("UPDATE {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
            'SET primary_resource_link_pk = null, share_approved = null ' .
            'WHERE primary_resource_link_pk IN ' .
            "(SELECT resource_link_pk FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' WHERE context_pk = %d)',
            $context->getRecordId());
        $ok = pg_query($this->db, $sql);

// Delete any resource links for this consumer
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
            'WHERE context_pk = %d', $context->getRecordId());
        pg_query($this->db, $sql);

// Delete context
        $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::CONTEXT_TABLE_NAME . ' ', 'WHERE context_pk = %d',
            $context->getRecordId());
        $ok = pg_query($this->db, $sql);
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
            $sql = sprintf('SELECT resource_link_pk, context_pk, consumer_pk, title, lti_resource_link_id, settings, primary_resource_link_pk, share_approved, created, updated ' .
                "FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
                'WHERE (context_pk = %d) AND (lti_resource_link_id = %s)', $resourceLink->getContext()->getRecordId(),
                $this->escape($resourceLink->getId()));
        } else {
            $sql = sprintf('SELECT r.resource_link_pk, r.context_pk, r.consumer_pk, r.title, r.lti_resource_link_id, r.settings, r.primary_resource_link_pk, r.share_approved, r.created, r.updated ' .
                "FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' r LEFT OUTER JOIN ' .
                $this->dbTableNamePrefix . static::CONTEXT_TABLE_NAME . ' c ON r.context_pk = c.context_pk ' .
                ' WHERE ((r.consumer_pk = %d) OR (c.consumer_pk = %d)) AND (lti_resource_link_id = %s)',
                $resourceLink->getConsumer()->getRecordId(), $resourceLink->getConsumer()->getRecordId(),
                $this->escape($resourceLink->getId()));
        }
        $rsContext = pg_query($this->db, $sql);
        if ($rsContext) {
            $row = pg_fetch_object($rsContext);
            if ($row) {
                $resourceLink->setRecordId(intval($row->resource_link_pk));
                if (!is_null($row->context_pk)) {
                    $resourceLink->setContextId(intval($row->context_pk));
                } else {
                    $resourceLink->setContextId(null);
                }
                if (!is_null($row->consumer_pk)) {
                    $resourceLink->setConsumerId(intval($row->consumer_pk));
                } else {
                    $resourceLink->setConsumerId(null);
                }
                $resourceLink->title = $row->title;
                $resourceLink->ltiResourceLinkId = $row->lti_resource_link_id;
                $settings = json_decode($row->settings, TRUE);
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
            $consumerId = strval($resourceLink->getConsumer()->getRecordId());
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
                'context_pk = %s, title = %s, lti_resource_link_id = %s, settings = %s, ' .
                'primary_resource_link_pk = %s, share_approved = %s, updated = %s ' .
                'WHERE (consumer_pk = %s) AND (resource_link_pk = %d)', $contextId, $this->escape($resourceLink->title),
                $this->escape($resourceLink->getId()), $this->escape($settingsValue), $primaryResourceLinkId, $approved,
                $this->escape($now), $consumerId, $id);
        }
        $ok = pg_query($this->db, $sql);
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
        $ok = pg_query($this->db, $sql);

// Delete users
        if ($ok) {
            $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::USER_RESULT_TABLE_NAME . ' ' .
                'WHERE (resource_link_pk = %d)', $resourceLink->getRecordId());
            $ok = pg_query($this->db, $sql);
        }

// Update any resource links for which this is the primary resource link
        if ($ok) {
            $sql = sprintf("UPDATE {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
                'SET primary_resource_link_pk = NULL ' .
                'WHERE (primary_resource_link_pk = %d)', $resourceLink->getRecordId());
            $ok = pg_query($this->db, $sql);
        }

// Delete resource link
        if ($ok) {
            $sql = sprintf("DELETE FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' ' .
                'WHERE (resource_link_pk = %s)', $resourceLink->getRecordId());
            $ok = pg_query($this->db, $sql);
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
        $rsUser = pg_query($this->db, $sql);
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
            "INNER JOIN {$this->dbTableNamePrefix}" . static::CONSUMER_TABLE_NAME . ' AS c ON r.consumer_pk = c.consumer_pk ' .
            'WHERE (r.primary_resource_link_pk = %d) ' .
            'UNION ' .
            'SELECT c2.consumer_name, r2.resource_link_pk, r2.title, r2.share_approved ' .
            "FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' AS r2 ' .
            "INNER JOIN {$this->dbTableNamePrefix}" . static::CONTEXT_TABLE_NAME . ' AS x ON r2.context_pk = x.context_pk ' .
            "INNER JOIN {$this->dbTableNamePrefix}" . static::CONSUMER_TABLE_NAME . ' AS c2 ON x.consumer_pk = c2.consumer_pk ' .
            'WHERE (r2.primary_resource_link_pk = %d) ' .
            'ORDER BY consumer_name, title', $resourceLink->getRecordId(), $resourceLink->getRecordId());
        $rsShare = pg_query($this->db, $sql);
        if ($rsShare) {
            while ($row = pg_fetch_object($rsShare)) {
                $share = new LTI\ResourceLinkShare();
                $share->resourceLinkId = intval($row->resource_link_pk);
                $share->approved = (intval($row->share_approved) === 1);
                $shares[] = $share;
            }
        }

        return $shares;
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
        $ok = false;

// Delete any expired nonce values
        $now = date("{$this->dateFormat} {$this->timeFormat}", time());
        $sql = "DELETE FROM {$this->dbTableNamePrefix}" . static::NONCE_TABLE_NAME . " WHERE expires <= '{$now}'";
        pg_query($this->db, $sql);

// Load the nonce
        $sql = sprintf("SELECT value AS T FROM {$this->dbTableNamePrefix}" . static::NONCE_TABLE_NAME . ' WHERE (consumer_pk = %d) AND (value = %s)',
            $nonce->getConsumer()->getRecordId(), $this->escape($nonce->getValue()));
        $rs_nonce = pg_query($this->db, $sql);
        if ($rs_nonce) {
            if (pg_fetch_object($rs_nonce)) {
                $ok = true;
            }
        }

        return $ok;
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
        $expires = date("{$this->dateFormat} {$this->timeFormat}", $nonce->expires);
        $sql = sprintf("INSERT INTO {$this->dbTableNamePrefix}" . static::NONCE_TABLE_NAME . " (consumer_pk, value, expires) VALUES (%d, %s, %s)",
            $nonce->getConsumer()->getRecordId(), $this->escape($nonce->getValue()), $this->escape($expires));
        $ok = pg_query($this->db, $sql);

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
        pg_query($this->db, $sql);

// Load share key
        $id = pg_escape_string($this->db, $shareKey->getId());
        $sql = 'SELECT resource_link_pk, auto_approve, expires ' .
            "FROM {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . ' ' .
            "WHERE share_key_id = '{$id}'";
        $rsShareKey = pg_query($this->db, $sql);
        if ($rsShareKey) {
            $row = pg_fetch_object($rsShareKey);
            if ($row && (intval($row->resource_link_pk) === $shareKey->resourceLinkId)) {
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
        $ok = pg_query($this->db, $sql);

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

        $ok = pg_query($this->db, $sql);

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
                $this->escape($userresult->getId(LTI\ToolProvider::ID_SCOPE_ID_ONLY)));
        }
        $rsUser = pg_query($this->db, $sql);
        if ($rsUser) {
            $row = pg_fetch_object($rsUser);
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
                $this->escape($userresult->getId(LTI\ToolProvider::ID_SCOPE_ID_ONLY)),
                $this->escape($userresult->ltiResultSourcedId), $this->escape($now), $this->escape($now));
        } else {
            $sql = sprintf("UPDATE {$this->dbTableNamePrefix}" . static::USER_RESULT_TABLE_NAME . ' ' .
                'SET lti_result_sourcedid = %s, updated = %s ' .
                'WHERE (user_result_pk = %d)', $this->escape($userresult->ltiResultSourcedId), $this->escape($now),
                $userresult->getRecordId());
        }
        $ok = pg_query($this->db, $sql);
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
        $ok = pg_query($this->db, $sql);

        if ($ok) {
            $userresult->initialize();
        }

        return $ok;
    }

    private function insert_id()
    {
        $rsId = pg_query('SELECT lastval();');
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

}
