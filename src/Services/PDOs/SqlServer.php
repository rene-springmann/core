<?php

namespace Core\Services\PDOs;

use Core\Services\AbstractDatabase;
use Core\Services\PDOs\Schemas\SqlServerSchema;
use PDO;

/**
 * Microsoft SQL Server Access Layer
 *
 * This class based on Laravel's 5.3 SqlServerConnector (License MIT)
 *
 * @see http://php.net/manual/en/ref.pdo-sqlsrv.php Installing PDO Driver PDO_SQLSRV
 * @see https://github.com/illuminate/database/blob/5.3/Connectors/SqlServerConnector.php Laravel's 5.3 SqlServerConnector on GitHub by Taylor Otwell
 */
class SqlServer extends AbstractDatabase
{
    /**
     * @inheritdoc
     */
    protected $supportsSavepoints = false;

    /**
     * @inheritdoc
     */
    protected $dateFormat = 'Y-m-d H:i:s.000';

//    /**
//     * Standard Case Insensitive Collation (default by SQL Server)
//     *
//     * @var string
//     */
//    private $collateCI = 'Latin1_General_CI_AS';
//
//    /**
//     * Standard Case Sensitive Collation
//     *
//     * @var string
//     */
//    private $collateCS = 'Latin1_General_CS_AS';

    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        // set default configuration
        parent::__construct(array_merge([
            'port'       => 1433,
            'dateformat' => 'Y-m-d H:i:s.000',
        ], $config));
    }

    /**
     * @inheritdoc
     */
    protected function makeSchema()
    {
        return new SqlServerSchema($this);
    }

    /**
     * Version
     *
     * @inheritdoc
     */
    public function version()
    {
        return 2008;
    }

    /**
     * @inheritdoc
     */
    public function quoteName($name)
    {
        return '[' . str_replace(']', ']]', $name) . ']';
    }

    /**
     * @inheritdoc
     */
    protected function makePDO(array $config, array $options)
    {
        //$options[PDO::ATTR_EMULATE_PREPARES] = false; // driver does not support this attribute

        $drivers = PDO::getAvailableDrivers();
        if (in_array('dblib', $drivers)) {
            $dsn = $this->getDblibDsn($config);
        }
        elseif (isset($config['odbc']) && in_array('odbc', $drivers)) {
            $dsn = $this->getOdbcDsn($config);
        }
        else {
            $dsn = $this->getSqlSrvDsn($config);
        }

        $username = $config['username'];
        $password = $config['password'];
        $pdo      = new PDO($dsn, $username, $password, $options);

        return $pdo;
    }

    /**
     * Get the DSN string for a DbLib connection.
     *
     * @param  array  $config
     * @return string
     */
    private function getDblibDsn(array $config)
    {
        $arguments = [
            'host'   => $this->buildHostString($config, ':'),
            'dbname' => $config['database'],
        ];

        if (isset($config['appname'])) {
            $arguments['appname'] = $config['appname'];
        }

        if (isset($config['charset'])) {
            $arguments['charset'] = $config['charset'];
        }

        return $this->buildConnectString('dblib', $arguments);
    }

    /**
     * Get the DSN string for an ODBC connection.
     *
     * @param  array  $config
     * @return string
     */
    private function getOdbcDsn(array $config)
    {
        return 'odbc:' . $config['odbc'];
    }

    /**
     * Get the DSN string for a SqlSrv connection.
     *
     * @param  array  $config
     * @return string
     */
    private function getSqlSrvDsn(array $config)
    {
        $arguments = [
            'Server' => $this->buildHostString($config, ','),
        ];

        $arguments['Database'] = $config['database'];

        if (isset($config['appname'])) {
            $arguments['APP'] = $config['appname'];
        }

        if (isset($config['readonly'])) {
            $arguments['ApplicationIntent'] = 'ReadOnly';
        }

        if (isset($config['pooling']) && $config['pooling'] === false) {
            $arguments['ConnectionPooling'] = '0';
        }

        return $this->buildConnectString('sqlsrv', $arguments);
    }

    /**
     * Build a connection string from the given arguments.
     *
     * @param  string  $driver
     * @param  array  $arguments
     * @return string
     */
    private function buildConnectString($driver, array $arguments)
    {
        $options = array_map(function ($key) use ($arguments) {
            return sprintf('%s=%s', $key, $arguments[$key]);
        }, array_keys($arguments));

        return $driver.':'.implode(';', $options);
    }

    /**
     * Build a host string from the given configuration.
     *
     * @param  array  $config
     * @param  string  $separator
     * @return string
     */
    private function buildHostString(array $config, $separator)
    {
        if (isset($config['port'])) {
            return $config['host'].$separator.$config['port'];
        } else {
            return $config['host'];
        }
    }
}