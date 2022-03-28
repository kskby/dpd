<?php
namespace Ipol\DPD\API\Service;

use \Ipol\DPD\API\User\UserInterface;
use \Ipol\DPD\API\Client\Factory as ClientFactory;

/**
 * Служба расчета стоимости доставки
 */
class Calculator implements ServiceInterface
{
	protected $wdsl = 'https://ws.dpd.ru/services/calculator2?wsdl';

	/**
     * Конструктор класса
     * 
     * @param \Ipol\DPD\API\User\UserInterface
     */
	public function __construct(UserInterface $user)
	{
		$this->client = ClientFactory::create($this->wdsl, $user);
	}

	/**
	 * Рассчитать общую стоимость доставки по России и странам ТС.
	 * 
	 * @param  array  $parms
	 * 
	 * @return array
	 */
	public function getServiceCost(array $parms)
	{
		return $this->client->invoke('getServiceCost2', $parms, 'request', 'serviceCode');
	}

	/**
	 * Рассчитать стоимость доставки по параметрам  посылок по России и странам ТС.
	 * 
	 * @param  array  $parms
	 * 
	 * @return array
	 */
	public function getServiceCostByParcels(array $parms)
	{
		return $this->client->invoke('getServiceCostByParcels2', $parms, 'request', 'serviceCode');
	}

	/**
	 * Рассчитать общую стоимость доставки по международным направлениям
	 * 
	 * @param  array  $parms
	 * 
	 * @return array
	 */
	public function getServiceCostInternational(array $parms)
	{
		return $this->client->invoke('getServiceCostInternational', $parms, 'request', 'serviceCode');
	}
}