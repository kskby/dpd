<?php
namespace Ipol\DPD;

use \Ipol\DPD\API\User\User as API;
use \Ipol\DPD\Config\ConfigInterface;

/**
 * Класс содержит набор готовых методов реализующих выполнение периодических
 * заданий
 */
class Agents
{
	/**
	 * Обновляет статусы заказов
	 * 
	 * Обновление статусов происходит в 2 этапа.
	 * На первом этапе обрабатываются заказы, которые создались в статусе "Ожидают проверки менеджером DPD"
	 * На втором этапе обрабатываются остальные заказы. Для получения изменений по статусам используется 
	 * метод getStatesByClient
	 * 
	 * @param \Ipol\DPD\Config\ConfigInterface $config
	 * 
	 * @return void
	 */
	public static function checkOrderStatus(ConfigInterface $config)
	{
		self::checkPindingOrderStatus($config);
		self::checkTrakingOrderStatus($config);
	}

	/**
	 * Проверяет статусы заказов ожидающих проверки
	 * 
	 * @return void
	 */
	protected static function checkPindingOrderStatus(ConfigInterface $config)
	{
		$table  = \Ipol\DPD\DB\Connection::getInstance($config)->getTable('order');
		$orders = $table->find([
			'where' => 'ORDER_STATUS = :order_status',
			'order' => 'ORDER_DATE_STATUS ASC, ORDER_DATE_CREATE ASC',
			'limit' => '0,2',
			'bind'  => [
				':order_status' => \Ipol\DPD\Order::STATUS_PENDING
			]
		])->fetchAll(\PDO::FETCH_CLASS|\PDO::FETCH_PROPS_LATE, $table->getModelClass(), [$table]);
		
		foreach ($orders as $order) {
			$order->dpd()->checkStatus();
		}
	}

	/**
	 * Проверяет статусы заказов прошедшие проверку
	 * 
	 * @return void
	 */
	protected static function checkTrakingOrderStatus(ConfigInterface $config)
	{
		if (!$config->get('STATUS_ORDER_CHECK')) {
			return;
		}

		do {
			$service = API::getInstanceByConfig($config)->getService('event-tracking');
			$ret = $service->getEvents();
			
			if (!$ret) {
				return;
			}

			$states = isset($ret['EVENT']) ? $ret['EVENT'] : [];
			$states = array_key_exists('DPD_ORDER_NR', $states) ? array($states) : $states;
			$states = array_filter($states, function($item) {
				return isset($item['CLIENT_ORDER_NR']);
			});

			// сортируем статусы по их времени наступления
			uasort($states, function($a, $b) {
				if ($a['CLIENT_ORDER_NR'] == $b['CLIENT_ORDER_NR']) {
					$time1 = strtotime($a['EVENT_DATE']);
					$time2 = strtotime($b['EVENT_DATE']);

					return $time1 - $time2;
				}

				return strcmp($a['CLIENT_ORDER_NR'], $b['CLIENT_ORDER_NR']);
			});


			foreach ($states as $state) {
				$order = \Ipol\DPD\DB\Connection::getInstance($config)->getTable('order')->getByOrderId($state['CLIENT_ORDER_NR']);
				
				if (!$order) {
					continue;
				}

				$status     = $state['EVENT_CODE'] ?: 'TYPE_CODE';
				$statusTime = date('Y-m-d H:i:s', strtotime($state['EVENT_DATE']));
				$number     = $state['DPD_ORDER_NR'];
				$message    = false;
				
				switch($status)
				{
					case 'OfferCreate':
					case 'OfferUpdating':
					case 'OfferWaiting':
						continue 2;

					case 'OfferCancelled':
					case 'OrderCancelled':
						$status = Order::STATUS_CANCEL;
					break;

					case 'OrderCreate':
					case 'OrderWaiting':
						$status = Order::STATUS_OK;
					break;

					case 'OrderPickup':
						$status = Order::STATUS_DEPARTURE;
					break;

					case 'OrderArrivedInRF':
					case 'OrderOnTerminal':
					case 'OrderOnRoad':
						$status = Order::STATUS_TRANSIT;
					break;

					case 'OrderReady':
						$status = $order->isSelfDelivery() ? Order::STATUS_ARRIVE : Order::STATUS_TRANSIT_TERMINAL;
					break;

					case 'OrderDelivering':
						$status = 'STATUS_COURIER';
					break;

					case 'OrderProblem':
					case 'OrderDeliveryProblem':
					case 'OrderProblem':
					case 'OrderDeliveryProblem':
						$status = Order::STATUS_PROBLEM;
					break;

					case 'OrderDied':
						$status = Order::STATUS_NOT_DONE;
					break;

					case 'OrderWorkCompleted':
						$status = Order::STATUS_DELIVERED;
					break;

					default:
						continue 2;
					break;
				}

				$params = isset($state['PARAMETER']['PARAM_NAME'])
					? [$state['PARAMETER']]
					: $state['PARAMETER']
				;

				foreach ($params as $param) {
					if ($param['PARAM_NAME'] == 'ORDER_NUMBER') {
						$number = $param['VALUE'];
					}
				}

				$order->setOrderStatus($status, $statusTime);
				$order->orderNum = $number ?: $order->orderNum;
				$order->save();
			}

			if ($ret['DOC_ID'] > 0) {
				$service->confirm($ret['DOC_ID']);
			}
		} while($ret['RESULT_COMPLETE'] != 1);
	}

	/**
	 * Загружает в локальную БД данные о местоположениях и терминалах
	 * 
	 * @param \Ipol\DPD\Config\ConfigInterface $config
	 * 
	 * @return string
	 */
	public static function loadExternalData(ConfigInterface $config)
	{
		$api = API::getInstanceByConfig($config);

		$locationTable  = \Ipol\DPD\DB\Connection::getInstance($config)->getTable('location');
		$terminalTable  = \Ipol\DPD\DB\Connection::getInstance($config)->getTable('terminal');

		$locationLoader = new \Ipol\DPD\DB\Location\Agent($api, $locationTable);
		$terminalLoader = new \Ipol\DPD\DB\Terminal\Agent($api, $terminalTable);

		$currStep = $config->get('LOAD_EXTERNAL_DATA_STEP');
		$position = $config->get('LOAD_EXTERNAL_DATA_POSITION');

		switch ($currStep) {
			case 'LOAD_LOCATION_ALL':
				$ret      = $locationLoader->loadAll($position);
				$currStep = 'LOAD_LOCATION_ALL';
				$nextStep = 'LOAD_LOCATION_CASH_PAY';

				if ($ret !== true) {
					break;
				}

			case 'LOAD_LOCATION_CASH_PAY':
				$ret      = $locationLoader->loadCashPay($position);
				$currStep = 'LOAD_LOCATION_CASH_PAY';
				$nextStep = 'LOAD_TERMINAL_UNLIMITED';

				if ($ret !== true) {
					break;
				}

			case 'LOAD_TERMINAL_UNLIMITED':
				$ret      = $terminalLoader->loadUnlimited($position);
				$currStep = 'LOAD_TERMINAL_UNLIMITED';
				$nextStep = 'LOAD_TERMINAL_LIMITED';

				if ($ret !== true) {
					break;
				}

			case 'LOAD_TERMINAL_LIMITED':
				$ret      = $terminalLoader->loadLimited($position);
				$currStep = 'LOAD_TERMINAL_LIMITED';
				$nextStep = 'LOAD_FINISH';

				if ($ret !== true) {
					break;
				}
			
			default:
				$ret      = true;
				$currStep = 'LOAD_FINISH';
				$nextStep = 'LOAD_LOCATION_ALL';
			break;
		}

		$nextStep = is_bool($ret) ? $nextStep : $currStep;
		$position = is_bool($ret) ? ''        : $ret;

		$config->set('LOAD_EXTERNAL_DATA_STEP', $nextStep);
		$config->set('LOAD_EXTERNAL_DATA_POSITION', $position);
	}
}