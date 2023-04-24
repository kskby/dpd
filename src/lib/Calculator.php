<?php
namespace Ipol\DPD;

use \Ipol\DPD\API\User\User as API;
use \Ipol\DPD\API\User\UserInterface;
use \Ipol\DPD\Currency\ConverterInterface;

/**
 * Класс калькулятор стоимости доставки
 */
class Calculator
{
	protected static $lastResult = false;

	protected $api;

	protected $shipment;

	protected $currencyConverter;

	/**
	 * Возвращает список поддерживаемых тарифов
	 *
	 * @return array
	 */
	public static function TariffList()
	{
		return array(
			"PCL" => "DPD OPTIMUM",
			"CUR" => "DPD CLASSIC",
			"CSM" => "DPD Online Express",
			"ECN" => "DPD ECONOMY",
			"ECU" => "DPD ECONOMY CU",
			"NDY" => "DPD EXPRESS",
			// "TEN" => "DPD 10:00",
			// "DPT" => "DPD 13:00",
			"BZP" => "DPD 18:00",
			"MXO" => "MXO DPD STANDARD",
			"MAX" => "DPD MAX domestic",
			"PUP" => "DPD SHOP",
			"DAY" => "DPD Same Day",
		);
	}

	/**
	 * Возвращает список тарифов которые будут использованы в расчете
	 *
	 * @return array
	 */
	public function AllowedTariffList()
	{
		$disableTariffs = (array) $this->getConfig()->get('TARIFF_OFF');
		$tariffs = array_diff_key(static::TariffList(), array_flip($disableTariffs));

		if (($shipment = $this->getShipment()) && $shipment->isPaymentOnDelivery()) {
			$locationTo = $shipment->getReceiver();
			$locationFrom = $shipment->getSender();

			if ($locationTo['COUNTRY_CODE'] == 'BY') {
				$tariffs = array_intersect_key($tariffs, ['CSM' => true]);
			} elseif ($locationFrom['COUNTRY_CODE'] == 'BY') {
				$tariffs = array_intersect_key($tariffs, ['PCL' => true, 'ECN' => true]);
			}
		}

		return $tariffs;
	}

	/**
	 * Возвращает последний расчет
	 * 
	 * @return array
	 */
	public static function getLastResult()
	{
		return static::$lastResult;
	}

	/**
	 * Конструктор
	 * 
	 * @param \Ipol\DPD\Shipment               $shipment  отправление
	 * @param \Ipol\DPD\API\User\UserInterface $api       инстанс API который будет использован в расчете,
	 *                                                    по умолчанию будет взят из конфига
	 */
	public function __construct(Shipment $shipment, UserInterface $api = null)
	{
		$this->shipment                  = $shipment;
		$this->api                       = $api ?: API::getInstanceByConfig($this->getConfig());
		$this->defaultTariffCode         = $this->getConfig()->get('DEFAULT_TARIFF_CODE');
		$this->minCostWhichUsedDefTariff = $this->getConfig()->get('DEFAULT_TARIFF_THRESHOLD', 0);

		if ($converter = $shipment->getCurrencyConverter()) {
			$this->setCurrencyConverter($converter);
		}
	}

	/**
	 * Возвращает конфиг
	 */
	public function getConfig()
	{
		return $this->getShipment()->getConfig();
	}

	/**
	 * Устанавливает конвертер валюты
	 * 
	 * @param \Ipol\DPD\Currency\ConverterInterface $converter
	 * 
	 * @return self
	 */
	public function setCurrencyConverter(ConverterInterface $converter)
	{
		$this->currencyConverter = $converter;

		return $this;
	}

	/**
	 * Возвращает конвертер валюты
	 * 
	 * @return \Ipol\DPD\Currency\ConverterInterface $converter
	 */
	public function getCurrencyConverter()
	{
		return $this->currencyConverter;
	}


	/**
	 * Устанавливает посылку для расчета стоимости
	 * 
	 * @param \Ipol\DPD\Shipment $shipment
	 * 
	 * @return self
	 */
	public function setShipment(Shipment $shipment)
	{
		$this->shipment = $shipment;

		return $this;
	}

	/**
	 * Возвращает посыдку для расчета стоимости
	 * 
	 * @return \Ipol\DPD\Shipment $shipment
	 */
	public function getShipment()
	{
		return $this->shipment;
	}

	/**
	 * Устанавливает тариф и порог мин. стоимости доставки
	 * при не достижении которого будет использован переданный тариф
	 * 
	 * @param string  $tariffCode
	 * @param float   $minCostWhichUsedTariff
	 * 
	 * @return self
	 */
	public function setDefaultTariff($tariffCode, $minCostWhichUsedTariff = 0)
	{
		$this->defaultTariffCode = $tariffCode;
		$this->minCostWhichUsedDefTariff = $minCostWhichUsedTariff;

		return $this;
	}

	/**
	 * Возвращает тариф по умолчанию
	 * 
	 * @return string
	 */
	public function getDefaultTariff()
	{
		return $this->defaultTariffCode;
	}

	/**
	 * Возвращает порог стоимости доставки при недостижении которого
	 * будет использован тариф по умолчанию
	 * 
	 * @return float
	 */
	public function getMinCostWhichUsedDefTariff()
	{
		return $this->minCostWhichUsedDefTariff;
	}

	/**
	 * Расчитывает стоимость доставки.
	 * 
	 * Возвращает оптимальный тариф доставки (минимальный по цене для клиента)
	 * 
	 * При передачи параметра $currency и установки конвертера, стоимость будет
	 * автоматически сконвертирована в переданную валюту
	 * 
	 * @see setCurrencyConverter
	 * 
	 * @param string $currency валюта
	 * 
	 * @return array
	 */
	public function calculate($currency = false)
	{
		if (!$this->getShipment()->isPossibileDelivery()) {
			return false;
		}

		$calcByParcel = $this->getConfig()->get('CALCULATE_BY_PARCEL') == 'Y';

		$parms = $this->getServiceParmsArray($calcByParcel);
		$tariffs = $this->getListFromService($parms, $calcByParcel);

		if (empty($tariffs)) {
			return false;
		}

		$tariff = $this->getActualTariff($tariffs);
		$tariff = $this->adjustTariffWithCommission($tariff);
		$tariff = $this->adjustTariffWithMarkup($tariff);
		$tariff = $this->convertCurrency($tariff, $currency);

		return self::$lastResult = $tariff;
	}

	public function calculateAll($currency = false)
	{
		if (!$this->getShipment()->isPossibileDelivery()) {
			return false;
		}

		$calcByParcel = $this->getConfig()->get('CALCULATE_BY_PARCEL') == 'Y';

		$parms = $this->getServiceParmsArray($calcByParcel);
		$tariffs = $this->getListFromService($parms, $calcByParcel);

		if (empty($tariffs)) {
			return false;
		}

		foreach ($tariffs as $k => $tariff) {
			$tariff = $this->adjustTariffWithCommission($tariff);
			$tariff = $this->adjustTariffWithMarkup($tariff);
			$tariff = $this->convertCurrency($tariff, $currency);

			$tariffs[$k] = $tariff;
		}

		return $tariffs;
	}

	/**
	 * Расчитывает стоимость доставки с помощью конкретного тарифа
	 * 
	 * Возвращает стоимость доставки c помощью указанного тарифа
	 * 
	 * @param  string $tariffCode код тарифа
	 * @param  string $currency   валюта
	 * 
	 * @return array
	 */
	public function calculateWithTariff($tariffCode, $currency = false)
	{
		if (!$this->getShipment()->isPossibileDelivery()) {
			return false;
		}

		$parms = $this->getServiceParmsArray();
		$tariffs = $this->getListFromService($parms);

		if (empty($tariffs)) {
			return false;
		}

		foreach($tariffs as $tariff) {
			if ($tariff['SERVICE_CODE'] == $tariffCode) {
				$tariff = $this->adjustTariffWithCommission($tariff);
				$tariff = $this->adjustTariffWithMarkup($tariff);
				$tariff = $this->convertCurrency($tariff, $currency);

				return self::$lastResult = $tariff;
			}
		}

		return false;
	}

	/**
	 * Корректирует стоимость тарифа с учетом комиссии на наложенный платеж 
	 * 
	 * @param  array $tariff
	 * @param  int   $personTypeId
	 * @param  int   $paySystemId
	 * 
	 * @return array
	 */
	public function adjustTariffWithCommission($tariff)
	{
		$defaultPrice  = $this->getConfig()->get('DEFAULT_PRICE');

		if (is_numeric($defaultPrice)) {
			$tariff['COST'] = $defaultPrice;
		} else {
			$defaultPrice = explode('|', $defaultPrice);

			if (!empty($defaultPrice[0]) && is_numeric($defaultPrice[0]) && !$this->getShipment()->getSelfDelivery()) {	
				$tariff['COST'] = $defaultPrice[0];
			} elseif (!empty($defaultPrice[1]) && is_numeric($defaultPrice[1]) && $this->getShipment()->getSelfDelivery()) {
				$tariff['COST'] = $defaultPrice[1];
			}
		}

		if (!$this->getShipment()->isPaymentOnDelivery()) {
			return $tariff;
		}

		$payment = $this->getShipment()->getPaymentMethod();

		$useCommission     = $this->getConfig()->get('COMMISSION_NPP_CHECK',   false, $payment['PERSONE_TYPE_ID']);
		$commissionPercent = $this->getConfig()->get('COMMISSION_NPP_PERCENT', 2,     $payment['PERSONE_TYPE_ID']);
		$minCommission     = $this->getConfig()->get('COMMISSION_NPP_MINSUM',  0,     $payment['PERSONE_TYPE_ID']);

		if (!$useCommission) {
			return $tariff;
		}

		$sum = ($this->getShipment()->getPrice() * $commissionPercent / 100);
		$tariff['COST'] += $sum < $minCommission ? $minCommission : $sum;

		return $tariff;
	}

	/**
	 * Добавляет наценку на тариф
	 *
	 * @param array $tariff
	 * 
	 * @return array
	 */
	public function adjustTariffWithMarkup($tariff)
	{
		$markup = $this->getConfig()->get('MARKUP', ['VALUE' => 0, 'TYPE' => 'FIXED']);

		if (empty($markup)
			|| empty($markup['VALUE'])
			|| empty($markup['TYPE'])
			|| !in_array($markup['TYPE'], ['FIXED', 'PERCENT'])
		) {
			return $tariff;
		}

		if ($markup['TYPE'] == 'FIXED') {
			$sum = $markup['VALUE'];
		} else {
			$sum = $tariff['COST'] * $markup['VALUE'] / 100;
		}

		$tariff['COST'] = $tariff['COST'] + $sum;

		return $tariff;
	}

	/**
	 * Возвращает параметры для передачи в API
	 * 
	 * @return array
	 */
	public function getServiceParmsArray($calcByParcel = false)
	{
		$ret = [
			'PICKUP'         => $this->getShipment()->getSender(),
			'DELIVERY'       => $this->getShipment()->getReceiver(),
			'SELF_PICKUP'    => $this->getShipment()->getSelfPickup()   ? 1 : 0,
			'SELF_DELIVERY'  => $this->getShipment()->getSelfDelivery() ? 1 : 0,
			'DECLARED_VALUE' => $this->getShipment()->getDeclaredValue() ? round($this->shipment->getPrice(), 2) : 0,
		];

		if ($calcByParcel) {
			$ret['PARCEL'] = [];

			foreach ($this->getShipment()->getItems() as $item) {
				$ret['PARCEL'][] = [
					'WEIGHT'   => $item['WEIGHT'] / 1000,
					'WIDTH'    => $item['DIMENSIONS']['WIDTH']  / 10,
					'HEIGHT'   => $item['DIMENSIONS']['HEIGHT'] / 10,
					'LENGTH'   => $item['DIMENSIONS']['LENGTH'] / 10,
					'QUANTITY' => $item['QUANTITY'],
				];
			}
		} else {
			$ret['WEIGHT'] = $this->getShipment()->getWeight();
			$ret['VOLUME'] = $this->getShipment()->getVolume();
		}

		return $ret;
	}

	/**
	 * Выполняет расчет через API
	 * 
	 * Возвращает список тарифов и их стоимость с учетом используемых тарифов
	 *  
	 * @param  array $parms массив параметров для расчета
	 * 
	 * @return array
	 */
	public function getListFromService($parms, $calcByParcel = false)
	{
		if (isset($parms['VOLUME']) && $parms['VOLUME'] <= 0) {
			unset($parms['VOLUME']);
		}

		if ($calcByParcel) {
			$tariffs = $this->api->getService('calculator')->getServiceCostByParcels($parms);
		} else {
			$tariffs = $this->api->getService('calculator')->getServiceCost($parms);
		}

		if (!$tariffs) {
			return [];
		}

		return array_intersect_key($tariffs, $this->AllowedTariffList());
	}

	/**
	 * Ищет оптимальный тариф среди списка
	 * 
	 * @param  array $tariffs
	 * 
	 * @return array
	 */
	protected function getActualTariff(array $tariffs)
	{
		$defaultTariff = false;
		$actualTariff  = reset($tariffs);

		foreach($tariffs as $tariff) {
			if ($tariff['SERVICE_CODE'] == $this->getDefaultTariff()) {
				$defaultTariff = $tariff;
			}

			if ($tariff['COST'] < $actualTariff['COST']) {
				$actualTariff = $tariff;
			}
		}

		if ($defaultTariff
			&& $actualTariff['COST'] < $this->getMinCostWhichUsedDefTariff()
		) {
			return $defaultTariff;
		}

		return $actualTariff;
	}

	/**
	 * Конвертирует стоимость доставки в указанную валюту
	 * 
	 * @param array  $tariff
	 * @param string $currencyTo
	 * 
	 * @return array
	 */
	protected function convertCurrency($tariff, $currencyTo)
	{
		$converter = $this->getCurrencyConverter();
		
		if ($converter) {
			$currencyFrom = $this->api->getClientCurrency();
			$currencyTo   = $currencyTo ?: $currencyFrom;

			$tariff['COST']     = $converter->convert($tariff['COST'], $currencyFrom, $currencyTo);
			$tariff['CURRENCY'] = $currencyTo;
		}

		return $tariff;
	}
}