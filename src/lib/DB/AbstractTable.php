<?php
namespace Ipol\DPD\DB;

/**
 * Абстрактный класс реализующий взаимодействие с одной таблицей
 */
abstract class AbstractTable implements TableInterface
{
    protected $connection;

    /**
     * Конструктор класса
     * 
     * @param \Ipol\DPD\DB\ConnectionInterface
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Возвращает соединение с БД
     * 
     * @return \Ipol\DPD\DB\ConnectionInterface
     */
    public function getConnection() 
    {
        return $this->connection;
    }

    /**
     * Возвращает конфиг
     * 
     * @return \Ipol\DPD\Config\ConfigInterface
     */
    public function getConfig()
    {
        return $this->getConnection()->getConfig();
    }

    /**
     * Возвращает инстанс PDO
     * 
     * @return \PDO
     */
    public function getPDO()
    {
        return $this->getConnection()->getPDO();
    }

    /**
     * Возвращает имя класса модели
     * 
     * @return array
     */
    public function getModelClass()
    {
        return \Ipol\DPD\DB\Model::class;
    }

    /**
     * Возвращает инстанс модели ассоциированной с таблицой
     * 
     * @return \Ipol\DPD\DB\Model
     */
    public function makeModel($id = false)
    {
        $classname = $this->getModelClass();

        return new $classname($this, $id);
    }

    /**
     * Возвращает список моделей отобранных по условию
     *
     * @param array $parms
     * 
     * @return array
     */
    public function findModels($parms)
    {
        $items = $this->find($parms);
        $ret = [];

        while($item = $items->fetch()) {
            $ret[] = $this->makeModel($item);
        }

        return $ret;
    }

    /**
     * Создание таблицы при необходимости
     * 
     * @return void
     */
    public function checkTableSchema()
	{
        $sqlPath = sprintf('%s/db/install/%s/%s.sql',
            $this->getConfig()->get('DATA_DIR'),
            str_replace('pdo_', '', $this->getConnection()->getDriver()),
            $this->getTableName()
        );

        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            $this->getPDO()->query($sql);
        }
	}

	

    /**
     * Добавление записи
     * 
     * @param array $values
     * 
     * @return bool
     */
    public function add($values)
    {
        $fields       = array_keys($values);
        $values       = $this->prepareParms($values);
        $placeholders = array_keys($values);
        
        $sql = 'INSERT INTO '
            . $this->getTableName() 
            . ' ('. implode(',', $fields) .') VALUES ('
            . implode(',', $placeholders) .')'
        ;

        return $this->getPDO()
                    ->prepare($sql)
                    ->execute($values)
                ? $this->getPDO()->lastInsertId()
                : false;
    }

    /**
     * Обновление записи
     * 
     * @param int   $id
     * @param array $values
     * 
     * @return bool
     */
    public function update($id, $values)
    {
        $fields       = array_keys($values);
        $values       = $this->prepareParms($values);
        $placeholders = array_keys($values);
        
        $sql = 'UPDATE '. $this->getTableName() .' SET ';
        foreach ($fields as $i => $field) {
            $sql .= $field .'='. $placeholders[$i] .',';
        }
        $sql = trim($sql, ',') . ' WHERE id = :id_where';

        return $this->getPDO()
                    ->prepare($sql)
                    ->execute(array_merge(
                        $values, 
                        [':id_where' => $id]
                    ));
    }

    /**
     * Удаление записи
     * 
     * @param int $id
     * 
     * @return bool
     */
    public function delete($id)
    {
        $sql = 'DELETE FROM '. $this->getTableName() .' WHERE id = :id';

        return $this->getPDO()
                    ->prepare($sql)
                    ->execute([':id' => $id]);
    }
    
    /**
     * Выборка записей
     * 
     * $parms = "id = 1" or
     * $parms = [
     *  'select' => '*',
     *  'where'  => 'id = :id',
     *  'order'  => 'id asc',
     *  'limit'  => '0,1',
     *  'bind'   => [':id' => 1]
     * ]
     * 
     * @param string|array $parms
     * 
     * @return \PDOStatement
     */
    public function find($parms = [])
    {
        $parms = is_array($parms)
            ? $parms
            : [
                'where' => $parms,
            ]
        ;
        
        $sql = sprintf('SELECT %s FROM %s %s %s %s',
            isset($parms['select'])     ? $parms['select'] : implode(',', array_keys($this->getFields())),
            $this->getTableName(),
            isset($parms['where'])      ? "WHERE {$parms['where']}" : '',
            isset($parms['order'])      ? "ORDER BY {$parms['order']}"   : '',
            isset($parms['limit'])      ? "LIMIT {$parms['limit']}"      : ''
        );

        $query  = $this->getPDO()->prepare($sql);
        $result = isset($parms['bind']) ? $query->execute($parms['bind']) : $query->execute();
        
        return $result ? $query : false;
    }

    /**
     * Выборка одной записи, псевдномим над find limit 0,1
     * 
     * @param int|string|array $parms
     * 
     * @return array
     */
    public function findFirst($parms = [])
    {
        if (is_numeric($parms)) {
            $parms = ['where' => 'id = :id', 'bind' => ['id' => $parms]];
        } elseif (is_string($parms)) {
            $parms = ['where' => $parms];
        }

        $parms['limit'] = '0,1';

        return $this->find($parms)->fetch();
    }

    /**
     * Составляет массив bind-values для передачи в PDO
     * 
     * @param array $parms
     * 
     * @return array
     */
    protected function prepareParms($parms)
    {
        $ret = array();
        foreach ($parms as $k => $v) {
            $ret[':'. $k] = $v;
        }

        return $ret;
    }
}