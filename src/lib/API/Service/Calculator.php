<?php

namespace Ipol\DPD\API\Service;

use Exception;
use Ipol\DPD\API\Client\ClientInterface;
use Ipol\DPD\API\Client\Soap;
use Ipol\DPD\API\User\UserInterface;
use Ipol\DPD\API\Client\Factory as ClientFactory;

/**
 * Служба расчета стоимости доставки
 */
class Calculator implements ServiceInterface
{
    protected string $wdsl = 'https://ws.dpd.ru/services/calculator2?wsdl';
    private Soap|ClientInterface $client;

    /**
     * Конструктор класса
     *
     * @param UserInterface $user
     * @throws Exception
     */
    public function __construct(UserInterface $user)
    {
        $this->client = ClientFactory::create($this->wdsl, $user);
    }

    /**
     * Рассчитать общую стоимость доставки по России и странам ТС.
     *
     * @param array $parms
     *
     * @return array
     * @throws Exception
     */
    public function getServiceCost(array $parms): array
    {
        return $this->client->invoke('getServiceCost2', $parms, 'request', 'serviceCode');
    }

    /**
     * Рассчитать стоимость доставки по параметрам  посылок по России и странам ТС.
     *
     * @param array $parms
     *
     * @return array
     * @throws Exception
     */
    public function getServiceCostByParcels(array $parms): array
    {
        return $this->client->invoke('getServiceCostByParcels2', $parms, 'request', 'serviceCode');
    }

    /**
     * Рассчитать общую стоимость доставки по международным направлениям
     *
     * @param array $parms
     *
     * @return array
     * @throws Exception
     */
    public function getServiceCostInternational(array $parms): array
    {
        return $this->client->invoke('getServiceCostInternational', $parms, 'request', 'serviceCode');
    }
}
