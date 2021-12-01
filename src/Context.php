<?php

namespace ceLTIc\LTI;

use ceLTIc\LTI\Service;
use ceLTIc\LTI\Http\HttpMessage;
use ceLTIc\LTI\ApiHook\ApiHook;
use ceLTIc\LTI\Util;

/**
 * Class to represent a platform context
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class Context
{
    use ApiHook;

    /**
     * Context ID as supplied in the last connection request.
     *
     * @var string|null $ltiContextId
     */
    public $ltiContextId = null;

    /**
     * Context title.
     *
     * @var string|null $title
     */
    public $title = null;

    /**
     * Context type.
     *
     * @var string|null $type
     */
    public $type = null;

    /**
     * User group sets (null if the platform does not support the groups enhancement)
     *
     * A group set is represented by an associative array with the following elements:
     *   - title
     *   - groups (array of group IDs)
     *   - num_members
     *   - num_staff
     *   - num_learners
     * The array key value is the group set ID.
     *
     * @var array|null $groupSets
     */
    public $groupSets = null;

    /**
     * User groups (null if the platform does not support the groups enhancement)
     *
     * A group is represented by an associative array with the following elements:
     *   - title
     *   - set (ID of group set, omitted if the group is not part of a set)
     * The array key value is the group ID.
     *
     * @var array|null $groups
     */
    public $groups = null;

    /**
     * HttpMessage object for last service request.
     *
     * @var HttpMessage|null $lastServiceRequest
     */
    public $lastServiceRequest = null;

    /**
     * Timestamp for when the object was created.
     *
     * @var int|null $created
     */
    public $created = null;

    /**
     * Timestamp for when the object was last updated.
     *
     * @var int|null $updated
     */
    public $updated = null;

    /**
     * Platform for this context.
     *
     * @var Platform|null $platform
     */
    private $platform = null;

    /**
     * Platform ID for this context.
     *
     * @var int|null $platformId
     */
    private $platformId = null;

    /**
     * ID for this context.
     *
     * @var int|null $id
     */
    private $id = null;

    /**
     * Setting values (LTI parameters, custom parameters and local parameters).
     *
     * @var array|null $settings
     */
    private $settings = null;

    /**
     * Whether the settings value have changed since last saved.
     *
     * @var bool $settingsChanged
     */
    private $settingsChanged = false;

    /**
     * Data connector object or string.
     *
     * @var DataConnector|null $dataConnector
     */
    private $dataConnector = null;

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Initialise the context.
     */
    public function initialize()
    {
        $this->title = '';
        $this->settings = array();
        $this->groupSets = null;
        $this->groups = null;
        $this->created = null;
        $this->updated = null;
    }

    /**
     * Initialise the context.
     *
     * Synonym for initialize().
     */
    public function initialise()
    {
        $this->initialize();
    }

    /**
     * Save the context to the database.
     *
     * @return bool    True if the context was successfully saved.
     */
    public function save()
    {
        $ok = $this->getDataConnector()->saveContext($this);
        if ($ok) {
            $this->settingsChanged = false;
        }

        return $ok;
    }

    /**
     * Delete the context from the database.
     *
     * @return bool    True if the context was successfully deleted.
     */
    public function delete()
    {
        return $this->getDataConnector()->deleteContext($this);
    }

    /**
     * Get tool consumer.
     *
     * @deprecated Use getPlatform() instead
     * @see Context::getPlatform()
     *
     * @return ToolConsumer Tool consumer object for this context.
     */
    public function getConsumer()
    {
        Util::logDebug('Method ceLTIc\LTI\Context::getConsumer() has been deprecated; please use ceLTIc\LTI\Context::getPlatform() instead.',
            true);
        return $this->getPlatform();
    }

    /**
     * Set tool consumer ID.
     *
     * @deprecated Use setPlatformId() instead
     * @see Context::setPlatformId()
     *
     * @param int $consumerId  Tool Consumer ID for this context.
     */
    public function setConsumerId($consumerId)
    {
        Util::logDebug('Method ceLTIc\LTI\Context::setConsumerId() has been deprecated; please use ceLTIc\LTI\Context::setPlatformId() instead.',
            true);
        $this->setPlatformId($consumerId);
    }

    /**
     * Get platform.
     *
     * @return Platform  Platform object for this context.
     */
    public function getPlatform()
    {
        if (is_null($this->platform)) {
            $this->platform = Platform::fromRecordId($this->platformId, $this->getDataConnector());
        }

        return $this->platform;
    }

    /**
     * Set platform ID.
     *
     * @param int $platformId  Platform ID for this context.
     */
    public function setPlatformId($platformId)
    {
        $this->platform = null;
        $this->platformId = $platformId;
    }

    /**
     * Get consumer key.
     *
     * @return string  Consumer key value for this context.
     */
    public function getKey()
    {
        return $this->getPlatform()->getKey();
    }

    /**
     * Get context ID.
     *
     * @return string ID for this context.
     */
    public function getId()
    {
        return $this->ltiContextId;
    }

    /**
     * Get the context record ID.
     *
     * @return int|null Context record ID value
     */
    public function getRecordId()
    {
        return $this->id;
    }

    /**
     * Sets the context record ID.
     *
     * @param int $id  Context record ID value
     */
    public function setRecordId($id)
    {
        $this->id = $id;
    }

    /**
     * Get the data connector.
     *
     * @return mixed Data connector object or string
     */
    public function getDataConnector()
    {
        return $this->dataConnector;
    }

    /**
     * Get a setting value.
     *
     * @param string $name    Name of setting
     * @param string $default Value to return if the setting does not exist (optional, default is an empty string)
     *
     * @return string Setting value
     */
    public function getSetting($name, $default = '')
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
     * @param string $name  Name of setting
     * @param string $value Value to set, use an empty value to delete a setting (optional, default is null)
     */
    public function setSetting($name, $value = null)
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
     * @return array Associative array of setting values
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Set an array of all setting values.
     *
     * @param array $settings Associative array of setting values
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;
    }

    /**
     * Save setting values.
     *
     * @return bool    True if the settings were successfully saved
     */
    public function saveSettings()
    {
        if ($this->settingsChanged) {
            $ok = $this->save();
        } else {
            $ok = true;
        }

        return $ok;
    }

    /**
     * Check if the Tool Settings service is available.
     *
     * @return bool    True if this context supports the Tool Settings service
     */
    public function hasToolSettingsService()
    {
        $has = !empty($this->getSetting('custom_context_setting_url'));
        if (!$has) {
            $has = self::hasConfiguredApiHook(self::$TOOL_SETTINGS_SERVICE_HOOK, $this->getPlatform()->getFamilyCode(), $this);
        }
        return $has;
    }

    /**
     * Get Tool Settings.
     *
     * @param int      $mode       Mode for request (optional, default is current level only)
     * @param bool     $simple     True if all the simple media type is to be used (optional, default is true)
     *
     * @return mixed The array of settings if successful, otherwise false
     */
    public function getToolSettings($mode = Service\ToolSettings::MODE_CURRENT_LEVEL, $simple = true)
    {
        $ok = false;
        $settings = array();
        if (!empty($this->getSetting('custom_context_setting_url'))) {
            $url = $this->getSetting('custom_context_setting_url');
            $service = new Service\ToolSettings($this, $url, $simple);
            $settings = $service->get($mode);
            $this->lastServiceRequest = $service->getHttpMessage();
            $ok = $settings !== false;
        }
        if (!$ok && $this->hasConfiguredApiHook(self::$TOOL_SETTINGS_SERVICE_HOOK, $this->getPlatform()->getFamilyCode(), $this)) {
            $className = $this->getApiHook(self::$TOOL_SETTINGS_SERVICE_HOOK, $this->getPlatform()->getFamilyCode());
            $hook = new $className($this);
            $settings = $hook->getToolSettings($mode, $simple);
        }

        return $settings;
    }

    /**
     * Set Tool Settings.
     *
     * @param array    $settings   An associative array of settings (optional, default is none)
     *
     * @return bool    True if action was successful, otherwise false
     */
    public function setToolSettings($settings = array())
    {
        $ok = false;
        if (!empty($this->getSetting('custom_context_setting_url'))) {
            $url = $this->getSetting('custom_context_setting_url');
            $service = new Service\ToolSettings($this, $url);
            $ok = $service->set($settings);
            $this->lastServiceRequest = $service->getHttpMessage();
        }
        if (!$ok && $this->hasConfiguredApiHook(self::$TOOL_SETTINGS_SERVICE_HOOK, $this->getPlatform()->getFamilyCode(), $this)) {
            $className = $this->getApiHook(self::$TOOL_SETTINGS_SERVICE_HOOK, $this->getPlatform()->getFamilyCode());
            $hook = new $className($this);
            $ok = $hook->setToolSettings($settings);
        }

        return $ok;
    }

    /**
     * Check if a Course Group service is available.
     *
     * @return bool    True if this context supports a Course Group service
     */
    public function hasGroupService()
    {
        $has = !empty($this->getSetting('custom_context_groups_url'));
        if (!$has) {
            $has = self::hasConfiguredApiHook(self::$MEMBERSHIPS_SERVICE_HOOK, $this->getPlatform()->getFamilyCode(), $this);
        }
        return $has;
    }

    /**
     * Get course group sets and groups.
     *
     * @return bool  True if the request was successful
     */
    public function getGroups()
    {
        $groupsUrl = $this->getSetting('custom_context_groups_url');
        $groupsetsUrl = $this->getSetting('custom_context_group_sets_url');
        $service = new Service\Groups($this, $groupsUrl, $groupsetsUrl);
        $ok = $service->get();
        if (!empty($service->getHttpMessage())) {
            $this->lastServiceRequest = $service->getHttpMessage();
        }
        if (!$ok && $this->hasConfiguredApiHook(self::$GROUPS_SERVICE_HOOK, $this->getPlatform()->getFamilyCode(), $this)) {
            $className = $this->getApiHook(self::$GROUPS_SERVICE_HOOK, $this->getPlatform()->getFamilyCode());
            $hook = new $className($this);
            $ok = $hook->getGroups();
        }

        return $ok;
    }

    /**
     * Check if the Membership service is supported.
     *
     * @deprecated Use hasMembershipsService() instead
     * @see Context::hasMembershipsService()
     *
     * @return bool    True if this context supports the Membership service
     */
    public function hasMembershipService()
    {
        Util::logDebug('Method ceLTIc\LTI\Context::hasMembershipService() has been deprecated; please use ceLTIc\LTI\Context::hasMembershipsService() instead.',
            true);
        return $this->hasMembershipsService();
    }

    /**
     * Check if a Membership service is available.
     *
     * @return bool    True if this context supports a Memberships service
     */
    public function hasMembershipsService()
    {
        $has = !empty($this->getSetting('custom_context_memberships_url')) || !empty($this->getSetting('custom_context_memberships_v2_url'));
        if (!$has) {
            $has = self::hasConfiguredApiHook(self::$MEMBERSHIPS_SERVICE_HOOK, $this->getPlatform()->getFamilyCode(), $this);
        }
        return $has;
    }

    /**
     * Get Membership.
     *
     * @deprecated Use getMemberships() instead
     * @see Context::getMemberships()
     *
     * @return mixed The array of UserResult objects if successful, otherwise false
     */
    public function getMembership()
    {
        Util::logDebug('Method ceLTIc\LTI\Context::getMembership() has been deprecated; please use ceLTIc\LTI\Context::getMemberships() instead.',
            true);
        return $this->getMemberships();
    }

    /**
     * Get Memberships.
     *
     * @param bool    $withGroups True is group information is to be requested as well
     *
     * @return mixed The array of UserResult objects if successful, otherwise false
     */
    public function getMemberships($withGroups = false)
    {
        $ok = false;
        $userResults = array();
        $hasMembershipsService = !empty($this->getSetting('custom_context_memberships_url'));
        $hasNRPService = !empty($this->getSetting('custom_context_memberships_v2_url'));
        $hasGroupsService = !empty($this->getSetting('custom_context_groups_url')) ||
            $this->hasConfiguredApiHook(self::$GROUPS_SERVICE_HOOK, $this->getPlatform()->getFamilyCode(), $this);
        $hasApiHook = $this->hasConfiguredApiHook(self::$MEMBERSHIPS_SERVICE_HOOK, $this->getPlatform()->getFamilyCode(), $this);
        if (($hasMembershipsService || $hasNRPService) && (!$withGroups || ($hasNRPService && $hasGroupsService) || !$hasApiHook)) {
            if ($hasNRPService) {
                $url = $this->getSetting('custom_context_memberships_v2_url');
                $format = Service\Membership::MEDIA_TYPE_MEMBERSHIPS_NRPS;
            } else {
                $url = $this->getSetting('custom_context_memberships_url');
                $format = Service\Membership::MEDIA_TYPE_MEMBERSHIPS_V1;
            }
            $service = new Service\Membership($this, $url, $format);
            if (!$withGroups || !$hasNRPService) {
                $userResults = $service->get();
            } else {
                $userResults = $service->getWithGroups();
            }
            if (!empty($service->getHttpMessage())) {
                $this->lastServiceRequest = $service->getHttpMessage();
            }
            $ok = $userResults !== false;
        }
        if (!$ok && $hasApiHook) {
            $className = $this->getApiHook(self::$MEMBERSHIPS_SERVICE_HOOK, $this->getPlatform()->getFamilyCode());
            $hook = new $className($this);
            $userResults = $hook->getMemberships($withGroups);
        }

        return $userResults;
    }

    /**
     * Check if the Line Item service is available.
     *
     * @return bool    True if this context supports the Line Item service
     */
    public function hasLineItemService()
    {
        $has = false;
        if (!empty($this->getSetting('custom_ags_scopes'))) {
            $scopes = explode(',', $this->getSetting('custom_ags_scopes'));
            if (in_array(Service\LineItem::$SCOPE, $scopes) || in_array(Service\LineItem::$SCOPE_READONLY, $scopes)) {
                $has = !empty($this->getSetting('custom_lineitems_url'));
            }
        }

        return $has;
    }

    /**
     * Check if the Score service is available.
     *
     * @return bool    True if this context supports the Score service
     */
    public function hasScoreService()
    {
        $has = false;
        if (!empty($this->getSetting('custom_ags_scopes'))) {
            $scopes = explode(',', $this->getSetting('custom_ags_scopes'));
            if (in_array(Service\Score::$SCOPE, $scopes)) {
                $has = !empty($this->getSetting('custom_lineitems_url'));
            }
        }

        return $has;
    }

    /**
     * Check if the Result service is available.
     *
     * @return bool    True if this context supports the Result service
     */
    public function hasResultService()
    {
        $has = false;
        if (!empty($this->getSetting('custom_ags_scopes'))) {
            $scopes = explode(',', $this->getSetting('custom_ags_scopes'));
            if (in_array(Service\Result::$SCOPE, $scopes)) {
                $has = !empty($this->getSetting('custom_lineitems_url'));
            }
        }

        return $has;
    }

    /**
     * Get line items.
     *
     * @param string|null  $resourceId         Tool resource ID
     * @param string|null  $tag                Tag
     * @param int|null     $limit              Limit of line items to be returned in each request, null for service default
     *
     * @return LineItem[]|bool  Array of LineItem objects or false on error
     */
    public function getLineItems($resourceId = null, $tag = null, $limit = null)
    {
        $lineItems = false;
        $this->lastServiceRequest = null;
        $lineItemService = $this->getLineItemService();
        if (!empty($lineItemService)) {
            $lineItems = $lineItemService->getAll(null, $resourceId, $tag);
            $http = $lineItemService->getHttpMessage();
            $this->lastServiceRequest = $http;
        }

        return $lineItems;
    }

    /**
     * Create a new line item.
     *
     * @param LineItem  $lineItem         Line item object
     *
     * @return bool  True if successful
     */
    public function createLineItem($lineItem)
    {
        $ok = false;
        $lineItemService = $this->getLineItemService();
        if (!empty($lineItemService)) {
            $ok = $lineItemService->createLineItem($lineItem);
        }

        return $ok;
    }

    /**
     * Load the context from the database.
     *
     * @param int             $id               Record ID of context
     * @param DataConnector   $dataConnector    Database connection object
     *
     * @return Context    Context object
     */
    public static function fromRecordId($id, $dataConnector)
    {
        $context = new Context();
        $context->dataConnector = $dataConnector;
        $context->load($id);

        return $context;
    }

    /**
     * Class constructor from consumer.
     *
     * @deprecated Use fromPlatform() instead
     * @see Context::fromPlatform()
     *
     * @param ToolConsumer    $consumer       Consumer instance
     * @param string          $ltiContextId   LTI Context ID value
     *
     * @return Context
     */
    public static function fromConsumer($consumer, $ltiContextId)
    {
        return self::fromPlatform($consumer, $ltiContextId);
    }

    /**
     * Class constructor from platform.
     *
     * @param Platform        $platform       Platform instance
     * @param string          $ltiContextId   LTI Context ID value
     *
     * @return Context
     */
    public static function fromPlatform($platform, $ltiContextId)
    {
        $context = new Context();
        $context->platform = $platform;
        $context->dataConnector = $platform->getDataConnector();
        $context->ltiContextId = $ltiContextId;
        if (!empty($ltiContextId)) {
            $context->load();
        }

        return $context;
    }

###
###  PRIVATE METHODS
###

    /**
     * Load the context from the database.
     *
     * @param int $id     Record ID of context (optional, default is null)
     *
     * @return bool    True if context was successfully loaded
     */
    private function load($id = null)
    {
        $this->initialize();
        $this->id = $id;
        return $this->getDataConnector()->loadContext($this);
    }

    /**
     * Get the Line Item service object.
     *
     * @return Service\\LineItem    Line Item service, or false if not available
     */
    private function getLineItemService()
    {
        $url = $this->getSetting('custom_lineitems_url');
        if (!empty($url)) {
            $lineItemService = new Service\LineItem($this->getPlatform(), $url);
        } else {
            $lineItemService = false;
        }

        return $lineItemService;
    }

}
