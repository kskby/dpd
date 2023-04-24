<?php
namespace Ipol\DPD\DB\Terminal;

use Ipol\DPD\DB\Connection;
use Ipol\DPD\DB\Model as BaseModel;
use Ipol\DPD\Shipment;

/**
 * Модель одной записи таблицы терминалов
 */
class Model extends BaseModel implements \JsonSerializable
{
	/**
	 * Проверяет может ли терминал принять посылку
	 * 
	 * @param  \Ipol\DPD\Shipment $shipment
	 * @param  bool               $checkLocation
	 * 
	 * @return bool
	 */
	public function checkShipment(Shipment $shipment, $checkLocation = true)
	{
		if ($checkLocation 
			&& !$this->checkLocation($shipment->getReceiver())
		) {
			return false;
		}

		if ($shipment->isPaymentOnDelivery()
			&& !$this->checkShipmentPayment($shipment)
		) {
			return false;
		}

		if (!$this->checkShipmentDimessions($shipment)) {
			return false;
		}

		return true;
	}

	/**
	 * Сверяет местоположение терминала и переданного местоположения
	 * 
	 * @param  array  $location
	 * 
	 * @return bool
	 */
	public function checkLocation(array $location)
	{
		return $this->fields['LOCATION_ID'] == $location['CITY_ID'];
	}

	/**
	 * Проверяет возможность принять НПП на терминале
	 * 
	 * @param \Ipol\DPD\Shipment $shipment
	 * 
	 * @return bool
	 */
	public function checkShipmentPayment(Shipment $shipment)
	{
		if ($this->fields['NPP_AVAILABLE'] != 'Y')  {
			return false;
		}

		$converter = $shipment->getCurrencyConverter();
		if (!$converter) return false;

		$config   = $shipment->getConfig();
		$location = Connection::getInstance($config)->getTable('location')->findFirst([
			'where' => 'CITY_ID = :city_id',
			'bind'  => ['city_id' => $this->fields['LOCATION_ID']]
		]);

		$currencyFrom  = $shipment->getConfig()->get('CURRENCY', '', $location['COUNTRY_CODE']);
		$currencyTo    = $shipment->getCurrency();
	
		$terminalPrice = $converter->convert($this->fields['NPP_AMOUNT'], $currencyFrom, $currencyTo);
		$shipmentPrice = $shipment->getPrice();

		return $terminalPrice >= $shipmentPrice;
	}

	/**
	 * Проверяет габариты посылки на возможность ее принятия на терминале
	 * 
	 * @param \Ipol\DPD\Shipment $shipment
	 * 
	 * @return bool
	 */
	public function checkShipmentDimessions(Shipment $shipment)
	{
		if ($this->fields['IS_LIMITED'] != 'Y') {
			return true;
		}

		if ($this->fields['LIMIT_MAX_WEIGHT'] > 0 && $shipment->getWeight() > $this->fields['LIMIT_MAX_WEIGHT']) {
			return false;
		}

		if ($this->fields['LIMIT_MAX_VOLUME'] > 0 && $shipment->getVolume() > $this->fields['LIMIT_MAX_VOLUME']) {
			return false;
		}

		$dimensions    = [$shipment->getWidth(), $shipment->getHeight(), $shipment->getLength()];
		$maxDimensions = [$this->fields['LIMIT_MAX_WIDTH'], $this->fields['LIMIT_MAX_HEIGHT'], $this->fields['LIMIT_MAX_LENGTH']];

		if ($this->fields['LIMIT_SUM_DIMENSION'] > 0 && array_sum($dimensions) > $this->fields['LIMIT_SUM_DIMENSION']) {
			return false;
		}

		sort($dimensions);
		sort($maxDimensions);

		foreach(array_keys($dimensions) as $k) {
			if ($dimensions[$k] > $maxDimensions[$k]) {
				return false;
			}
		}

		return true;
	}

	public function setSchedulePayments($value)
	{
		$this->fields['SCHEDULE_PAYMENTS'] = serialize($value);

		return $this;
	}

	public function getSchedulePayments($value)
	{
		return unserialize($this->fields['SCHEDULE_PAYMENTS'] ?: 'a:0:{}') ?: [];
	}

	public function jsonSerialize()
	{
		return [
			'ID'                             => $this->fields['ID'],
			'CODE'                           => $this->fields['CODE'],
			'NAME'                           => $this->fields['NAME'],
			'TYPE'                           => $this->fields['PARCEL_SHOP_TYPE'],
			'LAT'                            => $this->fields['LATITUDE'],
			'LON'                            => $this->fields['LONGITUDE'],
			'ADDRESS_FULL'                   => $this->fields['ADDRESS_FULL'],
			'SCHEDULE_SELF_PICKUP'           => $this->fields['SCHEDULE_SELF_PICKUP'] ? preg_split('!<br>!', $this->fields['SCHEDULE_SELF_PICKUP']) : [],
			'SCHEDULE_SELF_DELIVERY'         => $this->fields['SCHEDULE_SELF_DELIVERY'] ? preg_split('!<br>!', $this->fields['SCHEDULE_SELF_DELIVERY']) : [],
			'SCHEDULE_PAYMENT_CASH'          => $this->fields['SCHEDULE_PAYMENT_CASH'] ? preg_split('!<br>!', $this->fields['SCHEDULE_PAYMENT_CASH']) : [],
			'SCHEDULE_PAYMENT_CASHLESS'      => $this->fields['SCHEDULE_PAYMENT_CASHLESS'] ? preg_split('!<br>!', $this->fields['SCHEDULE_PAYMENT_CASHLESS']) : [],
			'ADDRESS_DESCR'                  => $this->fields['ADDRESS_DESCR'],
			'NPP'                            => [
				'AVAILABLE' => $this->fields['NPP_AVAILABLE'] == 'Y',
				'AMOUNT'    => $this->fields['NPP_AMOUNT'],
			],
		];
	}
}