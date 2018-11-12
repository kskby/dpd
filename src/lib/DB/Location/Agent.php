<?php
namespace Ipol\DPD\DB\Location;

use \Ipol\DPD\Config\ConfigInterface;
use \Ipol\DPD\API\User\UserInterface;
use \Ipol\DPD\DB\TableInterface;
use \Ipol\DPD\Utils;

/**
 * Класс реализует методы обновления информации о городах в которых работает DPD
 */
class Agent
{
	/**
	 * @deprecated
	 */
	protected static $cityFilePath = 'ftp://intergration:xYUX~7W98@ftp.dpd.ru:22/integration/GeographyDPD_20171125.csv';

	protected $api;
	protected $table;

	/**
	 * Конструктор
	 * 
	 * @param \Ipol\DPD\User\UserInterface $api   инстанс API
	 * @param \Ipol\DPD\DB\TableInterface  $table инстанс таблицы для записи данных в БД
	 */
	public function __construct(UserInterface $api, TableInterface $table)
	{
		$this->api   = $api;
		$this->table = $table;
	}

	/**
	 * @return \Ipol\DPD\User\UserInterface
	 */
	public function getApi()
	{
		return $this->api;
	}

	/**
	 * @return \Ipol\DPD\DB\Location\Table
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * Возвращает normalizer адресов
	 * 
	 * @return \Ipol\DPD\DB\Location\Normilizer
	 */
	public function getNormalizer()
	{
		return $this->getTable()->getNormalizer();
	}

	/**
	 * @return string
	 */
	public function getCityFilePath()
	{
		return $this->getTable()->getConfig()->get('DATA_DIR') .'/cities.csv';

		// return 'ftp://intergration:xYUX~7W98@ftp.dpd.ru:22/integration/GeographyDPD_20171125.csv';
	}

	/**
	 * Обновляет список городов обслуживания
	 * 
	 * @param integer $position Стартовая позиция курсора в файле
	 * @param array   $countries Массив стран для обработки
	 * 
	 * @return true|integer
	 */
	public function loadAll($position = 0, $countries = ['RU', 'KZ', 'BY', 'UA'])
	{
		ini_set('auto_detect_line_endings', true);
		
		$start_time = time();
		$countries  = array_intersect_key([
				'RU' => 'россия', 
				'KZ' => 'казахстан', 
				'BY' => 'беларусь', 
				'UA' => 'украина'
			], array_flip($countries)
		);

		$file = fopen($this->getCityFilePath(), 'r');
		if ($file === false) {
			return false;
		}

		fseek($file, $position ?: 0);

		while(($row = fgetcsv($file, null, ';')) !== false) {
			if (Utils::isNeedBreak($start_time)) {
				return ftell($file);
			}

			$country = $row[5];
			$region  = explode(',', $row[4]);

			if (!empty($countries) 
				&& !in_array(mb_strtolower($country), $countries)
			) {
				continue;
			}

			$this->loadLocation(
				$this->getNormalizer()->normilize(
					$country,
					$regionName = end($region),
					$cityName   = $row[2] .' '. $row[3]
				),

				[
					'CITY_ID'         => $row[0],
					'CITY_CODE'       => mb_substr($row[1], 2),
					'ORIG_NAME'       => $origName = implode(', ', [trim($country), trim($regionName), trim($cityName)]),
					'ORIG_NAME_LOWER' => mb_strtolower($origName),
				]
			);
		}

		return true;
	}

	/**
	 * Обновляет города в которых доступен НПП
	 * 
	 * @param string $position  Стартовая позиция импорта
	 * @param array  $countries Массив стран для обработки
	 * 
	 * @return true|string
	 */
	public function loadCashPay($position = 'RU:0', $countries = ['RU', 'KZ', 'BY', 'UA'])
	{
		$position   = explode(':', $position ?: 'RU:0');
		$index      = 0;
		$started    = false;
		$start_time = time();

		foreach($countries as $countryCode) {
			if ($position[0] != $countryCode && $started === false) {
				continue;
			}

			$started  = true;
			$arCities = $this->getApi()->getService('geography')->getCitiesCashPay($countryCode);

			foreach ($arCities as $arCity) {
				if ($index++ < $position[1]) {
					continue;
				}

				if (Utils::isNeedBreak($start_time)) {
					return sprintf('%s:%s', $countryCode, $index);
				}

				$this->loadLocation(
					$this->getNormalizer()->normilize(
						$country = $arCity['COUNTRY_NAME'],
						$region  = $arCity['REGION_NAME'],
						$city    = $arCity['ABBREVIATION'] .' '. $arCity['CITY_NAME']
					),

					[
						'CITY_ID'         => $arCity['CITY_ID'],
						'CITY_CODE'       => $arCity['CITY_CODE'],
						'IS_CASH_PAY'     => 'Y',
						'ORIG_NAME'       => $origName = implode(', ', [trim($country), trim($region), trim($city)]),
						'ORIG_NAME_LOWER' => mb_strtolower($origName),
					]
				);
			}
		}

		return true;
	}

	/**
	 * Сохраняет город в БД
	 * 
	 * @param array $city
	 * @param array $additFields
	 * 
	 * @return bool
	 */
	protected function loadLocation($city, $additFields = array())
	{
		$fields = array_merge($city, $additFields);

		$exists = $this->getTable()->findFirst([
			'select' => 'ID',
			'where'  => 'CITY_ID = :city_id',
			'bind'   => [
				'city_id' => $additFields['CITY_ID'],
			]
		]);

		if ($exists) {
			$result = $this->getTable()->update($exists['ID'], $fields);
		} else {
			$result = $this->getTable()->add($fields);
		}

		return $result ? ($exists ? $exists['ID'] : $result) : false;
	}
}