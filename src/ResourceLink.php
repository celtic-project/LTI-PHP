<?php

namespace ceLTIc\LTI;

use ceLTIc\LTI\DataConnector\DataConnector;
use ceLTIc\LTI\Service;
use ceLTIc\LTI\Http\HttpMessage;
use ceLTIc\LTI\ApiHook\ApiHook;
use DOMDocument;
use DOMElement;

/**
 * Class to represent a tool consumer resource link
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ResourceLink
{
    use ApiHook;

    /**
     * Read action.
     */
    const EXT_READ = 1;

    /**
     * Write (create/update) action.
     */
    const EXT_WRITE = 2;

    /**
     * Delete action.
     */
    const EXT_DELETE = 3;

    /**
     * Create action.
     */
    const EXT_CREATE = 4;

    /**
     * Update action.
     */
    const EXT_UPDATE = 5;

    /**
     * Decimal outcome type.
     */
    const EXT_TYPE_DECIMAL = 'decimal';

    /**
     * Percentage outcome type.
     */
    const EXT_TYPE_PERCENTAGE = 'percentage';

    /**
     * Ratio outcome type.
     */
    const EXT_TYPE_RATIO = 'ratio';

    /**
     * Letter (A-F) outcome type.
     */
    const EXT_TYPE_LETTER_AF = 'letteraf';

    /**
     * Letter (A-F) with optional +/- outcome type.
     */
    const EXT_TYPE_LETTER_AF_PLUS = 'letterafplus';

    /**
     * Pass/fail outcome type.
     */
    const EXT_TYPE_PASS_FAIL = 'passfail';

    /**
     * Free text outcome type.
     */
    const EXT_TYPE_TEXT = 'freetext';

    /**
     * Context title.
     *
     * @var string|null $title
     */
    public $title = null;

    /**
     * Resource link ID as supplied in the last connection request.
     *
     * @var string|null $ltiResourceLinkId
     */
    public $ltiResourceLinkId = null;

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
     * Request for last service request.
     *
     * @var string|null $extRequest
     */
    public $extRequest = null;

    /**
     * Request headers for last service request.
     *
     * @var array|null $extRequestHeaders
     */
    public $extRequestHeaders = null;

    /**
     * Response from last service request.
     *
     * @var string|null $extResponse
     */
    public $extResponse = null;

    /**
     * Response header from last service request.
     *
     * @var array|null $extResponseHeaders
     */
    public $extResponseHeaders = null;

    /**
     * Consumer key value for resource link being shared (if any).
     *
     * @var string|null $primaryResourceLinkId
     */
    public $primaryResourceLinkId = null;

    /**
     * Whether the sharing request has been approved by the primary resource link.
     *
     * @var bool|null $shareApproved
     */
    public $shareApproved = null;

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
     * Record ID for this resource link.
     *
     * @var int|null $id
     */
    private $id = null;

    /**
     * Tool Consumer for this resource link.
     *
     * @var ToolConsumer|null $consumer
     */
    private $consumer = null;

    /**
     * Tool Consumer ID for this resource link.
     *
     * @var int|null $consumerId
     */
    private $consumerId = null;

    /**
     * Context for this resource link.
     *
     * @var Context|null $context
     */
    private $context = null;

    /**
     * Context ID for this resource link.
     *
     * @var int|null $contextId
     */
    private $contextId = null;

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
     * XML document for the last extension service request.
     *
     * @var string|null $extDoc
     */
    private $extDoc = null;

    /**
     * XML node array for the last extension service request.
     *
     * @var object|null $extNodes
     */
    private $extNodes = null;

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
     * Initialise the resource link.
     */
    public function initialize()
    {
        $this->title = '';
        $this->settings = array();
        $this->groupSets = null;
        $this->groups = null;
        $this->primaryResourceLinkId = null;
        $this->shareApproved = null;
        $this->created = null;
        $this->updated = null;
    }

    /**
     * Initialise the resource link.
     *
     * Synonym for initialize().
     */
    public function initialise()
    {
        $this->initialize();
    }

    /**
     * Save the resource link to the database.
     *
     * @return bool    True if the resource link was successfully saved.
     */
    public function save()
    {
        $ok = $this->getDataConnector()->saveResourceLink($this);
        if ($ok) {
            $this->settingsChanged = false;
        }

        return $ok;
    }

    /**
     * Delete the resource link from the database.
     *
     * @return bool    True if the resource link was successfully deleted.
     */
    public function delete()
    {
        return $this->getDataConnector()->deleteResourceLink($this);
    }

    /**
     * Get tool consumer.
     *
     * @return ToolConsumer Tool consumer object for this resource link.
     */
    public function getConsumer()
    {
        if (is_null($this->consumer)) {
            if (!is_null($this->context) || !is_null($this->contextId)) {
                $this->consumer = $this->getContext()->getConsumer();
            } else {
                $this->consumer = ToolConsumer::fromRecordId($this->consumerId, $this->getDataConnector());
            }
        }

        return $this->consumer;
    }

    /**
     * Get tool consumer ID.
     *
     * @return int|null Tool Consumer ID for this resource link.
     */
    public function getConsumerId()
    {
        return $this->consumerId;
    }

    /**
     * Set tool consumer ID.
     *
     * @param int $consumerId   Tool Consumer ID for this resource link.
     */
    public function setConsumerId($consumerId)
    {
        $this->consumer = null;
        $this->consumerId = $consumerId;
    }

    /**
     * Get context.
     *
     * @return Context|null LTIContext object for this resource link.
     */
    public function getContext()
    {
        if (is_null($this->context) && !is_null($this->contextId)) {
            $this->context = Context::fromRecordId($this->contextId, $this->getDataConnector());
        }

        return $this->context;
    }

    /**
     * Get context record ID.
     *
     * @return int|null Context record ID for this resource link.
     */
    public function getContextId()
    {
        return $this->contextId;
    }

    /**
     * Set context ID.
     *
     * @param int $contextId   Context ID for this resource link.
     */
    public function setContextId($contextId)
    {
        $this->context = null;
        $this->contextId = $contextId;
    }

    /**
     * Get tool consumer key.
     *
     * @return string Consumer key value for this resource link.
     */
    public function getKey()
    {
        return $this->getConsumer()->getKey();
    }

    /**
     * Get resource link ID.
     *
     * @return string ID for this resource link.
     */
    public function getId()
    {
        return $this->ltiResourceLinkId;
    }

    /**
     * Get resource link record ID.
     *
     * @return int|null Record ID for this resource link.
     */
    public function getRecordId()
    {
        return $this->id;
    }

    /**
     * Set resource link record ID.
     *
     * @param int $id  Record ID for this resource link.
     */
    public function setRecordId($id)
    {
        $this->id = $id;
    }

    /**
     * Get the data connector.
     *
     * @return DataConnector|null Data connector object or string
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
     * @param array $settings  Associative array of setting values
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
     * Check if an Outcomes service is available.
     *
     * @return bool    True if this resource link supports an Outcomes service
     */
    public function hasOutcomesService()
    {
        $has = !empty($this->getSetting('ext_ims_lis_basic_outcome_url')) || !empty($this->getSetting('lis_outcome_service_url')) ||
            !empty($this->getSetting('custom_lineitem_url'));
        if (!$has) {
            $has = self::hasApiHook(self::$OUTCOMES_SERVICE_HOOK, $this->getConsumer()->getFamilyCode());
        }
        return $has;
    }

    /**
     * Check if a Memberships service is available.
     *
     * @return bool    True if this resource link supports a Memberships service
     */
    public function hasMembershipsService()
    {
        $has = false;
        if (!empty($this->contextId)) {
            $has = !empty($this->getContext()->getSetting('custom_context_memberships_url')) || !empty($this->getContext()->getSetting('custom_context_memberships_v2_url'));
        }
        if (!$has) {
            $has = !empty($this->getSetting('ext_ims_lis_memberships_url'));
        }
        if (!$has) {
            $has = self::hasApiHook(self::$MEMBERSHIPS_SERVICE_HOOK, $this->getConsumer()->getFamilyCode());
        }
        return $has;
    }

    /**
     * Check if the Setting extension service is available.
     *
     * @return bool    True if this resource link supports the Setting extension service
     */
    public function hasSettingService()
    {
        $url = $this->getSetting('ext_ims_lti_tool_setting_url');

        return !empty($url);
    }

    /**
     * Perform an Outcomes service request.
     *
     * @param int $action The action type constant
     * @param Outcome $ltiOutcome Outcome object
     * @param UserResult $userresult UserResult object
     *
     * @return string|bool    Outcome value read or true if the request was successfully processed
     */
    public function doOutcomesService($action, $ltiOutcome, $userresult)
    {
        $response = false;
        $this->extResponse = '';
// Lookup service details from the source resource link appropriate to the user (in case the destination is being shared)
        $sourceResourceLink = $userresult->getResourceLink();
        $sourcedId = $userresult->ltiResultSourcedId;

// Use LTI 1.1 service in preference to extension service if it is available
        $urlAGS = $sourceResourceLink->getSetting('custom_lineitem_url');
        $urlLTI11 = $sourceResourceLink->getSetting('lis_outcome_service_url');
        $urlExt = $sourceResourceLink->getSetting('ext_ims_lis_basic_outcome_url');
        if ($urlExt || $urlLTI11 || $urlAGS) {
            switch ($action) {
                case self::EXT_READ:
                    if ($urlAGS) {
                        $do = $action;
                    } elseif ($urlLTI11 && ($ltiOutcome->type === self::EXT_TYPE_DECIMAL)) {
                        $do = 'readResult';
                    } elseif ($urlExt) {
                        $urlLTI11 = null;
                        $do = 'basic-lis-readresult';
                    }
                    break;
                case self::EXT_WRITE:
                    if ($urlAGS) {
                        $do = $action;
                    } elseif ($urlLTI11 && $this->checkValueType($ltiOutcome, array(self::EXT_TYPE_DECIMAL))) {
                        $do = 'replaceResult';
                    } elseif ($this->checkValueType($ltiOutcome)) {
                        $urlLTI11 = null;
                        $do = 'basic-lis-updateresult';
                    }
                    break;
                case self::EXT_DELETE:
                    if ($urlAGS) {
                        $do = $action;
                    } elseif ($urlLTI11 && ($ltiOutcome->type === self::EXT_TYPE_DECIMAL)) {
                        $do = 'deleteResult';
                    } elseif ($urlExt) {
                        $urlLTI11 = null;
                        $do = 'basic-lis-deleteresult';
                    }
                    break;
            }
        }
        if (isset($do)) {
            $value = $ltiOutcome->getValue();
            if (is_null($value)) {
                $value = '';
            }
            if ($urlAGS) {
                switch ($action) {
                    case self::EXT_READ:
                        $ltiOutcome = $this->doResultService($userresult, $urlAGS);
                        $response = !empty($ltiOutcome);
                        break;
                    case self::EXT_DELETE:
                        $ltiOutcome->setValue(null);
                        $ltiOutcome->activityProgress = 'Initialized';
                        $ltiOutcome->gradingProgress = 'NotReady';
                    case self::EXT_WRITE:
                        $response = $this->doScoreService($ltiOutcome, $userresult, $urlAGS);
                        break;
                }
            } elseif ($urlLTI11) {
                $xml = '';
                if ($action === self::EXT_WRITE) {
                    $xml = <<< EOF

        <result>
          <resultScore>
            <language>{$ltiOutcome->language}</language>
            <textString>{$value}</textString>
          </resultScore>
        </result>
EOF;
                }
                $sourcedId = htmlentities($sourcedId);
                $xml = <<<EOF
      <resultRecord>
        <sourcedGUID>
          <sourcedId>{$sourcedId}</sourcedId>
        </sourcedGUID>{$xml}
      </resultRecord>
EOF;
                if ($this->doLTI11Service($do, $urlLTI11, $xml)) {
                    switch ($action) {
                        case self::EXT_READ:
                            if (!isset($this->extNodes['imsx_POXBody']["{$do}Response"]['result']['resultScore']['textString'])) {
                                break;
                            } else {
                                $ltiOutcome->setValue($this->extNodes['imsx_POXBody']["{$do}Response"]['result']['resultScore']['textString']);
                            }
                        case self::EXT_WRITE:
                        case self::EXT_DELETE:
                            $response = true;
                            break;
                    }
                }
            } else {
                $params = array();
                $params['sourcedid'] = $sourcedId;
                $params['result_resultscore_textstring'] = $value;
                if (!empty($ltiOutcome->language)) {
                    $params['result_resultscore_language'] = $ltiOutcome->language;
                }
                if (!empty($ltiOutcome->status)) {
                    $params['result_statusofresult'] = $ltiOutcome->status;
                }
                if (!empty($ltiOutcome->date)) {
                    $params['result_date'] = $ltiOutcome->date;
                }
                if (!empty($ltiOutcome->type)) {
                    $params['result_resultvaluesourcedid'] = $ltiOutcome->type;
                }
                if (!empty($ltiOutcome->data_source)) {
                    $params['result_datasource'] = $ltiOutcome->data_source;
                }
                if ($this->doService($do, $urlExt, $params)) {
                    switch ($action) {
                        case self::EXT_READ:
                            if (isset($this->extNodes['result']['resultscore']['textstring'])) {
                                $response = $this->extNodes['result']['resultscore']['textstring'];
                            }
                            break;
                        case self::EXT_WRITE:
                        case self::EXT_DELETE:
                            $response = true;
                            break;
                    }
                }
            }
            if (is_array($response) && (count($response) <= 0)) {
                $response = '';
            }
        }
        if (($response === false) && $this->hasApiHook(self::$OUTCOMES_SERVICE_HOOK, $this->getConsumer()->getFamilyCode())) {
            $className = $this->getApiHook(self::$OUTCOMES_SERVICE_HOOK, $this->getConsumer()->getFamilyCode());
            $hook = new $className($this);
            $response = $hook->doOutcomesService($action, $ltiOutcome, $userresult);
        }

        return $response;
    }

    /**
     * Perform a Memberships extension service request.
     *
     * The userResult table is updated with any user objects with lis_result_sourcedid values.
     *
     * @deprecated Use getMemberships() instead
     * @see ResourceLink::getMemberships()
     *
     * @param bool    $withGroups True is group information is to be requested as well
     *
     * @return mixed Array of UserResult objects or False if the request was not successful
     */
    public function doMembershipsService($withGroups = false)
    {
        return $this->getMemberships($withGroups);
    }

    /**
     * Perform a Setting service request.
     *
     * @param int    $action The action type constant
     * @param string $value  The setting value (optional, default is null)
     *
     * @return mixed The setting value for a read action, true if a write or delete action was successful, otherwise false
     */
    public function doSettingService($action, $value = null)
    {
        $response = false;
        $this->extResponse = '';
        switch ($action) {
            case self::EXT_READ:
                $do = 'basic-lti-loadsetting';
                break;
            case self::EXT_WRITE:
                $do = 'basic-lti-savesetting';
                break;
            case self::EXT_DELETE:
                $do = 'basic-lti-deletesetting';
                break;
        }
        if (isset($do)) {

            $url = $this->getSetting('ext_ims_lti_tool_setting_url');
            $params = array();
            $params['id'] = $this->getSetting('ext_ims_lti_tool_setting_id');
            if (is_null($value)) {
                $value = '';
            }
            $params['setting'] = $value;

            if ($this->doService($do, $url, $params)) {
                switch ($action) {
                    case self::EXT_READ:
                        if (isset($this->extNodes['setting']['value'])) {
                            $response = $this->extNodes['setting']['value'];
                            if (is_array($response)) {
                                $response = '';
                            }
                        }
                        break;
                    case self::EXT_WRITE:
                        $this->setSetting('ext_ims_lti_tool_setting', $value);
                        $this->saveSettings();
                        $response = true;
                        break;
                    case self::EXT_DELETE:
                        $response = true;
                        break;
                }
            }
        }

        return $response;
    }

    /**
     * Check if the Tool Settings service is available.
     *
     * @return bool    True if this resource link supports the Tool Settings service
     */
    public function hasToolSettingsService()
    {
        $has = !empty($this->getSetting('custom_link_setting_url'));
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
        if (!empty($this->getSetting('custom_link_setting_url'))) {
            $url = $this->getSetting('custom_link_setting_url');
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
        if (!empty($this->getSetting('custom_link_setting_url'))) {
            $url = $this->getSetting('custom_link_setting_url');
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
     * @see ResourceLink::hasMembershipsService()
     *
     * @return bool    True if this resource link supports the Membership service
     */
    public function hasMembershipService()
    {
        return $this->hasMembershipsService();
    }

    /**
     * Get Membership.
     *
     * @deprecated Use getMemberships() instead
     * @see ResourceLink::getMemberships()
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
        $hasLtiservice = !empty($this->contextId) &&
            (!empty($this->getContext()->getSetting('custom_context_memberships_url')) || !empty($this->getContext()->getSetting('custom_context_memberships_v2_url')));
        $hasExtService = !empty($this->getSetting('ext_ims_lis_memberships_url'));
        $hasApiHook = $this->hasApiHook(self::$MEMBERSHIPS_SERVICE_HOOK, $this->getConsumer()->getFamilyCode());
        if ($hasLtiservice && (!$withGroups || (!$hasExtService && !$hasApiHook))) {
            if (!empty($this->getContext()->getSetting('custom_context_memberships_v2_url'))) {
                $url = $this->getContext()->getSetting('custom_context_memberships_v2_url');
                $format = Service\Membership::MEMBERSHIPS_MEDIA_TYPE_NRPS;
            } else {
                $url = $this->getContext()->getSetting('custom_context_memberships_url');
                $format = Service\Membership::MEMBERSHIPS_MEDIA_TYPE_V1;
            }
            $service = new Service\Membership($this, $url, $format);
            $userResults = $service->get();
            $this->lastServiceRequest = $service->getHttpMessage();
            $ok = $userResults !== false;
        }
        if (!$ok && $hasExtService) {
            $this->extResponse = '';
            $url = $this->getSetting('ext_ims_lis_memberships_url');
            $params = array();
            $params['id'] = $this->getSetting('ext_ims_lis_memberships_id');
            if ($withGroups) {
                $ok = $this->doService('basic-lis-readmembershipsforcontextwithgroups', $url, $params);
            }
            if (!$ok) {
                $ok = $this->doService('basic-lis-readmembershipsforcontext', $url, $params);
            }
            if ($ok) {
                $this->groupSets = array();
                $this->groups = array();
                if (!isset($this->extNodes['memberships']['member'])) {
                    $members = array();
                } elseif (!isset($this->extNodes['memberships']['member'][0])) {
                    $members = array();
                    $members[0] = $this->extNodes['memberships']['member'];
                } else {
                    $members = $this->extNodes['memberships']['member'];
                }

                for ($i = 0; $i < count($members); $i++) {

                    $userresult = UserResult::fromResourceLink($this, $members[$i]['user_id']);

// Set the user name
                    $firstname = (isset($members[$i]['person_name_given'])) ? $members[$i]['person_name_given'] : '';
                    $lastname = (isset($members[$i]['person_name_family'])) ? $members[$i]['person_name_family'] : '';
                    $fullname = (isset($members[$i]['person_name_full'])) ? $members[$i]['person_name_full'] : '';
                    $userresult->setNames($firstname, $lastname, $fullname);

// Set the user email
                    $email = (isset($members[$i]['person_contact_email_primary'])) ? $members[$i]['person_contact_email_primary'] : '';
                    $userresult->setEmail($email, $this->getConsumer()->defaultEmail);

// Set the user roles
                    if (isset($members[$i]['roles'])) {
                        $userresult->roles = ToolProvider::parseRoles($members[$i]['roles']);
                    }

// Set the user groups
                    if (!isset($members[$i]['groups']['group'])) {
                        $groups = array();
                    } elseif (!isset($members[$i]['groups']['group'][0])) {
                        $groups = array();
                        $groups[0] = $members[$i]['groups']['group'];
                    } else {
                        $groups = $members[$i]['groups']['group'];
                    }
                    for ($j = 0; $j < count($groups); $j++) {
                        $group = $groups[$j];
                        if (isset($group['set'])) {
                            $set_id = $group['set']['id'];
                            if (!isset($this->groupSets[$set_id])) {
                                $this->groupSets[$set_id] = array('title' => $group['set']['title'], 'groups' => array(),
                                    'num_members' => 0, 'num_staff' => 0, 'num_learners' => 0);
                            }
                            $this->groupSets[$set_id]['num_members']++;
                            if ($userresult->isStaff()) {
                                $this->groupSets[$set_id]['num_staff']++;
                            }
                            if ($userresult->isLearner()) {
                                $this->groupSets[$set_id]['num_learners']++;
                            }
                            if (!in_array($group['id'], $this->groupSets[$set_id]['groups'])) {
                                $this->groupSets[$set_id]['groups'][] = $group['id'];
                            }
                            $this->groups[$group['id']] = array('title' => $group['title'], 'set' => $set_id);
                        } else {
                            $this->groups[$group['id']] = array('title' => $group['title']);
                        }
                        $userresult->groups[] = $group['id'];
                    }
                    if (isset($members[$i]['lis_result_sourcedid'])) {
                        $userresult->ltiResultSourcedId = $members[$i]['lis_result_sourcedid'];
                    }
                    $userResults[] = $userresult;
                }
            } else {
                $userResults = false;
            }
            $ok = $userResults !== false;
        }
        if (!$ok && $hasApiHook) {
            $className = $this->getApiHook(self::$MEMBERSHIPS_SERVICE_HOOK, $this->getConsumer()->getFamilyCode());
            $hook = new $className($this);
            $userResults = $hook->getMemberships($withGroups);
            $ok = $userResults !== false;
        }
        if ($ok) {
            $oldUsers = $this->getUserResultSourcedIDs(true, ToolProvider::ID_SCOPE_RESOURCE);
            foreach ($userResults as $userresult) {
// If a result sourcedid is provided save the user
                if (!empty($userresult->ltiResultSourcedId)) {
                    $userresult->save();
                }
// Remove old user (if it exists)
                unset($oldUsers[$userresult->getId(ToolProvider::ID_SCOPE_RESOURCE)]);
            }
// Delete any old users which were not in the latest list from the tool consumer
            foreach ($oldUsers as $id => $userresult) {
                $userresult->delete();
            }
        }

        return $userResults;
    }

    /**
     * Obtain an array of UserResult objects for users with a result sourcedId.
     *
     * The array may include users from other resource links which are sharing this resource link.
     * It may also be optionally indexed by the user ID of a specified scope.
     *
     * @param bool    $localOnly True if only users from this resource link are to be returned, not users from shared resource links (optional, default is false)
     * @param int     $idScope     Scope to use for ID values (optional, default is null for consumer default)
     *
     * @return UserResult[] Array of UserResult objects
     */
    public function getUserResultSourcedIDs($localOnly = false, $idScope = null)
    {
        return $this->getDataConnector()->getUserResultSourcedIDsResourceLink($this, $localOnly, $idScope);
    }

    /**
     * Get an array of ResourceLinkShare objects for each resource link which is sharing this context.
     *
     * @return ResourceLinkShare[] Array of ResourceLinkShare objects
     */
    public function getShares()
    {
        return $this->getDataConnector()->getSharesResourceLink($this);
    }

    /**
     * Class constructor from consumer.
     *
     * @param ToolConsumer $consumer            Consumer object
     * @param string $ltiResourceLinkId         Resource link ID value
     * @param string $tempId                    Temporary Resource link ID value (optional, default is null)
     *
     * @return ResourceLink
     */
    public static function fromConsumer($consumer, $ltiResourceLinkId, $tempId = null)
    {
        $resourceLink = new ResourceLink();
        $resourceLink->consumer = $consumer;
        $resourceLink->dataConnector = $consumer->getDataConnector();
        $resourceLink->ltiResourceLinkId = $ltiResourceLinkId;
        if (!empty($ltiResourceLinkId)) {
            $resourceLink->load();
            if (is_null($resourceLink->id) && !empty($tempId)) {
                $resourceLink->ltiResourceLinkId = $tempId;
                $resourceLink->load();
                $resourceLink->ltiResourceLinkId = $ltiResourceLinkId;
            }
        }

        return $resourceLink;
    }

    /**
     * Class constructor from context.
     *
     * @param Context   $context                   Context object
     * @param string    $ltiResourceLinkId         Resource link ID value
     * @param string    $tempId                    Temporary Resource link ID value (optional, default is null)
     *
     * @return ResourceLink
     */
    public static function fromContext($context, $ltiResourceLinkId, $tempId = null)
    {
        $resourceLink = new ResourceLink();
        $resourceLink->setContextId($context->getRecordId());
        $resourceLink->context = $context;
        $resourceLink->dataConnector = $context->getDataConnector();
        $resourceLink->ltiResourceLinkId = $ltiResourceLinkId;
        if (!empty($ltiResourceLinkId)) {
            $resourceLink->load();
            if (is_null($resourceLink->id) && !empty($tempId)) {
                $resourceLink->ltiResourceLinkId = $tempId;
                $resourceLink->load();
                $resourceLink->ltiResourceLinkId = $ltiResourceLinkId;
            }
        }

        return $resourceLink;
    }

    /**
     * Load the resource link from the database.
     *
     * @param int $id     Record ID of resource link
     * @param DataConnector   $dataConnector    Database connection object
     *
     * @return ResourceLink  ResourceLink object
     */
    public static function fromRecordId($id, $dataConnector)
    {
        $resourceLink = new ResourceLink();
        $resourceLink->dataConnector = $dataConnector;
        $resourceLink->load($id);

        return $resourceLink;
    }

###
###  PRIVATE METHODS
###

    /**
     * Load the resource link from the database.
     *
     * @param int $id     Record ID of resource link (optional, default is null)
     *
     * @return bool    True if resource link was successfully loaded
     */
    private function load($id = null)
    {
        $this->initialize();
        $this->id = $id;

        return $this->getDataConnector()->loadResourceLink($this);
    }

    /**
     * Convert data type of value to a supported type if possible.
     *
     * @param Outcome  $ltiOutcome     Outcome object
     * @param array    $supportedTypes Array of outcome types to be supported (optional, default is null to use supported types reported in the last launch for this resource link)
     *
     * @return bool    True if the type/value are valid and supported
     */
    private function checkValueType($ltiOutcome, $supportedTypes = null)
    {
        if (empty($supportedTypes)) {
            $supportedTypes = explode(',',
                str_replace(' ', '', strtolower($this->getSetting('ext_ims_lis_resultvalue_sourcedids', self::EXT_TYPE_DECIMAL))));
        }
        $type = $ltiOutcome->type;
        $value = $ltiOutcome->getValue();
// Check whether the type is supported or there is no value
        $ok = in_array($type, $supportedTypes) || (strlen($value) <= 0);
        if (!$ok) {
// Convert numeric values to decimal
            if ($type === self::EXT_TYPE_PERCENTAGE) {
                if (substr($value, -1) === '%') {
                    $value = substr($value, 0, -1);
                }
                $ok = is_numeric($value) && ($value >= 0) && ($value <= 100);
                if ($ok) {
                    $ltiOutcome->setValue($value / 100);
                    $ltiOutcome->type = self::EXT_TYPE_DECIMAL;
                }
            } elseif ($type === self::EXT_TYPE_RATIO) {
                $parts = explode('/', $value, 2);
                $ok = (count($parts) === 2) && is_numeric($parts[0]) && is_numeric($parts[1]) && ($parts[0] >= 0) && ($parts[1] > 0);
                if ($ok) {
                    $ltiOutcome->setValue($parts[0] / $parts[1]);
                    $ltiOutcome->type = self::EXT_TYPE_DECIMAL;
                }
// Convert letter_af to letter_af_plus or text
            } elseif ($type === self::EXT_TYPE_LETTER_AF) {
                if (in_array(self::EXT_TYPE_LETTER_AF_PLUS, $supportedTypes)) {
                    $ok = true;
                    $ltiOutcome->type = self::EXT_TYPE_LETTER_AF_PLUS;
                } elseif (in_array(self::EXT_TYPE_TEXT, $supportedTypes)) {
                    $ok = true;
                    $ltiOutcome->type = self::EXT_TYPE_TEXT;
                }
// Convert letter_af_plus to letter_af or text
            } elseif ($type === self::EXT_TYPE_LETTER_AF_PLUS) {
                if (in_array(self::EXT_TYPE_LETTER_AF, $supportedTypes) && (strlen($value) === 1)) {
                    $ok = true;
                    $ltiOutcome->type = self::EXT_TYPE_LETTER_AF;
                } elseif (in_array(self::EXT_TYPE_TEXT, $supportedTypes)) {
                    $ok = true;
                    $ltiOutcome->type = self::EXT_TYPE_TEXT;
                }
// Convert text to decimal
            } elseif ($type === self::EXT_TYPE_TEXT) {
                $ok = is_numeric($value) && ($value >= 0) && ($value <= 1);
                if ($ok) {
                    $ltiOutcome->type = self::EXT_TYPE_DECIMAL;
                } elseif (substr($value, -1) === '%') {
                    $value = substr($value, 0, -1);
                    $ok = is_numeric($value) && ($value >= 0) && ($value <= 100);
                    if ($ok) {
                        if (in_array(self::EXT_TYPE_PERCENTAGE, $supportedTypes)) {
                            $ltiOutcome->type = self::EXT_TYPE_PERCENTAGE;
                        } else {
                            $ltiOutcome->setValue($value / 100);
                            $ltiOutcome->type = self::EXT_TYPE_DECIMAL;
                        }
                    }
                }
            }
        }

        return $ok;
    }

    /**
     * Send a service request to the tool consumer.
     *
     * @param string $type   Message type value
     * @param string $url    URL to send request to
     * @param array  $params Associative array of parameter values to be passed
     *
     * @return bool    True if the request successfully obtained a response
     */
    private function doService($type, $url, $params)
    {
        $ok = false;
        $this->extRequest = '';
        $this->extRequestHeaders = '';
        $this->extResponse = '';
        $this->extResponseHeaders = '';
        $this->lastServiceRequest = null;
        if (!empty($url)) {
            $params = $this->getConsumer()->signParameters($url, $type, $this->getConsumer()->ltiVersion, $params);
// Connect to tool consumer
            $http = new HttpMessage($url, 'POST', $params);
// Parse XML response
            if ($http->send()) {
                $this->extResponse = $http->response;
                $this->extResponseHeaders = $http->responseHeaders;
                try {
                    $this->extDoc = new DOMDocument();
                    $this->extDoc->loadXML($http->response);
                    $this->extNodes = $this->domnodeToArray($this->extDoc->documentElement);
                    if (isset($this->extNodes['statusinfo']['codemajor']) && ($this->extNodes['statusinfo']['codemajor'] === 'Success')) {
                        $ok = true;
                    }
                } catch (\Exception $e) {

                }
            }
            $this->extRequest = $http->request;
            $this->extRequestHeaders = $http->requestHeaders;
            $this->lastServiceRequest = $http;
        }

        return $ok;
    }

    /**
     * Send a request to the Result service endpoint.
     *
     * @param UserResult $userResult   UserResult object
     * @param string $url  URL to send request to
     *
     * @return Outcome    Outcome object
     */
    private function doResultService($userResult, $url)
    {
        $outcome = null;
        $this->extRequest = '';
        $this->extRequestHeaders = '';
        $this->extResponse = '';
        $this->extResponseHeaders = '';
        $this->lastServiceRequest = null;
        if (!empty($url)) {
            $resultService = new Service\Result($this->getConsumer(), $url);
            $outcome = $resultService->get($userResult);
            $http = $resultService->getHttpMessage();
            $this->extResponse = $http->response;
            $this->extResponseHeaders = $http->responseHeaders;
            $this->extRequest = $http->request;
            $this->extRequestHeaders = $http->requestHeaders;
            $this->lastServiceRequest = $http;
        }

        return $outcome;
    }

    /**
     * Send a service request to the Score service endpoint.
     *
     * @param Outcome $ltiOutcome   Outcome object
     * @param UserResult $userResult   UserResult object
     * @param string $url  URL to send request to
     *
     * @return bool    True if the request successfully obtained a response
     */
    private function doScoreService($ltiOutcome, $userResult, $url)
    {
        $ok = false;
        $this->extRequest = '';
        $this->extRequestHeaders = '';
        $this->extResponse = '';
        $this->extResponseHeaders = '';
        $this->lastServiceRequest = null;
        if (!empty($url)) {
            $scoreService = new Service\Score($this->getConsumer(), $url);
            $scoreService->submit($ltiOutcome, $userResult);
            $http = $scoreService->getHttpMessage();
            $this->extResponse = $http->response;
            $this->extResponseHeaders = $http->responseHeaders;
            $ok = $http->ok;
            $this->extRequest = $http->request;
            $this->extRequestHeaders = $http->requestHeaders;
            $this->lastServiceRequest = $http;
        }

        return $ok;
    }

    /**
     * Send a service request to the tool consumer.
     *
     * @param string $type Message type value
     * @param string $url  URL to send request to
     * @param string $xml  XML of message request
     *
     * @return bool    True if the request successfully obtained a response
     */
    private function doLTI11Service($type, $url, $xml)
    {
        $ok = false;
        $this->extRequest = '';
        $this->extRequestHeaders = '';
        $this->extResponse = '';
        $this->extResponseHeaders = '';
        $this->lastServiceRequest = null;
        if (!empty($url)) {
            $id = uniqid();
            $xmlRequest = <<< EOD
<?xml version = "1.0" encoding = "UTF-8"?>
<imsx_POXEnvelopeRequest xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
  <imsx_POXHeader>
    <imsx_POXRequestHeaderInfo>
      <imsx_version>V1.0</imsx_version>
      <imsx_messageIdentifier>{$id}</imsx_messageIdentifier>
    </imsx_POXRequestHeaderInfo>
  </imsx_POXHeader>
  <imsx_POXBody>
    <{$type}Request>
{$xml}
    </{$type}Request>
  </imsx_POXBody>
</imsx_POXEnvelopeRequest>
EOD;
// Add message signature
            $consumer = $this->getConsumer();
            $header = $consumer->addSignature($url, $xmlRequest, 'POST', 'application/xml');
// Connect to tool consumer
            $http = new HttpMessage($url, 'POST', $xmlRequest, $header);
// Parse XML response
            if ($http->send()) {
                $this->extResponse = $http->response;
                $this->extResponseHeaders = $http->responseHeaders;
                try {
                    $this->extDoc = new DOMDocument();
                    $this->extDoc->loadXML($http->response);
                    $this->extNodes = $this->domnodeToArray($this->extDoc->documentElement);
                    if (isset($this->extNodes['imsx_POXHeader']['imsx_POXResponseHeaderInfo']['imsx_statusInfo']['imsx_codeMajor']) &&
                        ($this->extNodes['imsx_POXHeader']['imsx_POXResponseHeaderInfo']['imsx_statusInfo']['imsx_codeMajor'] === 'success')) {
                        $ok = true;
                    }
                } catch (\Exception $e) {

                }
            }
            $this->extRequest = $http->request;
            $this->extRequestHeaders = $http->requestHeaders;
            $this->lastServiceRequest = $http;
        }

        return $ok;
    }

    /**
     * Convert DOM nodes to array.
     *
     * @param DOMElement $node XML element
     *
     * @return array|string Array of XML document elements
     */
    private function domnodeToArray($node)
    {
        $output = array();
        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
            case XML_TEXT_NODE:
                $output = trim($node->textContent);
                break;
            case XML_ELEMENT_NODE:
                for ($i = 0; $i < $node->childNodes->length; $i++) {
                    $child = $node->childNodes->item($i);
                    $v = $this->domnodeToArray($child);
                    if (isset($child->tagName)) {
                        $output[$child->tagName][] = $v;
                    } else {
                        $s = (string) $v;
                        if (!empty($s)) {
                            $output = $s;
                        }
                    }
                }
                if (is_array($output)) {
                    if ($node->hasAttributes()) {
                        foreach ($node->attributes as $attrNode) {
                            $output['@attributes'][$attrNode->name] = (string) $attrNode->value;
                        }
                    }
                    foreach ($output as $t => $v) {
                        if (is_array($v) && (count($v) === 1) && ($t !== '@attributes')) {
                            $output[$t] = $v[0];
                        }
                    }
                }
                break;
        }

        return $output;
    }

}
