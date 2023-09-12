<?php
namespace Ipol\DPD\API\Service;

use Exception;
use Ipol\DPD\API\Client\ClientInterface;
use Ipol\DPD\API\Client\Soap;
use Ipol\DPD\API\User\UserInterface;
use Ipol\DPD\API\Client\Factory as ClientFactory;

/**
 * Служба по работе с географическими данными
 */
class Geography implements ServiceInterface
{
	protected string $wdsl = 'https://ws.dpd.ru/services/geography2?wsdl';

	protected $clientOld;
    private Soap|ClientInterface $client;

    /**
     * Конструктор класса
     *
     * @param UserInterface $user
     * @throws Exception
     */
	public function __construct(UserInterface $user)
	{
		// По не известным причинам данный сервис в тестовом режиме
		// выдает soap ошибку. В боевом - этой ошибки нет.
		// Поэтому для этого сервиса мы отключаем тестовый режим всегда
		// $user = new User($user->getClientNumber(), $user->getSecretKey(), false);

		$this->client = ClientFactory::create($this->wdsl, $user);
	}

    /**
     * Возвращает список городов с возможностью доставки наложенным платежом
     *
     * @param string $countryCode код страны
     *
     * @return array
     * @throws Exception
     */
	public function getCitiesCashPay(string $countryCode = 'RU'): array
    {
		return $this->client->invoke('getCitiesCashPay', array(
			'countryCode' => $countryCode
		), 'request', 'cityCode');
	}

    /**
     * Возвращает список пунктов приема/выдачи посылок, имеющих ограничения по габаритам и весу,
     * с указанием режима работы пункта и доступностью выполнения самопривоза/самовывоза.
     * При работе с методом необходимо проводить получение информации по списку подразделений ежедневно.
     *
     * @param string $countryCode код страны
     * @param bool|string $regionCode код региона
     * @param bool|string $cityCode код города
     * @param bool|string $cityName название города
     *
     * @return array
     * @throws Exception
     */
	public function getParcelShops(string $countryCode = 'RU', bool|string $regionCode = false, bool|string $cityCode = false, bool|string $cityName = false): array
    {
		$ret = $this->client->invoke('getParcelShops', array_filter(array(
			'countryCode' => $countryCode,
			'regionCode'  => $regionCode,
			'cityCode'    => $cityCode,
			'cityName'    => $cityName
		)));

		if ($ret) {
			return array_key_exists('CODE', $ret['PARCEL_SHOP']) ? [$ret['PARCEL_SHOP']] : $ret['PARCEL_SHOP'];
		}

		return $ret;
	}

    /**
     * Возвращает список подразделений DPD, не имеющих ограничений по габаритам и весу посылок приема/выдачи
     *
     * @return array
     * @throws Exception
     */
	public function getTerminalsSelfDelivery2(): array
    {
		$ret = $this->client->invoke('getTerminalsSelfDelivery2', array(), false);

		if ($ret) {
			return array_key_exists('CODE', $ret['TERMINAL']) ? [$ret['TERMINAL']] : $ret['TERMINAL'];
		}

		return $ret;
	}

    /**
     * Возвращает информацию о сроке бесплатного хранения на пункте
     *
     * @param array $terminalCodes
     * @param array $serviceCode
     *
     * @return array
     * @throws Exception
     */
	public function getStoragePeriod(array $terminalCodes = array(), array $serviceCode = array()): array
    {
		return $this->client->invoke('getStoragePeriod', array_filter(array(
			'terminalCоdes' => implode(',', $terminalCodes),
			'serviceCode'   => implode(',', $serviceCode)
		)));
	}
}
