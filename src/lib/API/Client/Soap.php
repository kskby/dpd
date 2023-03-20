<?php
namespace Ipol\DPD\API\Client;

use \Ipol\DPD\API\User\UserInterface;
use \Ipol\DPD\Utils;
use \Symfony\Component\Cache\Adapter\FilesystemAdapter;

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
	 * Кэш
	 *
	 * @var \Symfony\Component\Cache\Adapter\FilesystemAdapter
	 */
	protected $cache = null;

	/**
	 * Время жизни кэша
	 *
	 * @var integer
	 */
	protected $cache_time = 86400;

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
		if ($this->initError) {
			throw new \Exception($this->initError);
		}
		
		$parms     = array_merge($args, array('auth' => $this->auth));
		$request   = $wrap ? array($wrap => $parms) : $parms;
		$request   = $this->convertDataForService($request);

		$cache_key = 'api.'. $method .'.'. md5(serialize($request) . ($keys ? serialize($keys) : ''));
		$cache     = $this->cache();
		$cacheItem = $cache ? $cache->getItem($cache_key) : false;

		if (!$cacheItem || !$cacheItem->isHit()) {
			$ret = $this->$method($request);

			// hack return binary data
			if ($ret && isset($ret->return->file)) {
				return array('FILE' => $ret->return->file);
			}

			$ret = json_encode($ret);
			$ret = json_decode($ret, true);

			if (array_key_exists('return', $ret)) {
				$ret = $ret['return'];

                if ($keys && is_array($ret) && array_intersect((array) $keys, array_keys($ret))) {
					$ret = [$ret];
				}

				$ret = $this->convertDataFromService($ret, $keys);
			} else {
				$ret = [];
			}

			if ($cacheItem) {
				$cacheItem->set($ret);
				$cache->save($cacheItem);
			}
		} else {
			$ret = $cacheItem->get();
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
		if ($this->cache === null) {
			if (class_exists(FilesystemAdapter::class) && $this->cache_time > 0) {
				$this->cache = new FilesystemAdapter('', $this->cache_time, __DIR__ .'/../../../../data/cache/');
			} else {
				$this->cache = false;
			}
		}

		return $this->cache;
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
			if ($key != 'GTIN') {
				$key = Utils::underScoreToCamelCase($key);
			}

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
		foreach ((array) $data as $key => $value) {
			$key = $keys 
					? implode(':', array_intersect_key($value, $keys))
					: Utils::camelCaseToUnderScore($key);

			$ret[$key] = is_array($value)
							? $this->convertDataFromService($value)
							: $value;
		}

		return $ret;
	}

	public function __doRequest($request, $location, $action, $version, $one_way = 0)
	{
		$ret = parent::__doRequest($request, $location, $action, $version, $one_way);

		if (!is_dir(__DIR__ .'/logs/')) {
			mkdir(__DIR__ .'/logs/', 0777);
		}

		file_put_contents(__DIR__ .'/logs/'. md5($location) .'.logs', ''
			. 'LOCATION: '. PHP_EOL . $location . PHP_EOL
			. 'REQUEST : '. PHP_EOL . $request  . PHP_EOL
			. 'ANSWER  : '. PHP_EOL . $ret      . PHP_EOL
		);

		return $ret;	
	}	
}