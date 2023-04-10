<?php
declare(strict_types=1);

namespace ceLTIc\LTI;

use ceLTIc\LTI\DataConnector\DataConnector;
use ceLTIc\LTI\Platform;
use ceLTIc\LTI\Context;
use ceLTIc\LTI\Enum\IdScope;

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
    public ?string $ltiResultSourcedId = null;

    /**
     * Date/time the record was created.
     *
     * @var int|null $created
     */
    public ?int $created = null;

    /**
     * Date/time the record was last updated.
     *
     * @var int|null $updated
     */
    public ?int $updated = null;

    /**
     * Resource link object.
     *
     * @var ResourceLink|null $resourceLink
     */
    private ?ResourceLink $resourceLink = null;

    /**
     * Resource link record ID.
     *
     * @var int|null $resourceLinkId
     */
    private ?int $resourceLinkId = null;

    /**
     * UserResult record ID value.
     *
     * @var int|null $id
     */
    private ?int $id = null;

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
     * Initialise the user.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->ltiResultSourcedId = null;
        $this->created = null;
        $this->updated = null;
    }

    /**
     * Save the user to the database.
     *
     * @return bool  True if the user object was successfully saved
     */
    public function save(): bool
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
     * @return bool  True if the user object was successfully deleted
     */
    public function delete(): bool
    {
        $ok = $this->getDataConnector()->deleteUserResult($this);

        return $ok;
    }

    /**
     * Get resource link.
     *
     * @return ResourceLink  Resource link object
     */
    public function getResourceLink(): ResourceLink
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
     *
     * @return void
     */
    public function setResourceLink(ResourceLink $resourceLink): void
    {
        $this->resourceLink = $resourceLink;
    }

    /**
     * Get record ID of user.
     *
     * @return int|null  Record ID of user
     */
    public function getRecordId(): ?int
    {
        return $this->id;
    }

    /**
     * Set record ID of user.
     *
     * @param int|null $id  Record ID of user
     *
     * @return void
     */
    public function setRecordId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * Set resource link ID of user.
     *
     * @param int|null $resourceLinkId  Resource link ID of user
     *
     * @return void
     */
    public function setResourceLinkId(?int $resourceLinkId): void
    {
        $this->resourceLink = null;
        $this->resourceLinkId = $resourceLinkId;
    }

    /**
     * Get the data connector.
     *
     * @return DataConnector|null  Data connector object
     */
    public function getDataConnector(): ?DataConnector
    {
        return $this->dataConnector;
    }

    /**
     * Set the data connector.
     *
     * @param DataConnector|null $dataConnector  Data connector object
     *
     * @return void
     */
    public function setDataConnector(?DataConnector $dataConnector): void
    {
        $this->dataConnector = $dataConnector;
    }

    /**
     * Get the user ID (which may be a compound of the platform and resource link IDs).
     *
     * @param int $idScope                   Scope to use for user ID (optional, default is null for consumer default setting)
     * @param Context|Platform|null $source  Context or Platform for user (optional)
     *
     * @return string|null  UserResult ID value, or null on error
     */
    public function getId(?IdScope $idScope = null, Context|Platform|null $source = null): ?string
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
            $idScope = IdScope::IdOnly;
        }
        $ok = !empty($key) || ($idScope === IdScope::IdOnly);
        if ($ok) {
            $id = $key . IdScope::SEPARATOR;
            switch ($idScope) {
                case IdScope::Platform:
                    $id .= $this->ltiUserId;
                    break;
                case IdScope::Context:
                    $ok = !is_null($context) && !empty($context->ltiContextId);
                    if ($ok) {
                        $id .= $context->ltiContextId . IdScope::SEPARATOR . $this->ltiUserId;
                    }
                    break;
                case IdScope::Resource:
                    $ok = !is_null($resourceLink) && !empty($resourceLink->ltiResourceLinkId);
                    if ($ok) {
                        $id .= $resourceLink->ltiResourceLinkId . IdScope::SEPARATOR . $this->ltiUserId;
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
     * @param int $id                       Record ID of user
     * @param DataConnector $dataConnector  Database connection object
     *
     * @return UserResult  UserResult object
     */
    public static function fromRecordId(int $id, DataConnector $dataConnector): UserResult
    {
        $userResult = new UserResult();
        $userResult->dataConnector = $dataConnector;
        $userResult->load($id);

        return $userResult;
    }

    /**
     * Class constructor from resource link.
     *
     * @param ResourceLink|null $resourceLink  ResourceLink object
     * @param string $ltiUserId                UserResult ID value
     *
     * @return UserResult UserResult object
     */
    public static function fromResourceLink(?ResourceLink $resourceLink, string $ltiUserId): UserResult
    {
        $userResult = new UserResult();
        $userResult->resourceLink = $resourceLink;
        if (!is_null($resourceLink)) {
            $userResult->resourceLinkId = $resourceLink->getRecordId();
            $userResult->dataConnector = $resourceLink->getDataConnector();
        }
        $userResult->ltiUserId = $ltiUserId;
        if (!empty($ltiUserId)) {
            $userResult->load();
        }

        return $userResult;
    }

###
###  PRIVATE METHODS
###

    /**
     * Load the user from the database.
     *
     * @param int $id  Record ID of user (optional, default is null)
     *
     * @return bool  True if the user object was successfully loaded
     */
    private function load(?int $id = null): bool
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
