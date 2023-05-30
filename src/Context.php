<?php
declare(strict_types=1);

namespace ceLTIc\LTI;

use ceLTIc\LTI\Service;
use ceLTIc\LTI\Http\HttpMessage;
use ceLTIc\LTI\ApiHook\ApiHook;
use ceLTIc\LTI\DataConnector\DataConnector;
use ceLTIc\LTI\Enum\ToolSettingsMode;

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
    public ?string $ltiContextId = null;

    /**
     * Context title.
     *
     * @var string|null $title
     */
    public ?string $title = null;

    /**
     * Context type.
     *
     * @var string|null $type
     */
    public ?string $type = null;

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
    public ?array $groupSets = null;

    /**
     * User groups (null if the platform does not support the groups enhancement)
     *
     * A group is represented by an associative array with the following elements:
     *   - title
     *   - set (ID of group set, array of IDs if the group belongs to more than one set, omitted if the group is not part of a set)
     * The array key value is the group ID.
     *
     * @var array|null $groups
     */
    public ?array $groups = null;

    /**
     * HttpMessage object for last service request.
     *
     * @var HttpMessage|null $lastServiceRequest
     */
    public ?HttpMessage $lastServiceRequest = null;

    /**
     * Timestamp for when the object was created.
     *
     * @var int|null $created
     */
    public ?int $created = null;

    /**
     * Timestamp for when the object was last updated.
     *
     * @var int|null $updated
     */
    public ?int $updated = null;

    /**
     * Platform for this context.
     *
     * @var Platform|null $platform
     */
    private ?Platform $platform = null;

    /**
     * Platform ID for this context.
     *
     * @var int|null $platformId
     */
    private ?int $platformId = null;

    /**
     * ID for this context.
     *
     * @var int|null $id
     */
    private ?int $id = null;

    /**
     * Setting values (LTI parameters, custom parameters and local parameters).
     *
     * @var array|null $settings
     */
    private ?array $settings = null;

    /**
     * Whether the settings value have changed since last saved.
     *
     * @var bool $settingsChanged
     */
    private bool $settingsChanged = false;

    /**
     * Data connector object or string.
     *
     * @var DataConnector|null $dataConnector
     */
    private ?DataConnector $dataConnector = null;

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Initialise the context.
     *
     * @return void
     */
    public function initialize(): void
    {
        $this->title = '';
        $this->settings = [];
        $this->groupSets = null;
        $this->groups = null;
        $this->created = null;
        $this->updated = null;
    }

    /**
     * Initialise the context.
     *
     * Synonym for initialize().
     *
     * @return void
     */
    public function initialise(): void
    {
        $this->initialize();
    }

    /**
     * Save the context to the database.
     *
     * @return bool  True if the context was successfully saved.
     */
    public function save(): bool
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
     * @return bool  True if the context was successfully deleted.
     */
    public function delete(): bool
    {
        return $this->getDataConnector()->deleteContext($this);
    }

    /**
     * Get platform.
     *
     * @return Platform  Platform object for this context.
     */
    public function getPlatform(): Platform
    {
        if (is_null($this->platform)) {
            $this->platform = Platform::fromRecordId($this->platformId, $this->getDataConnector());
        }

        return $this->platform;
    }

    /**
     * Set platform ID.
     *
     * @param int|null $platformId  Platform ID for this context.
     *
     * @return void
     */
    public function setPlatformId(?int $platformId): void
    {
        $this->platform = null;
        $this->platformId = $platformId;
    }

    /**
     * Get consumer key.
     *
     * @return string  Consumer key value for this context.
     */
    public function getKey(): string
    {
        return $this->getPlatform()->getKey();
    }

    /**
     * Get context ID.
     *
     * @return string|null  ID for this context.
     */
    public function getId(): ?string
    {
        return $this->ltiContextId;
    }

    /**
     * Get the context record ID.
     *
     * @return int|string|null  Context record ID value
     */
    public function getRecordId(): int|string|null
    {
        return $this->id;
    }

    /**
     * Sets the context record ID.
     *
     * @param int|string|null $id  Context record ID value
     *
     * @return void
     */
    public function setRecordId(int|string|null $id): void
    {
        $this->id = $id;
    }

    /**
     * Get the data connector.
     *
     * @return DataConnector|string  Data connector object or string
     */
    public function getDataConnector(): DataConnector|string
    {
        return $this->dataConnector;
    }

    /**
     * Get a setting value.
     *
     * @param string $name     Name of setting
     * @param string $default  Value to return if the setting does not exist (optional, default is an empty string)
     *
     * @return string  Setting value
     */
    public function getSetting(string $name, string $default = ''): string
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
     * @param string $name       Name of setting
     * @param array|null $value  Value to set, use an empty value to delete a setting (optional, default is null)
     *
     * @return void
     */
    public function setSetting(string $name, string|array|null $value = null): void
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
     * @return array  Associative array of setting values
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Set an array of all setting values.
     *
     * @param array $settings  Associative array of setting values
     *
     * @return void
     */
    public function setSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    /**
     * Save setting values.
     *
     * @return bool  True if the settings were successfully saved
     */
    public function saveSettings(): bool
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
     * @return bool  True if this context supports the Tool Settings service
     */
    public function hasToolSettingsService(): bool
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
     * @param ToolSettingsMode|null $mode  Mode for request (optional, default is current level only)
     * @param bool $simple                 True if all the simple media type is to be used (optional, default is true)
     *
     * @return array|bool  The array of settings if successful, otherwise false
     */
    public function getToolSettings(?ToolSettingsMode $mode = null, bool $simple = true): array|bool
    {
        $ok = false;
        $settings = [];
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
     * @param array $settings  An associative array of settings (optional, default is none)
     *
     * @return bool  True if action was successful, otherwise false
     */
    public function setToolSettings(array $settings = []): bool
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
     * @return bool  True if this context supports a Course Group service
     */
    public function hasGroupService(): bool
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
    public function getGroups(): bool
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
     * Check if a Membership service is available.
     *
     * @return bool  True if this context supports a Memberships service
     */
    public function hasMembershipsService(): bool
    {
        $has = !empty($this->getSetting('custom_context_memberships_url')) || !empty($this->getSetting('custom_context_memberships_v2_url'));
        if (!$has) {
            $has = self::hasConfiguredApiHook(self::$MEMBERSHIPS_SERVICE_HOOK, $this->getPlatform()->getFamilyCode(), $this);
        }
        return $has;
    }

    /**
     * Get Memberships.
     *
     * @param bool $withGroups  True is group information is to be requested as well
     *
     * @return array|bool  The array of UserResult objects if successful, otherwise false
     */
    public function getMemberships(bool $withGroups = false): array|bool
    {
        $ok = false;
        $userResults = [];
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
     * Check if the Line-item service is available.
     *
     * @return bool  True if this context supports the Line-item service
     */
    public function hasLineItemService(): bool
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
     * @return bool  True if this context supports the Score service
     */
    public function hasScoreService(): bool
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
     * @return bool  True if this context supports the Result service
     */
    public function hasResultService(): bool
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
     * Get line-items.
     *
     * @param string|null $resourceId  Tool resource ID
     * @param string|null $tag         Tag
     * @param int|null $limit          Limit of line-items to be returned in each request, null for service default
     *
     * @return LineItem[]|bool  Array of LineItem objects or false on error
     */
    public function getLineItems(?string $resourceId = null, ?string $tag = null, ?int $limit = null): array|bool
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
     * Create a new line-item.
     *
     * @param LineItem $lineItem  Line-item object
     *
     * @return bool  True if successful
     */
    public function createLineItem(LineItem $lineItem): bool
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
     * @param int $id                       Record ID of context
     * @param DataConnector $dataConnector  Database connection object
     *
     * @return Context  Context object
     */
    public static function fromRecordId(int $id, DataConnector $dataConnector): Context
    {
        $context = new Context();
        $context->dataConnector = $dataConnector;
        $context->load($id);

        return $context;
    }

    /**
     * Class constructor from platform.
     *
     * @param Platform $platform    Platform instance
     * @param string $ltiContextId  LTI Context ID value
     *
     * @return Context
     */
    public static function fromPlatform(Platform $platform, string $ltiContextId): Context
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
     * @param int $id  Record ID of context (optional, default is null)
     *
     * @return bool  True if context was successfully loaded
     */
    private function load(int $id = null): bool
    {
        $this->initialize();
        $this->id = $id;
        return $this->getDataConnector()->loadContext($this);
    }

    /**
     * Get the Line-item service object.
     *
     * @return Service\\LineItem|bool  Line-item service, or false if not available
     */
    private function getLineItemService(): Service\LineItem|bool
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
