<?php
namespace Ipol\DPD\API\Client;

use \Ipol\DPD\API\User\UserInterface;
use \Ipol\DPD\Utils;

/**
 * Реализация SOAP клиента для работы с API
 */
class Soap extends \SoapClient implements ClientInterface
{
	/**
	 * Параметры авторизации
	 * @var array
	 */
	protected $auth = array();

	/**
	 * Параметры для SoapClient
	 * @var array
	 */
	protected $soap_options = array(
		'connection_timeout' => 20,
	);

	protected $initError = false;

	/**
	 * Конструктор класса
	 * 
	 * @param string                           $wsdl     адрес SOAP-api
	 * @param \Ipol\DPD\API\User\UserInterface $user     инстанс подключения к API    
	 * @param array                            $options  опции для SOAP
	 */
	public function __construct($wsdl, UserInterface $user, array $options = array())
	{
		try {
			$this->auth = array(
				'clientNumber' => $user->getClientNumber(),
				'clientKey'    => $user->getSecretKey(),
			);

			if (empty($this->auth['clientNumber'])
			    || empty($this->auth['clientKey'])
			) {
				throw new \Exception('DPD: Authentication data is not provided');
			}

			parent::__construct(
				$user->resolveWsdl($wsdl),
				array_merge($this->soap_options, $options)
			);
		} catch (\Exception $e) {
			$this->initError = $e->getMessage();
		}
	}

	/**
	 * Устанавливает время жизни кэша
	 * 
	 * @param int $cacheTime
	 * 
	 * @return self
	 */
	public function setCacheTime($cacheTime)
	{
		$this->cache_time = $cacheTime;

		return $this;
	}

	/**
	 * Выполняет запрос к внешнему API
	 * 
	 * @param  string $method выполняемый метод API
	 * @param  array  $args   параметры для передачи
	 * @param  string $wrap   название эл-та обертки
	 * @param  string $keys
	 * 
	 * @return mixed
	 */
	public function invoke($method, array $args = array(), $wrap = 'request', $keys = false)
	{
		$parms   = array_merge($args, array('auth' => $this->auth));
		$request = $wrap ? array($wrap => $parms) : $parms;
		$request = $this->convertDataForService($request);

		// $cache_id = serialize($request) . ($keys ? serialize($keys) : '');
		// $cache_path = '/'. IPOLH_DPD_MODULE .'/api/'. $method;

		if ($this->initError) {
			throw new \Exception($this->initError);
		}

		$ret = $this->$method($request);

		// hack return binary data
		if ($ret && isset($ret->return->file)) {
			return array('FILE' => $ret->return->file);
		}

		$ret = json_encode($ret);
		$ret = json_decode($ret, true);

		if (array_key_exists('return', $ret)) {
			$ret = $ret['return'];
			$ret = $this->convertDataFromService($ret, $keys);
		} else {
			$ret = [];
		}

		return $ret;
	}

	/**
	 * Возвращает инстанс кэша
	 * 
	 * @return 
	 */
	protected function cache()
	{
		return null;
	}

	/**
	 * Конвертирует переданные данные в формат внешнего API
	 *
	 * Под конвертацией понимается:
	 * - перевод названий параметров в camelCase
	 * 
	 * @param  array $data 
	 * 
	 * @return array
	 */
	protected function convertDataForService($data)
	{
		$ret = array();
		foreach ($data as $key => $value) {
			$key = Utils::underScoreToCamelCase($key);

			$ret[$key] = is_array($value) 
							? $this->convertDataForService($value)
							: $value;
		}

		return $ret;
	}

	/**
	 * Конвертирует полученные данные в формат модуля
	 * 
	 * Под конвертацией понимается:
	 * - перевод названий параметров в UNDER_SCORE
	 * 
	 * @param  array $data 
	 * 
	 * @return array
	 */
	protected function convertDataFromService($data, $keys = false)
	{
		$keys = $keys ? array_flip((array) $keys) : false;

		$ret = array();
		foreach ($data as $key => $value) {
			$key = $keys 
					? implode(':', array_intersect_key($value, $keys))
					: Utils::camelCaseToUnderScore($key);

			$ret[$key] = is_array($value)
							? $this->convertDataFromService($value)
							: $value;
		}

		return $ret;
	}

	// public function __doRequest($request, $location, $action, $version, $one_way = 0)
	// {
	// 	$ret = parent::__doRequest($request, $location, $action, $version, $one_way);

	// 	if (!is_dir(__DIR__ .'/logs/')) {
	// 		mkdir(__DIR__ .'/logs/', 0777);
	// 	}

	// 	file_put_contents(__DIR__ .'/logs/'. md5($location) .'.logs', ''
	// 		. 'LOCATION: '. PHP_EOL . $location . PHP_EOL
	// 		. 'REQUEST : '. PHP_EOL . $request  . PHP_EOL
	// 		. 'ANSWER  : '. PHP_EOL . $ret      . PHP_EOL
	// 	);

	// 	return $ret;	
	// }	
}