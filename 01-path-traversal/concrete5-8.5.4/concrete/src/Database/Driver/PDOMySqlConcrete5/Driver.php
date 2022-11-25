<?php
namespace Concrete\Core\Database\Driver\PDOMySqlConcrete5;

use Concrete\Core\Database\Connection\PDOConnection;
use Concrete\Core\Database\Platforms\MySQL80Platform;
use Concrete\Core\Database\Schema\MySqlSchemaManager;

/**
 * PDO MySql driver.
 *
 * @since 2.0
 */
class Driver extends \Doctrine\DBAL\Driver\PDOMySql\Driver
{
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
        $conn = new PDOConnection(
            $this->_constructPdoDsn($params),
            $username,
            $password,
            $driverOptions
        );

        return $conn;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Doctrine\DBAL\Driver\AbstractMySQLDriver::createDatabasePlatformForVersion()
     */
    public function createDatabasePlatformForVersion($version)
    {
        if (false === stripos($version, 'mariadb')) {
            if (preg_match('/^(\d+)/', $version, $match)) {
                if ((int) $match[1] >= 8) {
                    return new MySQL80Platform();
                }
            }
        }

        return parent::createDatabasePlatformForVersion($version);
    }

    /**
     * {@inheritDoc}
     *
     * @see \Doctrine\DBAL\Driver\AbstractMySQLDriver::getSchemaManager()
     */
    public function getSchemaManager(\Doctrine\DBAL\Connection $conn)
    {
        return new MySqlSchemaManager($conn);
    }

    /**
     * Constructs the MySql PDO DSN.
     *
     * @param array $params
     *
     * @return string the DSN
     */
    private function _constructPdoDsn(array $params)
    {
        $dsn = 'mysql:';
        if (isset($params['host']) && $params['host'] != '') {
            $dsn .= 'host=' . $params['host'] . ';';
        }
        if (isset($params['port'])) {
            $dsn .= 'port=' . $params['port'] . ';';
        }
        if (isset($params['database'])) {
            $dsn .= 'dbname=' . $params['database'] . ';';
        }
        if (isset($params['unix_socket'])) {
            $dsn .= 'unix_socket=' . $params['unix_socket'] . ';';
        }
        if (isset($params['character_set'])) {
            // Let's use the new character_set parameter
            $charset = $params['character_set'];
        } elseif (isset($params['charset'])) {
            // Let's use the old charset parameter
            $charset = $params['charset'];
        } else {
            $charset = null;
        }
        if ($charset !== null) {
            $dsn .= 'charset=' . $charset . ';';
        }

        return $dsn;
    }
}
