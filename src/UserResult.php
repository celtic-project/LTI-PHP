<?php

namespace ceLTIc\LTI;

use ceLTIc\LTI\DataConnector;

/**
 * Class to represent a platform user
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class UserResult extends User
{

    /**
     * UserResult's result sourcedid.
     *
     * @var string|null $ltiResultSourcedId
     */
    public $ltiResultSourcedId = null;

    /**
     * Date/time the record was created.
     *
     * @var datetime|null $created
     */
    public $created = null;

    /**
     * Date/time the record was last updated.
     *
     * @var datetime|null $updated
     */
    public $updated = null;

    /**
     * Resource link object.
     *
     * @var ResourceLink|null $resourceLink
     */
    private $resourceLink = null;

    /**
     * Resource link record ID.
     *
     * @var int|null $resourceLinkId
     */
    private $resourceLinkId = null;

    /**
     * UserResult record ID value.
     *
     * @var string|null $id
     */
    private $id = null;

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
     * Initialise the user.
     */
    public function initialize()
    {
        parent::initialize();
        $this->ltiResultSourcedId = null;
        $this->created = null;
        $this->updated = null;
    }

    /**
     * Save the user to the database.
     *
     * @return bool    True if the user object was successfully saved
     */
    public function save()
    {
        if (!is_null($this->resourceLinkId)) {
            $ok = $this->getDataConnector()->saveUserResult($this);
        } else {
            $ok = true;
        }

        return $ok;
    }

    /**
     * Delete the user from the database.
     *
     * @return bool    True if the user object was successfully deleted
     */
    public function delete()
    {
        $ok = $this->getDataConnector()->deleteUserResult($this);

        return $ok;
    }

    /**
     * Get resource link.
     *
     * @return ResourceLink Resource link object
     */
    public function getResourceLink()
    {
        if (is_null($this->resourceLink) && !is_null($this->resourceLinkId)) {
            $this->resourceLink = ResourceLink::fromRecordId($this->resourceLinkId, $this->getDataConnector());
        }

        return $this->resourceLink;
    }

    /**
     * Set resource link.
     *
     * @param ResourceLink $resourceLink  Resource link object
     */
    public function setResourceLink($resourceLink)
    {
        $this->resourceLink = $resourceLink;
    }

    /**
     * Get record ID of user.
     *
     * @return int Record ID of user
     */
    public function getRecordId()
    {
        return $this->id;
    }

    /**
     * Set record ID of user.
     *
     * @param int $id  Record ID of user
     */
    public function setRecordId($id)
    {
        $this->id = $id;
    }

    /**
     * Set resource link ID of user.
     *
     * @param int $resourceLinkId  Resource link ID of user
     */
    public function setResourceLinkId($resourceLinkId)
    {
        $this->resourceLink = null;
        $this->resourceLinkId = $resourceLinkId;
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
     * Set the data connector.
     *
     * @param DataConnector $dataConnector  Data connector object
     */
    public function setDataConnector($dataConnector)
    {
        $this->dataConnector = $dataConnector;
    }

    /**
     * Get the user ID (which may be a compound of the platform and resource link IDs).
     *
     * @param int $idScope Scope to use for user ID (optional, default is null for consumer default setting)
     *
     * @return string UserResult ID value
     */
    public function getId($idScope = null, $platform = null)
    {
        $key = '';
        if (is_null($platform) && !is_null($this->getResourceLink())) {
            $platform = $this->getResourceLink()->getPlatform();
        }
        if (!is_null($platform)) {
            $key = $platform->getId();
        }
        if (is_null($idScope) && !is_null($this->getResourceLink())) {
            $idScope = $this->resourceLink->getPlatform()->idScope;
        }
        if (is_null($idScope)) {
            $idScope = Tool::ID_SCOPE_ID_ONLY;
        }
        switch ($idScope) {
            case Tool::ID_SCOPE_GLOBAL:
                $id = $key . Tool::ID_SCOPE_SEPARATOR . $this->ltiUserId;
                break;
            case Tool::ID_SCOPE_CONTEXT:
                if ($this->resourceLink->getContext() && $this->resourceLink->getContext()->ltiContextId) {
                    $id = $key . Tool::ID_SCOPE_SEPARATOR . $this->resourceLink->getContext()->ltiContextId;
                }
                $id .= Tool::ID_SCOPE_SEPARATOR . $this->ltiUserId;
                break;
            case Tool::ID_SCOPE_RESOURCE:
                if (!is_null($this->resourceLink) && !empty($this->resourceLink->ltiResourceLinkId)) {
                    $id = $key . Tool::ID_SCOPE_SEPARATOR . $this->resourceLink->ltiResourceLinkId;
                }
                $id .= Tool::ID_SCOPE_SEPARATOR . $this->ltiUserId;
                break;
            default:
                $id = $this->ltiUserId;
                break;
        }

        return $id;
    }

    /**
     * Load the user from the database.
     *
     * @param int $id     Record ID of user
     * @param DataConnector   $dataConnector    Database connection object
     *
     * @return UserResult  UserResult object
     */
    public static function fromRecordId($id, $dataConnector)
    {
        $userresult = new UserResult();
        $userresult->dataConnector = $dataConnector;
        $userresult->load($id);

        return $userresult;
    }

    /**
     * Class constructor from resource link.
     *
     * @param ResourceLink $resourceLink   ResourceLink object
     * @param string       $ltiUserId      UserResult ID value
     *
     * @return UserResult UserResult object
     */
    public static function fromResourceLink($resourceLink, $ltiUserId)
    {
        $userresult = new UserResult();
        $userresult->resourceLink = $resourceLink;
        if (!is_null($resourceLink)) {
            $userresult->resourceLinkId = $resourceLink->getRecordId();
            $userresult->dataConnector = $resourceLink->getDataConnector();
        }
        $userresult->ltiUserId = $ltiUserId;
        if (!empty($ltiUserId)) {
            $userresult->load();
        }

        return $userresult;
    }

###
###  PRIVATE METHODS
###

    /**
     * Load the user from the database.
     *
     * @param int $id     Record ID of user (optional, default is null)
     *
     * @return bool    True if the user object was successfully loaded
     */
    private function load($id = null)
    {
        $this->initialize();
        $this->id = $id;
        $dataConnector = $this->getDataConnector();
        if (!is_null($dataConnector)) {
            return $dataConnector->loadUserResult($this);
        }

        return false;
    }

}
