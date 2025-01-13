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
	public function getTableName(): string
    {
		return 'b_ipol_dpd_location';
	}

	/**
	 * Возвращает список полей и их значения по умолчанию
	 *
	 * @return array
	 */
	public function getFields(): array
    {
		return [
			'ID'              => null,
			'COUNTRY_CODE'    => null,
			'COUNTRY_NAME'    => null,
			'REGION_CODE'     => null,
			'REGION_NAME'     => null,
			'CITY_ID'         => null,
			'CITY_CODE'       => null,
			'CITY_NAME'       => null,
			'CITY_ABBR'       => null,
			'IS_CASH_PAY'     => null,
			'ORIG_NAME'       => null,
			'ORIG_NAME_LOWER' => null,
			'IS_CITY'         => null,
		];
	}

	/**
	 * Возвращает normalizer адресов
	 *
	 * @return Normalizer
	 */
	public function getNormalizer(): Normalizer
    {
		return new Normalizer();
	}

    /**
     * Возвращает запись по ID города
     *
     * @param $cityId
     * @param string $select
     *
     * @return array
     */
	public function getByCityId($cityId, string $select = '*'): array
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
	public function getByAddress(string $country, string $region, string $city, string $select = '*'): array
    {
		$city = $this->getNormalizer()->normilize($country, $region, $city);

		if (empty($city['CITY_ABBR'])) {
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

		return $this->findFirst([
			'select' => $select,
			'where'  => 'COUNTRY_NAME = :country AND REGION_NAME = :region AND CITY_NAME = :city AND IS_CITY = :is_city',
			'bind'   => [
				'country' => $city['COUNTRY_NAME'],
				'region'  => $city['REGION_NAME'],
				'city'    => $city['CITY_NAME'],
				'is_city' => $city['IS_CITY'],
			]
		]);
	}
}
