<?php

namespace ceLTIc\LTI\DataConnector;

use ceLTIc\LTI;

/**
 * Class to represent an LTI Data Connector for PDO variations for PostgreSQL connections
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class DataConnector_pdo_pgsql extends DataConnector_pdo
{
###
###  ResourceLink methods
###

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
        $id = $resourceLink->getRecordId();
        $userResults = array();

        if ($localOnly) {
            $sql = 'SELECT u.user_result_pk, u.lti_result_sourcedid, u.lti_user_id, u.created, u.updated ' .
                "FROM {$this->dbTableNamePrefix}" . static::USER_RESULT_TABLE_NAME . ' AS u ' .
                "INNER JOIN {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' AS rl ' .
                'ON u.resource_link_pk = rl.resource_link_pk ' .
                'WHERE (rl.resource_link_pk = :id) AND (rl.primary_resource_link_pk IS NULL)';
            $query = $this->db->prepare($sql);
            $query->bindValue('id', $id, \PDO::PARAM_INT);
        } else {
            $sql = 'SELECT u.user_result_pk, u.lti_result_sourcedid, u.lti_user_id, u.created, u.updated ' .
                "FROM {$this->dbTableNamePrefix}" . static::USER_RESULT_TABLE_NAME . ' AS u ' .
                "INNER JOIN {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_TABLE_NAME . ' AS rl ' .
                'ON u.resource_link_pk = rl.resource_link_pk ' .
                'WHERE ((rl.resource_link_pk = :id) AND (rl.primary_resource_link_pk IS NULL)) OR ' .
                '((rl.primary_resource_link_pk = :pid) AND share_approved)';
            $query = $this->db->prepare($sql);
            $query->bindValue('id', $id, \PDO::PARAM_INT);
            $query->bindValue('pid', $id, \PDO::PARAM_INT);
        }
        if ($this->executeQuery($sql, $query)) {
            while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
                $row = array_change_key_case($row);
                $userresult = LTI\UserResult::fromRecordId($row['user_result_pk'], $resourceLink->getDataConnector());
                $userresult->setRecordId(intval($row['user_result_pk']));
                $userresult->ltiResultSourcedId = $row['lti_result_sourcedid'];
                $userresult->created = strtotime($row['created']);
                $userresult->updated = strtotime($row['updated']);
                if (is_null($idScope)) {
                    $userResults[] = $userresult;
                } else {
                    $userResults[$userresult->getId($idScope)] = $userresult;
                }
            }
        }

        return $userResults;
    }

###
###  ResourceLinkShareKey methods
###

    /**
     * Save resource link share key object.
     *
     * @param ResourceLinkShareKey $shareKey Resource link share key object
     *
     * @return bool    True if the resource link share key object was successfully saved
     */
    public function saveResourceLinkShareKey($shareKey)
    {
        $id = $shareKey->getId();
        $expires = date("{$this->dateFormat} {$this->timeFormat}", $shareKey->expires);
        $sql = "INSERT INTO {$this->dbTableNamePrefix}" . static::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . ' ' .
            '(share_key_id, resource_link_pk, auto_approve, expires) ' .
            'VALUES (:id, :prlid, :approve, :expires)';
        $query = $this->db->prepare($sql);
        $query->bindValue('id', $id, \PDO::PARAM_STR);
        $query->bindValue('prlid', $shareKey->resourceLinkId, \PDO::PARAM_INT);
        $query->bindValue('approve', $shareKey->autoApprove, \PDO::PARAM_INT);
        $query->bindValue('expires', $expires, \PDO::PARAM_STR);
        $ok = $this->executeQuery($sql, $query);

        return $ok;
    }

}
