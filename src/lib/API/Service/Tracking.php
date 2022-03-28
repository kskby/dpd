<?php
namespace Ipol\DPD\API\Service;

use \Ipol\DPD\API\User\UserInterface;
use \Ipol\DPD\API\Client\Factory as ClientFactory;

/**
 * Служба для работы со статусами заказа
 */
class Tracking implements ServiceInterface
{
	protected $wdsl = 'https://ws.dpd.ru/services/tracing?wsdl';

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
	 * Возвращает трекинг статусы
	 * 
	 * @return array
	 */
	public function getStatesByClient()
	{
		return $this->client->invoke('getStatesByClient');
	}

	/**
	 * Подтверждает получение статусов
	 * 
	 * @param  $docId
	 * 
	 * @return array
	 */
	public function confirm($docId)
	{
		return $this->client->invoke('confirm', array(
			'docId' => $docId
		));
	}
}