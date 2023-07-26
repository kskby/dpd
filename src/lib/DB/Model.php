<?php
namespace Ipol\DPD\DB;

use \Bitrix\Main\SystemException;
use Exception;
use \Ipol\DPD\Utils;

/**
 * Класс модели таблицы
 * Каждый экземпляр класса - одна строка из таблицы
 *
 * К значениям полей можно обратиться двумя способами
 *
 * - как к св-ву объекта, в этом случае перед чтением/записи св-ва
 *   будет произведен поиск метода setPopertyName/getPropertyName
 *   и если они есть они будут вызваны и возвращен результат этого вызова
 *
 * - как к массиву, в этом случае данные будут записаны/возвращены как есть
 */
class Model implements \ArrayAccess
{
	/**
	 * Поля записи
	 */
	protected array|bool $fields = false;

	/**
	 * @var TableInterface
	 */
	protected TableInterface $table;

	/**
	 * @return TableInterface
	 */
	public function getTable(): TableInterface
    {
		return $this->table;
	}

	/**
	 * Конструктор класса
	 *
	 * @param mixed $id ID или массив полей сущности
	 */
	public function __construct(TableInterface $table, $id = false)
	{
		$this->table  = $table;
		$this->fields = $table->getFields();

		$this->load($id);
	}

	/**
	 * Получает поля сущности из БД
	 *
	 * @param  mixed $id ID или массив полей сущности
	 *
	 * @return bool
	 */
	public function load($id): bool
    {
		if (!$id) {
			return false;
		}

		$data = is_array($id)
			? $id
			: $this->getTable()->findFirst($id)
		;

		if (!$data) {
			return false;
		}

		$this->fields = $data;
		$this->afterLoad();

		return true;
	}

	/**
	 * Вызывается после получения полей сущности из БД
	 *
	 * @return void
	 */
	public function afterLoad()
	{}

    /**
     * Добавляет запись в таблицу
     *
     * @return bool
     * @throws Exception
     */
	public function insert(): bool
    {
		if ($this->id) {
			throw new Exception('Record is exists');
		}

		$ret = $this->getTable()->add($this->fields);

		if ($ret) {
			$this->id = $ret;
		}

		return $ret;
	}

    /**
     * Обновляет запись в таблице
     *
     * @return bool
     * @throws Exception
     */
	public function update()
	{
		if (!$this->id) {
			throw new Exception('Record is not exists');
		}

        return $this->getTable()->update($this->id, $this->fields);
	}

    /**
     * Сохраняет запись вне зависимости от ее состояния
     *
     * @return bool
     * @throws Exception
     */
	public function save(): bool
    {
		if ($this->id) {
			return $this->update();
		}

		return $this->insert();
	}

    /**
     * Удаляет запись из таблицы
     *
     * @return bool
     * @throws Exception
     */
	public function delete(): bool
    {
		if (!$this->id) {
			throw new Exception('Record is not exists');
		}

		$ret = $this->getTable()->delete($this->id);

		if ($ret) {
			$this->id = null;
		}

		return $ret;
	}

    /**
     * Возвращает представление записи в виде массива
     *
     * @return bool|array
     */
	public function getArrayCopy(): bool|array
    {
		return $this->fields;
	}

	/**
	 * Проверяет существование св-ва
	 *
	 * @param  string  $prop
	 * @return boolean
	 */
	public function __isset($prop)
	{
		$prop = Utils::camelCaseToUnderScore($prop);

		return array_key_exists($prop, $this->fields);
	}

    /**
     * Удаляет св-во сущности
     *
     * @param string $prop
     *
     * @return void
     * @throws Exception
     */
	public function __unset($prop)
	{
		throw new Exception("Can\'t be removed property {$prop}");
	}

    /**
     * Получает значение св-ва сущности
     *
     * @param string $prop
     *
     * @return mixed
     * @throws Exception
     */
	public function __get($prop)
	{
		$method = 'get'. Utils::UnderScoreToCamelCase($prop, true);
		if (method_exists($this, $method)) {
			return $this->$method();
		}

		$prop = Utils::camelCaseToUnderScore($prop);
		if (!$this->__isset($prop)) {
			throw new Exception("Missing property {$prop}");
		}

		return $this->fields[$prop];
	}

    /**
     * Задает значение св-ва сущности
     *
     * @param string $prop
     * @param mixed $value
     *
     * @return void
     * @throws Exception
     */
	public function __set($prop, $value)
	{
		$method = 'set'. Utils::UnderScoreToCamelCase($prop, true);
		if (method_exists($this, $method)) {
			return $this->$method($value);
		}

		$prop = Utils::camelCaseToUnderScore($prop);
		if (!$this->__isset($prop)) {
			throw new Exception("Missing property {$prop}");
		}

		$this->fields[$prop] = $value;
	}

	/**
	 * @param string $prop
	 *
	 * @return bool
	 */
	public function offsetExists($prop): bool
	{
		return $this->__isset($prop);
	}

    /**
     * @param string $prop
     *
     * @return void
     * @throws Exception
     */
	public function offsetUnset($prop): void
	{
		throw new Exception("Can\'t be removed property {$prop}");
	}

    /**
     * @param string $prop
     *
     * @return mixed
     * @throws Exception
     */
	public function offsetGet($prop): mixed
	{
		if (!$this->offsetExists($prop)) {
			throw new Exception("Missing property {$prop}");
		}

		return $this->fields[$prop];
	}

    /**
     * @param string $prop
     * @param $value
     * @return void
     * @throws Exception
     */
	public function offsetSet($prop, $value): void
	{
		if (!$this->offsetExists($prop)) {
			throw new Exception("Missing property {$prop}");
		}

		$this->fields[$prop] = $value;
	}
}
