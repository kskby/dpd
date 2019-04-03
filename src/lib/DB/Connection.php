<?php
namespace Ipol\DPD\DB;

use \PDO;
use \Ipol\DPD\Config\ConfigInterface;

/**
 * Класс реализует соединения с БД и организует доступ к таблицами
 */
class Connection implements ConnectionInterface
{
    protected static $instance;
    
    /**
     * @var array
     */
    protected static $classmap = array(
        'location' => '\\Ipol\\DPD\\DB\\Location\\Table',
		'terminal' => '\\Ipol\\DPD\\DB\\Terminal\\Table',
		'order'    => '\\Ipol\\DPD\\DB\\Order\\Table',
    );

    /**
     * @var array
     */
    protected $tables = array();
    
    /**
     * Возвращает инстанс подключения
     * 
     * @return \Ipol\DPD\DB\ConnectionInterface
     */
    public static function getInstance(ConfigInterface $config)
    {
        return self::$instance = self::$instance ?: new static($config);
    }

    /**
     * Конструктор класса
     * 
     * @param   string  $dsn        The DSN string
     * @param   string  $username   (optional) Username
     * @param   string  $password   (optional) Password
     * @param   string  $driver     (optional) Driver's name
     * @param   PDO     $pdo        (optional) PDO object
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
     * @return \Ipol\DPD\Config\ConfigInterface
     */
    public function getConfig()
    {
        return $this->config;
    }
    
    /**
     * Returns the DSN associated with this connection
     *
     * @return  string
     */
    public function getDSN()
    {
        return $this->dsn;
    }
    
    /**
     * Returns the driver's name
     *
     * @return  string
     */
    public function getDriver()
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
    public function getPDO()
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
     * @return \Ipol\DPD\DB\TableInterface
     */
    public function getTable($tableName)
    {
        if (isset(static::$classmap[$tableName])) {
            if (!isset($this->tables[$tableName])) {
                $this->tables[$tableName] = new static::$classmap[$tableName]($this);
                $this->tables[$tableName]->checkTableSchema();
            }

            return $this->tables[$tableName];
		}

		throw new \Exception("Data mapper for {$tableName} not found");
    }

    protected function init()
    {
        if (strtoupper($this->getDriver()) == 'MYSQL') {
            $this->getPDO()->query('SET NAMES UTF8');
        }
    }
}