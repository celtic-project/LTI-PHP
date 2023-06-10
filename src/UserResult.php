<?php

namespace ceLTIc\LTI;

use ceLTIc\LTI\DataConnector\DataConnector;
use ceLTIc\LTI\Platform;
use ceLTIc\LTI\Context;

/**
 * Class to represent a platform user association with a resource link
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
     * @var int|null $created
     */
    public $created = null;

    /**
     * Date/time the record was last updated.
     *
     * @var int|null $updated
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
     * @var int|null $id
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
     * @return int|null  Record ID of user
     */
    public function getRecordId()
    {
        return $this->id;
    }

    /**
     * Set record ID of user.
     *
     * @param int|null $id  Record ID of user
     */
    public function setRecordId($id)
    {
        $this->id = $id;
    }

    /**
     * Set resource link ID of user.
     *
     * @param int|null $resourceLinkId  Resource link ID of user
     */
    public function setResourceLinkId($resourceLinkId)
    {
        $this->resourceLink = null;
        $this->resourceLinkId = $resourceLinkId;
    }

    /**
     * Get the data connector.
     *
     * @return DataConnector|null  Data connector object
     */
    public function getDataConnector()
    {
        return $this->dataConnector;
    }

    /**
     * Set the data connector.
     *
     * @param DataConnector|null $dataConnector  Data connector object
     */
    public function setDataConnector($dataConnector)
    {
        $this->dataConnector = $dataConnector;
    }

    /**
     * Get the user ID (which may be a compound of the platform and resource link IDs).
     *
     * @param int $idScope Scope to use for user ID (optional, default is null for consumer default setting)
     * @param Context|Platform|null $source   Context or Platform for user (optional)
     *
     * @return string|null   UserResult ID value, or null on error
     */
    public function getId($idScope = null, $source = null)
    {
        $resourceLink = $this->getResourceLink();
        $context = null;
        $platform = null;
        if ($source instanceof Context) {
            $context = $source;
            $platform = $context->getPlatform();
        } elseif (!is_null($resourceLink)) {
            $context = $resourceLink->getContext();
            $platform = $resourceLink->getPlatform();
        } elseif ($source instanceof Platform) {
            $platform = $source;
        }
        $key = '';
        if (!is_null($platform)) {
            $key = $platform->getId();
            if (is_null($idScope)) {
                $idScope = $platform->idScope;
            }
        }
        if (is_null($idScope)) {
            $idScope = Tool::ID_SCOPE_ID_ONLY;
        }
        $ok = !empty($key) || ($idScope === Tool::ID_SCOPE_ID_ONLY);
        if ($ok) {
            $id = $key . Tool::ID_SCOPE_SEPARATOR;
            switch ($idScope) {
                case Tool::ID_SCOPE_GLOBAL:
                    $id .= $this->ltiUserId;
                    break;
                case Tool::ID_SCOPE_CONTEXT:
                    $ok = !is_null($context) && !empty($context->ltiContextId);
                    if ($ok) {
                        $id .= $context->ltiContextId . Tool::ID_SCOPE_SEPARATOR . $this->ltiUserId;
                    }
                    break;
                case Tool::ID_SCOPE_RESOURCE:
                    $ok = !is_null($resourceLink) && !empty($resourceLink->ltiResourceLinkId);
                    if ($ok) {
                        $id .= $resourceLink->ltiResourceLinkId . Tool::ID_SCOPE_SEPARATOR . $this->ltiUserId;
                    }
                    break;
                default:
                    $id = $this->ltiUserId;
                    break;
            }
        }
        if (!$ok) {
            $id = null;
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
     * @param ResourceLink|null $resourceLink  ResourceLink object
     * @param string            $ltiUserId     UserResult ID value
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
     * @param int|null $id  Record ID of user (optional, default is null)
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
