<?php
namespace Ipol\DPD\DB\Order;

use \Ipol\DPD\Order as DpdOrder;
use \Ipol\DPD\DB\Model as BaseModel;
use \Ipol\DPD\Shipment;

/**
 * Модель одной записи таблицы заказов
 */
class Model extends BaseModel
{
	const SERVICE_VARIANT_D = 'Д';
	const SERVICE_VARIANT_T = 'Т';
	
	/**
	 * Отправление
	 * @var \Ipol\DPD\Shipment
	 */
	protected $shipment;

	/**
	 * Возвращает список статусов и их описаний
	 * 
	 * @return array
	 */
	public static function StatusList()
	{
		return [
			DpdOrder::STATUS_NEW => 'Новый заказ, еще не отправлялся в DPD',
			DpdOrder::STATUS_PENDING => 'Принят, но нуждается в ручной доработке сотрудником DPD',
			DpdOrder::STATUS_OFFER_CREATE => 'Получена заявка',
			DpdOrder::STATUS_OFFER_ERROR => '​В заявке присутствует ошибка',
			DpdOrder::STATUS_OFFER_WAITING => '​Запрошены паспортные данные получателя',
			DpdOrder::STATUS_OFFER_CANCEL => 'Отмена заявки',
			DpdOrder::STATUS_OK => 'Заказ создан в DPD',
			DpdOrder::STATUS_WAITING => 'Заказ ожидает дату приема',
			DpdOrder::STATUS_DEPARTURE => 'Заказ принят у отправителя',
			DpdOrder::STATUS_TRANSIT => 'Посылка находится в пути',
			DpdOrder::STATUS_TRANSIT_TERMINAL => 'Посылка находится на транзитном терминале',
			DpdOrder::STATUS_ARRIVE_PICKUP => 'Заказ готов к выдаче на пункте',
			DpdOrder::STATUS_ARRIVE_COURIER => '​Заказ готов к передаче курьеру для доставки',
			DpdOrder::STATUS_COURIER => 'Посылка передана курьеру',
			
			DpdOrder::STATUS_TRANSIT_RETURN => 'Заказ следует по маршруту до терминала возврата',
			DpdOrder::STATUS_ARRIVE_PICKUP_RETURN => 'Заказ на возврат готов к выдаче',
			DpdOrder::STATUS_ARRIVE_COURIER_RETURN => '​Заказ на возврат готов к передаче курьеру для доставки',
			DpdOrder::STATUS_COURIER_RETURN => 'Посылка передана курьеру для возврата',

			DpdOrder::STATUS_CUSTOMS_CLEARANCE => 'Таможенное оформление в стране отправления',
			DpdOrder::STATUS_END_CUSTOMS_CLEARANCE => '​Закончено таможенное оформление в стране отправления',
			DpdOrder::STATUS_ARRIVED_IN_RF => 'Заказ прибыл в страну доставки',
			DpdOrder::STATUS_END_CUSTOMS_CLEARANCE_IN_RF => 'Закончено таможенное оформление',
			DpdOrder::STATUS_TRANSIT_SPEC => 'Передано спецперевозчику',

			DpdOrder::STATUS_PROBLEM => 'C посылкой возникла проблемная ситуация',
			DpdOrder::STATUS_DELIVERY_PROBLEM => 'Отказ от заказа в момент доставки',
			DpdOrder::STATUS_NOT_DONE => 'Заказ не доставлен',
			DpdOrder::STATUS_CANCEL => 'Заказ отменен',
			DpdOrder::STATUS_REMOVED => '​Заказ утилизирован',
			DpdOrder::STATUS_NOT_CLAIMED => '​Посылка не востребована',
			DpdOrder::STATUS_LOST => 'Посылка утеряна',
			DpdOrder::STATUS_DELIVERED => 'Посылка доставлена получателю',
			DpdOrder::STATUS_RETURNED => 'Посылка возвращена с доставки',
		];
	}

	/**
	 * Возвращает отправку
	 *
	 * @param bool $forced true - создает новый инстанс на основе полей записи
	 * 
	 * @return \Ipol\DPD\Shipment
	 */
	public function getShipment($forced = false)
	{
		if (is_null($this->shipment) || $forced) {
			$this->shipment = new Shipment($this->getTable()->getConfig());
			$this->shipment->setSender($this->senderLocation);
			$this->shipment->setReceiver($this->receiverLocation);
			$this->shipment->setPaymentMethod($this->personeTypeId, $this->paySystemId);
			$this->shipment->setItems($this->orderItems, $this->sumNpp);

			list($selfPickup, $selfDelivery) = array_values($this->getServiceVariant());
			$this->shipment->setSelfPickup($selfPickup);
			$this->shipment->setSelfDelivery($selfDelivery);

			if ($this->isCreated()) {
				$this->shipment->setWidth($this->dimensionWidth);
				$this->shipment->setHeight($this->dimensionHeight);
				$this->shipment->setLength($this->dimensionLength);
				$this->shipment->setWeight($this->cargoWeight);
			}
		}

		return $this->shipment;
	}

	/**
	 * Ассоциирует внешнюю отправку с записью
	 * Происходит заполнение полей записи на основе данных отправки
	 * 
	 * @param \Ipol\DPD\Shipment $shipment
	 * 
	 * @return self
	 */
	public function setShipment(Shipment $shipment)
	{
		$this->shipment         = $shipment;
		$this->senderLocation   = $shipment->getSender()['ID'];
		$this->receiverLocation = $shipment->getReceiver()['ID'];
		$this->cargoWeight      = $shipment->getWeight();
		$this->cargoVolume      = $shipment->getVolume();
		$this->dimensionWidth   = $shipment->getWidth();
		$this->dimensionWidth   = $shipment->getHeight();
		$this->dimensionLength  = $shipment->getLength();
		$this->personeTypeId    = $shipment->getPaymentMethod()['PERSONE_TYPE_ID'];
		$this->paySystemId      = $shipment->getPaymentMethod()['PAY_SYSTEM_ID'];
		$this->orderItems       = $shipment->getItems();
		$this->price            = $shipment->getPrice();
		$this->serviceVariant   = [
			'SELF_PICKUP'   => $shipment->getSelfPickup(),
			'SELF_DELIVERY' => $shipment->getSelfDelivery(),
		];
		$this->priceDelivery    = $this->getActualPriceDelivery();

		return $this;
	}

	/**
	 * Сеттер св-ва ORDER_ITEMS
	 * 
	 * @param array $items
	 */
	public function setOrderItems($items)
	{
		$this->fields['ORDER_ITEMS'] = is_string($items)
			? $items
			: \serialize($items)
		;

		$this->reloadUnits();

		return $this;
	}

	/**
	 * Геттер св-ва ORDER_ITEMS
	 * 
	 * @return array
	 */
	public function getOrderItems()
	{
		return is_string($this->fields['ORDER_ITEMS'])
			? \unserialize($this->fields['ORDER_ITEMS'])
			: ($this->fields['ORDER_ITEMS'] ?: []);
	}

	/**
	 * Выставляет вариант оплаты доставки
	 *
	 * @return void
	 */
	public function setPaymentType($value)
	{
		$this->fields['PAYMENT_TYPE'] = $value;
		$this->reloadUnits();
	}

	/**
	 * Сеттер св-ва NPP
	 * 
	 * @param float $npp
	 * 
	 * @return self
	 */
	public function setNpp($value)
	{
		$this->fields['NPP'] = $value == 'Y' ? 'Y' : 'N';
		$this->reloadUnits();

		return $this;
	}

	/**
	 * Возвращает сумму наложенного платежа
	 *
	 * @return float
	 */
	public function getSumNpp()
	{
		$ret = 0;

		foreach ($this->unitLoads as $item) {
			$ret += $item['QUANTITY'] * $item['NPP'];
		}

		return $ret;
	}

	/**
	 * Устанавливает сумму наложенного платежа
	 * 
	 * @deprecated use setUnitLoads method
	 */
	public function setSumNpp($sum)
	{
		throw new \Exception('use setUnitLoads method for set npp sum');
	}

	/**
	 * Выставляет флаг ОЦ
	 * 
	 * @return void
	 */
	public function setUseCargoValue($value)
	{
		$this->fields['USE_CARGO_VALUE'] = $value == 'Y' ? 'Y' : 'N';
		$this->reloadUnits();
	}

	/**
	 * Возвращает сумму наложенного платежа
	 *
	 * @return float
	 */
	public function getCargoValue()
	{
		$ret = 0;

		foreach ($this->unitLoads as $item) {
			$ret += $item['QUANTITY'] * $item['CARGO'];
		}

		return $ret;
	}

	/**
	 * Устанавливает сумму ОЦ
	 * 
	 * @deprecated use setUnitLoads method
	 */
	public function setCargoValue($sum)
	{
		throw new \Exception('use setUnitLoads method for set cargo value');
	}

	/**
	 * Устанавливает вложения заказа
	 *
	 * @param array $value
	 * 
	 * @return void
	 */
	public function setUnitLoads($value)
	{	
		if (!is_string($value)) {
			$items      = [];
			$isReplaced = true;

			foreach ((array) $value as $k => $item) {
				$item['QUANTITY'] = (int) $item['QUANTITY'];
				
				if (isset($item['CARGO'])) {
					if ($this->fields['USE_CARGO_VALUE'] != 'Y') {
						$item['CARGO'] = 0;
					} else {
						$item['CARGO'] = round($item['CARGO'], 2);
					}
				}
				
				if (isset($item['NPP'])) {
					if ($this->fields['NPP'] != 'Y') {
						$item['NPP'] = 0;
					} else {
						$item['NPP'] = round($item['NPP'], 2);
					}
				}

				if (!isset($item['ID'])) {
					$isReplaced = false;
				}

				if ($this->paymentType != DpdOrder::PAYMENT_TYPE_OUP 
					|| !isset($item['ID'])
					|| $item['ID'] != 'DELIVERY'
				) {
					$items[$k] = $item;
				}

			}

			if ($isReplaced) {
				$units = $items;
			} else {
				$units = [];

				foreach ($items as $id => $item) {
					if (in_array($id, array_column($this->unitLoads, 'ID'))) {
						foreach ($this->unitLoads as $curItem) {
							if ((string) $curItem['ID'] !== (string) $id) {
								continue;
							}

							$units[] = array_merge($curItem, $item);
						}
					} elseif (isset($item['ID'])) {
						$units[] = $item;
					}
				}
			}

			$value = \serialize($units);
		}

		$this->fields['UNIT_LOADS'] = $value;

		return $this;
	}

	/**
	 * Возвращает вложения заказа
	 *
	 * @return array
	 */
	public function getUnitLoads()
	{
		return \is_string($this->fields['UNIT_LOADS'])
			? \unserialize($this->fields['UNIT_LOADS'])
			: ($this->fields['UNIT_LOADS'] ?: [])
		;
	}

	/**
	 * Перезаполняет вложения на основе состава заказа
	 * 
	 * @return void
	 */
	public function reloadUnits()
	{
		$this->unitLoads = array_merge(array_map(function($item) {
			return [
				'ID'       => isset($item['ID']) ? $item['ID'] : crc32($item['NAME']),
				'NAME'     => $item['NAME'],
				'QUANTITY' => $item['QUANTITY'],
				'CARGO'    => $item['PRICE'],
				'NPP'      => $item['PRICE'],
				'VAT'      => $item['VAT_RATE'],
			];
		}, $this->orderItems), [
			[
				'ID'       => 'DELIVERY',
				'NAME'     => 'Доставка',
				'QUANTITY' => 1,
				'CARGO'    => 0,
				'NPP'      => $this->priceDelivery,
				'VAT'      => '',
			]
		]);
	}

	/**
	 * Устанавливает стоимость доставки
	 *
	 * @param float $value
	 * 
	 * @return self
	 */
	public function setPriceDelivery($value)
	{
		$this->fields['PRICE_DELIVERY'] = $value;
		$this->reloadUnits();

		return $this;
	}

	/**
	 * Устанавливает тариф доставки
	 *
	 * @param $value
	 * 
	 * @return self
	 */
	public function setServiceCode($value)
	{
		$this->fields['SERVICE_CODE'] = $value;

		if (!$this->priceDelivery) {
			$this->priceDelivery = $this->getActualPriceDelivery();
		}

		return $this;
	}

	/**
	 * Устанавливает вариант доставки
	 *
	 * @param string $variant
	 * 
	 * @return self
	 */
	public function setServiceVariant($variant)
	{
		$D = self::SERVICE_VARIANT_D;
		$T = self::SERVICE_VARIANT_T;

		if (is_string($variant) && preg_match('~^('. $D .'|'. $T .'){2}$~sUi', $variant)) {
			$this->fields['SERVICE_VARIANT'] = $variant;
		} else if (is_array($variant)) {
			$selfPickup   = $variant['SELF_PICKUP'];
			$selfDelivery = $variant['SELF_DELIVERY'];
		
			$this->fields['SERVICE_VARIANT'] = ''
				. ($selfPickup   ? $T : $D)
				. ($selfDelivery ? $T : $D)
			;
		}

		return $this;
	}

	/**
	 * Возвращает вариант доставки
	 *
	 * @return array
	 */
	public function getServiceVariant()
	{
		$D = self::SERVICE_VARIANT_D;
		$T = self::SERVICE_VARIANT_T;

		return array(
			'SELF_PICKUP'   => mb_substr($this->fields['SERVICE_VARIANT'], 0, 1) == $T,
			'SELF_DELIVERY' => mb_substr($this->fields['SERVICE_VARIANT'], 1, 1) == $T,
		);
	}

	/**
	 * Возвращает флаг доставка от ТЕРМИНАЛА или ДВЕРИ
	 * 
	 * @return bool
	 */
	public function isSelfPickup()
	{
		$serviceVariant = $this->getServiceVariant();
		return $serviceVariant['SELF_PICKUP'];
	}

	/**
	 * Возвращает флаг доставка до ТЕРМИНАЛА или ДВЕРИ
	 * 
	 * @return bool
	 */
	public function isSelfDelivery()
	{
		$serviceVariant = $this->getServiceVariant();
		return $serviceVariant['SELF_DELIVERY'];
	}

	/**
	 * Возвращает текстовое описание статуса заказа
	 *
	 * @return string
	 */
	public function getOrderStatusText()
	{
		$statusList = static::StatusList();
		$ret = $statusList[$this->orderStatus];

		if ($this->orderStatus == DpdOrder::STATUS_ERROR) {
			$ret .= ': '. $this->orderError;
		}

		return $ret;
	}

	/**
	 * Возвращает ифнормацию о тарифе
	 *
	 * @param  bool $forced пересоздать ли экземпляр отгрузки
	 *
	 * @return array
	 */
	public function getTariffDelivery($forced = false)
	{
		if ($this->serviceCode) {
			return $this->getShipment($forced)->calculator()->calculateWithTariff($this->serviceCode, $this->currency);
		}

		return false;
	}

	/**
	 * Возвращает стоимость доставки в заказе
	 *
	 * @return float
	 */
	public function getActualPriceDelivery()
	{
		$tariff = $this->getTariffDelivery();

		if ($tariff) {
			return $tariff['COST'];
		}

		return false;
	}

	/**
	 * Сеттер для номера заказа, попутно устанавливаем номер отправления
	 *
	 * @param $orderNum
	 * 
	 * @return self
	 */
	public function setOrderNum($orderNum)
	{
		$this->fields['ORDER_NUM']         = $orderNum;
		$this->fields['ORDER_DATE_CREATE'] = $orderNum ? date('Y-m-d H:i:s') : null;

		return $this;
	}

	/**
	 * Сеттер для статуса заказа
	 * 
	 * @param $orderStatus
	 * @param $orderStatusDate
	 * 
	 * @return self
	 */
	public function setOrderStatus($orderStatus, $orderStatusDate = false, $errorMessage = '')
	{
		if (empty($orderStatus)) {
			return;
		}

		if (!array_key_exists($orderStatus, self::StatusList())) {
			return;
		}

		$this->fields['ORDER_STATUS'] = $orderStatus;
		$this->fields['ORDER_DATE_STATUS'] = $orderStatusDate ?: date('Y-m-d H:i:s');
		$this->fields['ORDER_ERROR'] = $errorMessage;

		if ($orderStatus == DpdOrder::STATUS_CANCEL) {
			$this->fields['ORDER_DATE_CANCEL'] = $orderStatusDate ?: date('Y-m-d H:i:s');
		}
	}

	/**
	 * Устанавливает статус заказа по его коду
	 * 
	 * @param int    $eventCode
	 * @param string $eventTime
	 * 
	 * @return self
	 */
	public function setOrderStatusByCode($eventCode, $eventTime, $eventReason = '', $eventParams = [])
	{
		$statuses = [
			1001 => DpdOrder::STATUS_OFFER_CREATE,
			1101 => DpdOrder::STATUS_OFFER_ERROR,
			​1201 => DpdOrder::STATUS_OFFER_WAITING,
			1301 => DpdOrder::STATUS_OFFER_CANCEL,
			1401 => DpdOrder::STATUS_OK,
			1501 => DpdOrder::STATUS_WAITING,
			​1601 => DpdOrder::STATUS_DEPARTURE,
			1701 => DpdOrder::STATUS_ARRIVED_IN_RF,
			1801 => DpdOrder::STATUS_END_CUSTOMS_CLEARANCE_IN_RF,
			1802 => DpdOrder::STATUS_TRANSIT_TERMINAL,
			2101 => DpdOrder::STATUS_TRANSIT,
			2102 => DpdOrder::STATUS_TRANSIT_RETURN,
			​2201 => DpdOrder::STATUS_ARRIVE_PICKUP,
			2202 => DpdOrder::STATUS_ARRIVE_COURIER,
			2203 => DpdOrder::STATUS_ARRIVE_PICKUP_RETURN,
			​2204 => DpdOrder::STATUS_ARRIVE_COURIER_RETURN,
			​2301 => DpdOrder::STATUS_COURIER,
			2303 => DpdOrder::STATUS_TRANSIT_TERMINAL,
			2205 => DpdOrder::STATUS_CUSTOMS_CLEARANCE,
			2302 => DpdOrder::STATUS_END_CUSTOMS_CLEARANCE,
			2304 => DpdOrder::STATUS_COURIER,
			2305 => DpdOrder::STATUS_ARRIVE_COURIER_RETURN,
			2306 => DpdOrder::STATUS_ARRIVE_COURIER,
			2307 => DpdOrder::STATUS_PROBLEM,
			2309 => DpdOrder::STATUS_COURIER_RETURN,
			2310 => DpdOrder::STATUS_TRANSIT_SPEC,
			2401 => DpdOrder::STATUS_PROBLEM,
			2402 => DpdOrder::STATUS_PROBLEM,
			​2404 => DpdOrder::STATUS_DELIVERY_PROBLEM,
			2405 => DpdOrder::STATUS_DELIVERY_PROBLEM,
			2406 => DpdOrder::STATUS_DELIVERY_PROBLEM,
			2407 => DpdOrder::STATUS_PROBLEM,
			2408 => DpdOrder::STATUS_PROBLEM,
			2409 => DpdOrder::STATUS_PROBLEM,
			2410 => DpdOrder::STATUS_PROBLEM,
			​​3701 => DpdOrder::STATUS_PROBLEM,
			​2501 => DpdOrder::STATUS_NOT_DONE,
			2901 => DpdOrder::STATUS_CANCEL,
			​3301 => DpdOrder::STATUS_REMOVED,
			​3302 => DpdOrder::STATUS_NOT_CLAIMED,
			​3303 => DpdOrder::STATUS_LOST,
			3304 => DpdOrder::STATUS_DELIVERED,
			​3305 => DpdOrder::STATUS_DELIVERED,
			​3306 => DpdOrder::STATUS_RETURNED,
		];

		if (!array_key_exists($eventCode, $statuses)) {
			return false;
		}

		$status = $statuses[$eventCode];
		$message = isset($eventParams['ERROR_MESSAGE'])
			? $eventParams['ERROR_MESSAGE']
			: $eventReason
		;

		return $this->setOrderStatus($status, $eventTime, $message);
	}

	/**
	 * Возвращает флаг новый заказ
	 * 
	 * @return bool
	 */
	public function isNew()
	{
		return $this->fields['ORDER_STATUS'] == DpdOrder::STATUS_NEW;
	}

	/**
	 * Проверяет отправлялся ли заказ в DPD
	 *
	 * @return bool
	 */
	public function isCreated()
	{
		return $this->fields['ORDER_STATUS'] != DpdOrder::STATUS_NEW
			&& $this->fields['ORDER_STATUS'] != DpdOrder::STATUS_CANCEL
			&& $this->fields['ORDER_STATUS'] != DpdOrder::STATUS_OFFER_CANCEL
		;
	}

	/**
	 * Проверяет отправлялся ли заказ в DPD и был ли он там успешно создан
	 *
	 * @return bool
	 */
	public function isDpdCreated()
	{
		return $this->isCreated() && !empty($this->fields['ORDER_NUM']);
	}

	/**
	 * Возвращает инстанс для работы с внешним заказом
	 *
	 * @return \Ipol\DPD\Order;
	 */
	public function dpd()
	{
		return new DpdOrder($this);
	}
}