<?php
namespace Ipol\DPD\DB\Location;

use Ipol\DPD\DB\AbstractTable;

/**
 * Класс для работы с таблицей местоположений
 */
class Table extends AbstractTable
{
	/**
	 * Возвращает имя таблицы
	 * 
	 * @return string
	 */
	public function getTableName()
	{
		return 'b_ipol_dpd_location';
	}

	/**
	 * Возвращает список полей и их значения по умолчанию
	 * 
	 * @return array
	 */
	public function getFields()
	{
		return [
			'ID'           => null,
			'COUNTRY_CODE' => null,
			'COUNTRY_NAME' => null,
			'REGION_CODE'  => null,
			'REGION_NAME'  => null,
			'CITY_CODE'    => null,
			'CITY_NAME'    => null,
			'CITY_ABBR'    => null,
			'IS_CASH_PAY'  => null,
			'CITY_ID'      => null,
		];
	}

	/**
	 * Возвращает normalizer адресов
	 * 
	 * @return \Ipol\DPD\DB\Location\Normalizer
	 */
	public function getNormalizer()
	{
		return new Normalizer();
	}

	/**
	 * Возвращает запись по ID города
	 * 
	 * @param  int $locationId
	 * @param  array  $select
	 * 
	 * @return array|false
	 */
	public function getByCityId($cityId, $select = '*')
	{
		return $this->findFirst([
			'select' => $select,
			'where'  => 'CITY_ID = :city_id',
			'bind'   => [
				':city_id' => $cityId,
			]
		]);
	}

	/**
	 * Производит поиск города по текстовому названию в БД
	 * 
	 * @param string $country Название страны
	 * @param string $region  Название региона
	 * @param string $city    Название города
	 * @param string $select  список полей которые необходимо выбрать
	 * 
	 * @return array
	 */
	public function getByAddress($country, $region, $city, $select = '*')
	{
		$city = $this->getNormalizer()->normilize($country, $region, $city);
		
		return $this->findFirst([
			'select' => $select,
			'where'  => 'COUNTRY_NAME = :country AND REGION_NAME = :region AND CITY_NAME = :city',
			'bind'   => [
				'country' => $city['COUNTRY_NAME'],
				'region'  => $city['REGION_NAME'],
				'city'    => $city['CITY_NAME'],
			]
		]);
	}
}