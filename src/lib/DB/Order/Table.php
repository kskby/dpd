<?php
namespace Ipol\DPD\DB\Order;

use Ipol\DPD\DB\AbstractTable;

/**
 * Класс для работы с таблицей заказов
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
		return 'b_ipol_dpd_order';
	}

	/**
	 * Возвращает название класса модели
	 * 
	 * @return string
	 */
	public function getModelClass()
	{
		return \Ipol\DPD\DB\Order\Model::class;
	}
	
	/**
	 * Возвращает список полей и их значения по умолчанию
	 * 
	 * @return array
	 */
	public function getFields()
    {
		return [
			'ID'                     => null,
			'ORDER_ID'               => null,
			'SHIPMENT_ID'            => null,
			'ORDER_DATE'             => null,
			'ORDER_DATE_CREATE'      => null,
			'ORDER_DATE_CANCEL'      => null,
			'ORDER_DATE_STATUS'      => null,
			'ORDER_NUM'              => null,
			'ORDER_STATUS'           => 'NEW',
			'ORDER_STATUS_CANCEL'    => null,
			'ORDER_ERROR'            => null,
			'SERVICE_CODE'           => null,
			'SERVICE_VARIANT'        => null,
			'PICKUP_DATE'            => null,
			'PICKUP_TIME_PERIOD'     => null,
			'DELIVERY_TIME_PERIOD'   => null,
			'CARGO_WEIGHT'           => 0,
			'DIMENSION_WIDTH'        => 0,
			'DIMENSION_HEIGHT'       => 0,
			'DIMENSION_LENGTH'       => 0,
			'CARGO_VOLUME'           => 0,
			'CARGO_NUM_PACK'         => 1,
			'CARGO_CATEGORY'         => 'Текстиль',
			'SENDER_FIO'             => null,
			'SENDER_NAME'            => null,
			'SENDER_PHONE'           => null,
			'SENDER_LOCATION'        => null,
			'SENDER_STREET'          => null,
			'SENDER_STREETABBR'      => null,
			'SENDER_HOUSE'           => null,
			'SENDER_KORPUS'          => null,
			'SENDER_STR'             => null,
			'SENDER_VLAD'            => null,
			'SENDER_OFFICE'          => null,
			'SENDER_FLAT'            => null,
			'SENDER_TERMINAL_CODE'   => null,
			'RECEIVER_FIO'           => null,
			'RECEIVER_NAME'          => null,
			'RECEIVER_PHONE'         => null,
			'RECEIVER_LOCATION'      => null,
			'RECEIVER_STREET'        => null,
			'RECEIVER_STREETABBR'    => null,
			'RECEIVER_HOUSE'         => null,
			'RECEIVER_KORPUS'        => null,
			'RECEIVER_STR'           => null,
			'RECEIVER_VLAD'          => null,
			'RECEIVER_OFFICE'        => null,
			'RECEIVER_FLAT'          => null,
			'RECEIVER_TERMINAL_CODE' => null,
			'RECEIVER_COMMENT'       => null,
			'PRICE'                  => null,
			'PRICE_DELIVERY'         => null,
			'CARGO_VALUE'            => null,
			'NPP'                    => 'N',
			'SUM_NPP'                => null,
			'CARGO_REGISTERED'       => 'N',
			'SMS'                    => null,
			'EML'                    => null,
			'ESD'                    => null,
			'ESZ'                    => null,
			'OGD'                    => null,
			'DVD'                    => 'N',
			'VDO'                    => 'N',
			'POD'                    => null,
			'PRD'                    => 'N',
			'TRM'                    => 'N',
			'LABEL_FILE'             => null,
			'INVOICE_FILE'           => null,
			'CURRENCY'               => null,
			'PERSONE_TYPE_ID'        => null,
			'PAY_SYSTEM_ID'          => null,
			'ORDER_ITEMS'            => null,
			'PAY_SYSTEM_ID'          => null,
			'PERSONE_TYPE_ID'        => null,
			'PAYMENT_TYPE'           => null,
			'SENDER_EMAIL'           => '',
			'RECEIVER_EMAIL'         => '',
			'SENDER_NEED_PASS'       => 'N',
			'RECEIVER_NEED_PASS'     => 'N',
		];
    }

	/**
	 * Возвращает модель по ID
	 * 
	 * @param integer $orderId
	 * @param bool    $autoCreate создавать пустую модель, если не найдена
	 * 
	 * @return \Ipol\DPD\DB\Order\Model
	 */
	public function getByOrderId($orderId, $autoCreate = false)
	{
		$item = $this->findFirst([
			'where' => 'ORDER_ID = :order_id',
			'bind'  => [
				':order_id' => $orderId
			],
		]);
		
		if ($item) {
			return $this->makeModel($item);
		} elseif (!$autoCreate) {
			return false;
		}
		
		return $this->makeModel();
	}
}