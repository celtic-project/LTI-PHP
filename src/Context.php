<?php

namespace ceLTIc\LTI;

use ceLTIc\LTI\Service;
use ceLTIc\LTI\Http\HttpMessage;
use ceLTIc\LTI\ApiHook\ApiHook;

/**
 * Class to represent a tool consumer context
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
     * UserResult group sets (null if the consumer does not support the groups enhancement)
     *
     * @var array|null $groupSets
     */
    public $groupSets = null;

    /**
     * UserResult groups (null if the consumer does not support the groups enhancement)
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
     * Tool Consumer for this context.
     *
     * @var ToolConsumer|null $consumer
     */
    private $consumer = null;

    /**
     * Tool Consumer ID for this context.
     *
     * @var int|null $consumerId
     */
    private $consumerId = null;

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
     * @return ToolConsumer Tool consumer object for this context.
     */
    public function getConsumer()
    {
        if (is_null($this->consumer)) {
            $this->consumer = ToolConsumer::fromRecordId($this->consumerId, $this->getDataConnector());
        }

        return $this->consumer;
    }

    /**
     * Set tool consumer ID.
     *
     * @param int $consumerId  Tool Consumer ID for this resource link.
     */
    public function setConsumerId($consumerId)
    {
        $this->consumer = null;
        $this->consumerId = $consumerId;
    }

    /**
     * Get tool consumer key.
     *
     * @return string Consumer key value for this context.
     */
    public function getKey()
    {
        return $this->getConsumer()->getKey();
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
            $has = self::hasApiHook(self::$TOOL_SETTINGS_SERVICE_HOOK, $this->getConsumer()->getFamilyCode());
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
        if (!$ok && $this->hasApiHook(self::$TOOL_SETTINGS_SERVICE_HOOK, $this->getConsumer()->getFamilyCode())) {
            $className = $this->getApiHook(self::$TOOL_SETTINGS_SERVICE_HOOK, $this->getConsumer()->getFamilyCode());
            $hook = new $className($this);
            $settings = $hook->getToolSettings($mode, $simple);
        }

        return $settings;
    }

    /**
     * Perform a Tool Settings service request.
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
        if (!$ok && $this->hasApiHook(self::$TOOL_SETTINGS_SERVICE_HOOK, $this->getConsumer()->getFamilyCode())) {
            $className = $this->getApiHook(self::$TOOL_SETTINGS_SERVICE_HOOK, $this->getConsumer()->getFamilyCode());
            $hook = new $className($this);
            $ok = $hook->setToolSettings($settings);
        }

        return $ok;
    }

    /**
     * Check if the Membership service is supported.
     *
     * @deprecated Use hasMembershipsService() instead
     * @see Context::hasMembershipsService()
     *
     * @return bool    True if this resource link supports the Membership service
     */
    public function hasMembershipService()
    {
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
            $has = self::hasApiHook(self::$MEMBERSHIPS_SERVICE_HOOK, $this->getConsumer()->getFamilyCode());
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
        $hasLtiService = !empty($this->getSetting('custom_context_memberships_url')) || !empty($this->getSetting('custom_context_memberships_v2_url'));
        $hasApiHook = $this->hasApiHook(self::$MEMBERSHIPS_SERVICE_HOOK, $this->getConsumer()->getFamilyCode());
        if ($hasLtiService && (!$withGroups || !$hasApiHook)) {
            if (!empty($this->getSetting('custom_context_memberships_v2_url'))) {
                $url = $this->getSetting('custom_context_memberships_v2_url');
                $format = Service\Membership::MEMBERSHIPS_MEDIA_TYPE_NRPS;
            } else {
                $url = $this->getSetting('custom_context_memberships_url');
                $format = Service\Membership::MEMBERSHIPS_MEDIA_TYPE_V1;
            }
            $service = new Service\Membership($this, $url, $format);
            $userResults = $service->get();
            $this->lastServiceRequest = $service->getHttpMessage();
            $ok = $userResults !== false;
        }
        if (!$ok && $hasApiHook) {
            $className = $this->getApiHook(self::$MEMBERSHIPS_SERVICE_HOOK, $this->getConsumer()->getFamilyCode());
            $hook = new $className($this);
            $userResults = $hook->getMemberships($withGroups);
        }

        return $userResults;
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
     * @param ToolConsumer    $consumer       Consumer instance
     * @param string          $ltiContextId   LTI Context ID value
     *
     * @return Context
     */
    public static function fromConsumer($consumer, $ltiContextId)
    {
        $context = new Context();
        $context->consumer = $consumer;
        $context->dataConnector = $consumer->getDataConnector();
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

}
