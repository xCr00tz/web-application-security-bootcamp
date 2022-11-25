<?php

namespace Concrete\Core\Database\Connection;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Schema\Column as DbalColumn;
use Doctrine\DBAL\Schema\Table as DbalTable;
use Doctrine\DBAL\Types\Type as DbalType;
use Doctrine\ORM\EntityManager;
use Exception;
use ORM;
use PDO;

class Connection extends \Doctrine\DBAL\Connection
{
    /** @var EntityManager */
    protected $entityManager;

    /**
     * The supported character sets and associated default collation.
     *
     * @var null|array NULL if not yet initialized; an array with keys (character set - always lower case) and values (default collation for the character set - always lower case) otherwise
     */
    protected $supportedCharsets;

    /**
     * The supported collations and the associated character sets.
     *
     * @var null|array NULL if not yet initialized; an array with keys (collation) and values (associated character set) otherwise
     */
    protected $supportedCollations;

    /**
     * Overridden params.
     *
     * @var array
     */
    private $overriddenParams = [];

    /**
     * @deprecated Please use the ORM facade instead of this method:
     * - ORM::entityManager() in the application/site code and core
     * - $pkg->getEntityManager() in packages
     *
     * @return EntityManager
     */
    public function getEntityManager()
    {
        if (!$this->entityManager) {
            $this->entityManager = $this->createEntityManager();
        }

        return $this->entityManager;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     *
     * @return EntityManager
     */
    public function createEntityManager()
    {
        return ORM::entityManager();
    }

    /**
     * Returns true if a table exists – is NOT case sensitive.
     *
     * @param mixed $tableName
     *
     * @return bool
     */
    public function tableExists($tableName)
    {
        $sm = $this->getSchemaManager();
        $schemaTables = $sm->listTableNames();

        return in_array(strtolower($tableName), array_map('strtolower', $schemaTables));
    }

    /**
     * @deprecated
     *
     * @param mixed $q
     * @param mixed $arguments
     */
    public function Execute($q, $arguments = [])
    {
        if ($q instanceof \Doctrine\DBAL\Statement) {
            return $q->execute($arguments);
        } else {
            if (!is_array($arguments)) {
                $arguments = [$arguments]; // adodb backward compatibility
            }

            return $this->executeQuery($q, $arguments);
        }
    }

    public function query()
    {
        $args = func_get_args();
        if (isset($args) && isset($args[1]) && (is_string($args[1]) || is_array($args[1]))) {
            return $this->executeQuery($args[0], $args[1]);
        } else {
            return call_user_func_array('parent::query', $args);
        }
    }

    /**
     * This is essentially a workaround for not being able to define indexes on TEXT fields with the current version of Doctrine DBAL.
     * This feature will be removed when DBAL will support it, so don't use this feature.
     *
     * @param array $textIndexes
     */
    public function createTextIndexes(array $textIndexes)
    {
        if (!empty($textIndexes)) {
            $sm = $this->getSchemaManager();
            foreach ($textIndexes as $tableName => $indexes) {
                if ($sm->tablesExist([$tableName])) {
                    $existingIndexNames = array_map(
                        function (\Doctrine\DBAL\Schema\Index $index) {
                            return $index->getShortestName('');
                        },
                        $sm->listTableIndexes($tableName)
                    );
                    $chunks = [];
                    foreach ($indexes as $indexName => $indexColumns) {
                        if (!in_array(strtolower($indexName), $existingIndexNames, true)) {
                            $newIndexColumns = [];
                            foreach ((array) $indexColumns as $indexColumn) {
                                $indexColumn = (array) $indexColumn;
                                $s = $this->quoteIdentifier($indexColumn[0]);
                                if (!empty($indexColumn[1])) {
                                    $s .= '(' . (int) $indexColumn[1] . ')';
                                }
                                $newIndexColumns[] = $s;
                            }
                            $chunks[] = $this->quoteIdentifier($indexName) . ' (' . implode(', ', $newIndexColumns) . ')';
                        }
                    }
                    if (!empty($chunks)) {
                        $sql = 'ALTER TABLE ' . $this->quoteIdentifier($tableName) . ' ADD INDEX ' . implode(', ADD INDEX ', $chunks);
                        $this->executeQuery($sql);
                    }
                }
            }
        }
    }

    /**
     * @deprecated
     * alias to old ADODB method
     *
     * @param mixed $q
     * @param mixed $arguments
     */
    public function GetRow($q, $arguments = [])
    {
        if (!is_array($arguments)) {
            $arguments = [$arguments]; // adodb backward compatibility
        }
        $r = $this->fetchAssoc($q, $arguments);
        if (!is_array($r)) {
            $r = [];
        }

        return $r;
    }

    /**
     * @deprecated
     * alias to old ADODB method
     *
     * @param mixed $string
     */
    public function qstr($string)
    {
        return $this->quote($string);
    }

    /**
     * @deprecated
     * alias to old ADODB method
     *
     * @param mixed $q
     * @param mixed $arguments
     */
    public function GetOne($q, $arguments = [])
    {
        if (!is_array($arguments)) {
            $arguments = [$arguments]; // adodb backward compatibility
        }

        return $this->fetchColumn($q, $arguments, 0);
    }

    /**
     * @deprecated
     * alias to old ADODB method
     */
    public function ErrorMsg()
    {
        if ($this->errorCode() > 0) {
            return $this->errorCode();
        }

        return false;
    }

    /**
     * @deprecated
     * alias to old ADODB method
     *
     * @param mixed $q
     * @param mixed $arguments
     */
    public function GetAll($q, $arguments = [])
    {
        if (!is_array($arguments)) {
            $arguments = [$arguments]; // adodb backward compatibility
        }

        return $this->fetchAll($q, $arguments);
    }

    /**
     * @deprecated
     * alias to old ADODB method
     *
     * @param mixed $q
     * @param mixed $arguments
     */
    public function GetArray($q, $arguments = [])
    {
        return $this->GetAll($q, $arguments);
    }

    /**
     * @deprecated
     * alias to old ADODB method
     *
     * @param mixed $q
     * @param mixed $arguments
     */
    public function GetAssoc($q, $arguments = [])
    {
        $query = $this->query($q, $arguments);

        return $query->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    /**
     * @deprecated
     * Returns an associative array of all columns in a table
     *
     * @param mixed $table
     */
    public function MetaColumnNames($table)
    {
        $sm = $this->getSchemaManager();
        $columnNames = [];
        $columns = $sm->listTableColumns($table);
        foreach ($columns as $column) {
            $columnNames[] = $column->getName();
        }

        return $columnNames;
    }

    /**
     * Insert or update a row in a database table.
     *
     * @param string $table the name of the database table
     * @param array $fieldArray array keys are the field names, array values are the field values
     * @param string|string[] $keyCol the names of the primary key fields
     * @param bool $autoQuote set to true to quote the field values
     */
    public function replace($table, $fieldArray, $keyCol, $autoQuote = true)
    {
        $qb = $this->createQueryBuilder();
        $qb->select('count(*)')->from($table, 't');
        $where = $qb->expr()->andX();
        $updateKeys = [];
        if (!is_array($keyCol)) {
            $keyCol = [$keyCol];
        }
        foreach ($keyCol as $key) {
            if (isset($fieldArray[$key])) {
                $field = $fieldArray[$key];
            } else {
                $field = null;
            }
            $updateKeys[$key] = $field;
            if ($autoQuote) {
                $field = $qb->expr()->literal($field);
            }
            $where->add($qb->expr()->eq($key, $field));
        }
        $qb->where($where);
        $sql = $qb->getSql();
        $num = parent::query($sql)->fetchColumn();
        if ($num) {
            $update = true;
        } else {
            try {
                $this->insert($table, $fieldArray);
                $update = false;
            } catch (UniqueConstraintViolationException $x) {
                $update = true;
            }
        }
        if ($update) {
            $this->update($table, $fieldArray, $updateKeys);
        }
    }

    /**
     * @deprecated -
     * alias to old ADODB method
     *
     * @param mixed $q
     * @param mixed $arguments
     */
    public function GetCol($q, $arguments = [])
    {
        $r = $this->fetchAll($q, $arguments);
        $return = [];

        foreach ($r as $value) {
            $return[] = $value[key($value)];
        }

        return $return;
    }

    /**
     * @deprecated
     * alias to old ADODB method
     */
    public function Insert_ID()
    {
        return $this->lastInsertId();
    }

    /**
     * @deprecated
     */
    public function MetaTables()
    {
        $sm = $this->getSchemaManager();
        $schemaTables = $sm->listTables();
        $tables = [];
        foreach ($schemaTables as $table) {
            $tables[] = $table->getName();
        }

        return $tables;
    }

    /**
     * @deprecated
     *
     * @param mixed $table
     */
    public function MetaColumns($table)
    {
        $sm = $this->getSchemaManager();
        $schemaColumns = $sm->listTableColumns($table);

        return $schemaColumns;
    }

    /**
     * @deprecated Alias to old ADODB method
     */
    public function BeginTrans()
    {
        $this->beginTransaction();

        return true;
    }

    /**
     * @deprecated Alias to old ADODB method
     */
    public function StartTrans()
    {
        $this->beginTransaction();

        return true;
    }

    /**
     * @deprecated Alias to old ADODB method
     */
    public function CommitTrans()
    {
        $this->commit();

        return true;
    }

    /**
     * @deprecated Alias to old ADODB method
     */
    public function CompleteTrans()
    {
        $this->commit();

        return true;
    }

    /**
     * @deprecated Alias to old ADODB method
     */
    public function RollbackTrans()
    {
        $this->rollBack();

        return true;
    }

    /**
     * @deprecated Alias to old ADODB method
     */
    public function FailTrans()
    {
        $this->rollBack();

        return true;
    }

    /**
     * Get the supported character sets and associated default collation.
     *
     * @throws \Exception throws an exception in case of errors
     *
     * @return array keys: character set (always lower case); array values: default collation for the character set (always lower case)
     */
    public function getSupportedCharsets()
    {
        if ($this->supportedCharsets === null) {
            $supportedCharsets = [];
            $rs = $this->executeQuery('SHOW CHARACTER SET');
            while (($row = $rs->fetch(PDO::FETCH_ASSOC)) !== false) {
                if (!isset($row['Charset']) || !isset($row['Default collation'])) {
                    throw new Exception(t('Unrecognized result of the "%s" database query.', 'SHOW CHARACTER SET'));
                }
                $supportedCharsets[strtolower($row['Charset'])] = strtolower($row['Default collation']);
            }
            $this->supportedCharsets = $supportedCharsets;
        }

        return $this->supportedCharsets;
    }

    /**
     * Get the supported collations and the associated character sets.
     *
     * @throws \Exception throws an exception in case of errors
     *
     * @return array keys: collation (always lower case); array values: associated character set (always lower case)
     */
    public function getSupportedCollations()
    {
        if ($this->supportedCollations === null) {
            $supportedCollations = [];
            $rs = $this->executeQuery('SHOW COLLATION');
            while (($row = $rs->fetch(PDO::FETCH_ASSOC)) !== false) {
                if (!isset($row['Collation']) || !isset($row['Charset'])) {
                    throw new Exception(t('Unrecognized result of the "%s" database query.', 'SHOW COLLATION'));
                }
                $supportedCollations[strtolower($row['Collation'])] = strtolower($row['Charset']);
            }
            $this->supportedCollations = $supportedCollations;
        }

        return $this->supportedCollations;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Doctrine\DBAL\Connection::getParams()
     */
    public function getParams()
    {
        $result = $this->overriddenParams + parent::getParams();
        // Forward the connection charset/collate to the default table options
        if (!isset($result['defaultTableOptions']['charset']) && !isset($result['defaultTableOptions']['collate'])) {
            if (isset($result['character_set']) && isset($result['collation'])) {
                if (!isset($result['defaultTableOptions'])) {
                    $result['defaultTableOptions'] = [];
                }
                $result['defaultTableOptions']['charset'] = $result['character_set'];
                $result['defaultTableOptions']['collate'] = $result['collation'];
            }
        }

        return $result;
    }

    /**
     * Check if a collation can be used for keys of a specific length.
     *
     * @param string $collation the name of a collation
     * @param int $fieldLength the length (in chars) of a field to be used as key/index
     *
     * @return bool
     */
    public function isCollationSupportedForKeys($collation, $fieldLength)
    {
        $sm = $this->getSchemaManager();
        $existingTables = array_map('strtolower', $sm->listTableNames());
        for ($i = 0; ; ++$i) {
            $tableName = 'tmp_checkCollationFieldLength' . $i;
            if (!in_array(strtolower($tableName), $existingTables)) {
                break;
            }
        }
        $column = new DbalColumn('ColumnName', DbalType::getType(DbalType::STRING), ['length' => (int) $fieldLength]);
        $column->setPlatformOption('collation', (string) $collation);
        $table = new DbalTable($tableName, [$column]);
        $table->setPrimaryKey([$column->getName()]);
        try {
            $sm->createTable($table);
        } catch (Exception $x) {
            // SQLSTATE[42000]: Syntax error or access violation: 1071 Specified key was too long; max key length is XYZ bytes
            return false;
        }
        try {
            $sm->dropTable($tableName);
        } catch (Exception $x) {
        }

        return true;
    }

    /**
     * @param string $characterSet
     * @param string $collation
     *
     * @internal
     */
    public function refreshCharactersetCollation($characterSet, $collation)
    {
        $this->executeQuery('SET NAMES ' . $this->quote($characterSet) . ' COLLATE ' . $this->quote($collation));
        $this->overriddenParams['character_set'] = $characterSet;
        $this->overriddenParams['collation'] = $collation;
    }
}
