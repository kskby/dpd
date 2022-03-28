<?php
namespace Ipol\DPD\API\Service;

use \Ipol\DPD\API\User\UserInterface;
use \Ipol\DPD\API\Client\Factory as ClientFactory;

/**
 * Служба для работы с наклейками
 */
class LabelPrint implements ServiceInterface
{
	protected $wdsl = 'https://ws.dpd.ru/services/label-print?wsdl';

	/**
     * Конструктор класса
     * 
     * @param \Ipol\DPD\API\User\UserInterface
     */
	public function __construct(UserInterface $user)
	{
		$this->client = ClientFactory::create($this->wdsl, $user);
		$this->client->setCacheTime(0);
	}

	/**
	 * Формирует файл с наклейками DPD
	 * 
	 * @param integer $orderNum      номер заказа
	 * @param integer $parcelsNumber кол-во посылок (наклеек)
	 * @param string  $fileFormat    формат итогового файла, по умолчанию PDF
	 * @param string  $pageSize      размер страницы, по умолчанию A5
	 * 
	 * @return mixed
	 */
	public function createLabelFile($orderNum, $parcelsNumber = 1, $fileFormat = 'PDF', $pageSize = 'A5')
	{
		return $this->client->invoke('createLabelFile', array(
			'fileFormat' => $fileFormat,
			'pageSize'   => $pageSize,
			'order'      => array(
				'orderNum'      => $orderNum,
				'parcelsNumber' => $parcelsNumber
			),
		), 'getLabelFile');
	}
}