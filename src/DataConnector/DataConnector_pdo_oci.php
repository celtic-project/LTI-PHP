<?php
declare(strict_types=1);

namespace ceLTIc\LTI\DataConnector;

/**
 * Class to represent an LTI Data Connector for PDO variations for Oracle connections
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @copyright  SPV Software Products
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class DataConnector_pdo_oci extends DataConnector_pdo
{

    /**
     * Array of identity field sequence names
     *
     * @var array $sequence
     */
    private static array $sequence = [];

    /**
     * Class constructor
     *
     * @param object $db                 Database connection object
     * @param string $dbTableNamePrefix  Prefix for database table names (optional, default is none)
     */
    public function __construct(object $db, string $dbTableNamePrefix = '')
    {
        parent::__construct($db, $dbTableNamePrefix);
        $this->dateFormat = 'd-M-Y';
        $db->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, true);
        if (empty(self::$sequence)) {
            $n = strlen($this->dbTableNamePrefix) + 6;
            $sql = <<< EOD
SELECT TABLE_NAME, DATA_DEFAULT FROM USER_TAB_COLUMNS
WHERE (TABLE_NAME LIKE UPPER('{$this->dbTableNamePrefix}lti2_%')) AND (COLUMN_NAME = UPPER(CONCAT(SUBSTR(TABLE_NAME, {$n}), '_pk')))
EOD;
            $query = $this->db->prepare($sql);
            if ($this->executeQuery($sql, $query)) {
                $row = $query->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($row as $entry) {
                    self::$sequence[substr($entry['TABLE_NAME'], strlen($this->dbTableNamePrefix))] = str_replace('nextval',
                        'currval', $entry['DATA_DEFAULT']);
                }
            }
        }
    }

###
###    PROTECTED METHODS
###

    /**
     * Get the ID for the last record inserted into a table.
     *
     * @param string $tableName   Name of database table
     *
     * @return int  Id of last inserted record
     */
    protected function getLastInsertId(string $tableName): int
    {
        $pk = 0;
        $sql = 'SELECT ' . self::$sequence[strtoupper($tableName)] . ' FROM dual';
        $query = $this->db->prepare($sql);
        if ($this->executeQuery($sql, $query)) {
            $row = $query->fetch(\PDO::FETCH_ASSOC);
            $pk = intval(array_values($row)[0]);
        }
        return $pk;
    }

}
