<?php
namespace Ipol\DPD\DB;

use AllowDynamicProperties;
use Exception;
use PDO;
use Ipol\DPD\Config\ConfigInterface;

/**
 * Класс реализует соединения с БД и организует доступ к таблицами
 */
#[AllowDynamicProperties] class Connection implements ConnectionInterface
{
    protected static ConnectionInterface|Connection $instance;

    protected static array $classmap = [
        'location' => '\\Ipol\\DPD\\DB\\Location\\Table',
		'terminal' => '\\Ipol\\DPD\\DB\\Terminal\\Table',
		'order'    => '\\Ipol\\DPD\\DB\\Order\\Table',
    ];

    protected array $tables = [];

    /**
     * Возвращает инстанс подключения
     *
     * @param ConfigInterface $config
     * @return ConnectionInterface|Connection
     */
    public static function getInstance(ConfigInterface $config): ConnectionInterface|Connection
    {
        return self::$instance = self::$instance ?? new static($config);
    }

    /**
     * Конструктор класса
     *
     * <li>string  $dsn        The DSN string</li>
     * <li>string  $username   (optional) Username</li>
     * <li>string  $password   (optional) Password</li>
     * <li>string  $driver     (optional) Driver's name</li>
     * <li>PDO     $pdo        (optional) PDO object</li>
     */
    public function __construct(ConfigInterface $config)
    {
        $dbConfig = $config->get('DB');

        $this->config   = $config;
        $this->dsn      = $dbConfig['DSN'];
        $this->username = $dbConfig['USERNAME'];
        $this->password = $dbConfig['PASSWORD'];
        $this->driver   = $dbConfig['DRIVER'];
        $this->pdo      = $dbConfig['PDO'];

        self::$instance = $this;

        $this->init();
    }

    /**
     * Возвращает конфиг
     *
     * @return ConfigInterface
     */
    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    /**
     * Returns the DSN associated with this connection
     *
     * @return  string
     */
    public function getDSN(): string
    {
        return $this->dsn;
    }

    /**
     * Returns the driver's name
     *
     * @return  string
     */
    public function getDriver(): string
    {
        if ($this->driver === null) {
            $this->driver = $this->getPDO()->getAttribute(PDO::ATTR_DRIVER_NAME);
        }
        return $this->driver;
    }

    /**
     * Returns the PDO object associated with this connection
     *
     * @return \PDO
     */
    public function getPDO(): PDO
    {
        if (is_null($this->pdo)) {
            $this->pdo = new PDO($this->dsn, $this->username, $this->password);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        }

        return $this->pdo;
    }

    /**
     * Возвращает маппер для таблицы
     *
     * @param string $tableName имя маппера/таблицы
     *
     * @return TableInterface
     * @throws Exception
     */
    public function getTable(string $tableName): TableInterface
    {
        if (isset(static::$classmap[$tableName])) {
            if (!isset($this->tables[$tableName])) {
                $this->tables[$tableName] = new static::$classmap[$tableName]($this);
                $this->tables[$tableName]->checkTableSchema();
            }

            return $this->tables[$tableName];
		}

		throw new Exception("Data mapper for {$tableName} not found");
    }

    protected function init(): void
    {
        if (strtoupper($this->getDriver()) == 'MYSQL') {
            $this->getPDO()->query('SET NAMES UTF8');
        }
    }
}
