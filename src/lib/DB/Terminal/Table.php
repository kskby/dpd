<?php
namespace Ipol\DPD\DB\Terminal;

use \Ipol\DPD\DB\AbstractTable;
use Ipol\DPD\DB\Model;

/**
 * Класс для работы с таблицей терминалов
 */
class Table extends AbstractTable
{
	/**
	 * Возвращает имя таблицы
	 *
	 * @return string
	 */
	public function getTableName(): string
    {
		return 'b_ipol_dpd_terminal';
	}

	/**
	 * Возвращает название класса модели
	 *
	 * @return string
	 */
	public function getModelClass(): string
    {
		return Model::class;
	}

	/**
	 * Возвращает список полей и их значения по умолчанию
	 *
	 * @return array
	 */
	public function getFields(): array
    {
		return [
			'ID'                        => null,
			'LOCATION_ID'               => null,
			'CODE'                      => null,
			'NAME'                      => null,
			'ADDRESS_FULL'              => null,
			'ADDRESS_SHORT'             => null,
			'ADDRESS_DESCR'             => null,
			'PARCEL_SHOP_TYPE'          => null,
			'SCHEDULE_SELF_PICKUP'      => null,
			'SCHEDULE_SELF_DELIVERY'    => null,
			'SCHEDULE_PAYMENT_CASH'     => null,
			'SCHEDULE_PAYMENT_CASHLESS' => null,
			'SCHEDULE_PAYMENTS'         => null,
			'LATITUDE'                  => 0,
			'LONGITUDE'                 => 0,
			'IS_LIMITED'                => 'N',
			'LIMIT_MAX_SHIPMENT_WEIGHT' => 0,
			'LIMIT_MAX_WEIGHT'          => 0,
			'LIMIT_MAX_LENGTH'          => 0,
			'LIMIT_MAX_WIDTH'           => 0,
			'LIMIT_MAX_HEIGHT'          => 0,
			'LIMIT_MAX_VOLUME'          => 0,
			'LIMIT_SUM_DIMENSION'       => 0,
			'NPP_AMOUNT'                => 0,
			'NPP_AVAILABLE'             => 'N',
			// 'SERVICES'                  => null,
		];
	}

	/**
	 * Ищет терминалы по местоположению
	 *
	 * @param int $locationId
	 * @param array|string $select
	 *
	 * @return \PDOStatement
	 */
	public function findByLocationId(int $locationId, array|string $select = '*'): \PDOStatement
    {
		return $this->find([
			'select' => $select,
			'where'  => 'LOCATION_ID = :location_id',
			'bind'   => [
				':location_id' => $locationId
			]
		]);
	}

    /**
     * Возвращает запись о терминале по его коду
     *
     * @param $code
     * @param string $select
     * @return Model|bool
     */
	public function getByCode($code, $select = '*'): Model|bool
    {
		$data = $this->findFirst([
			'select' => $select,
			'where'  => 'CODE = :code',
			'bind'   => [
				':code' => $code,
			]
		]);

		if ($data) {
			return static::makeModel($data);
		}

		return false;
	}
}
