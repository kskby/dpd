<?php
namespace Ipol\DPD;

/**
 * Класс содержит вспомогательные методы для работы с модулем
 */
class Utils
{
	/**
	 * Переводит строку из under_score в camelCase
	 * 
	 * @param  string  $string                   строка для преобразования
	 * @param  boolean $capitalizeFirstCharacter первый символ строчный или прописной
	 * 
	 * @return string
	 */
	public static function underScoreToCamelCase($string, $capitalizeFirstCharacter = false)
	{
		// символы разного регистра
		if (/*strtolower($string) != $string
			&&*/ strtoupper($string) != $string
		) {
			return $string;
		}

		$string = strtolower($string);
		$string = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));

		if (!$capitalizeFirstCharacter) {
			$string[0] = strtolower($string[0]);
		}

		return $string;
	}

	/**
	 * Переводит строку из camelCase в under_score
	 * 
	 * @param  string  $string    строка для преобразования
	 * @param  boolean $uppercase
	 * 
	 * @return string
	 */
	public static function camelCaseToUnderScore($string, $uppercase = true)
	{
		// символы разного регистра
		if (strtolower($string) != $string
			&& strtoupper($string) != $string
		) {
			$string = ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', $string)), '_');;
		}		

		if ($uppercase) {
			$string = strtoupper($string);
		}

		return $string;
	}

	/**
	 * Конверирует кодировку
	 * В качестве значений может быть как скалярный тип, так и массив
	 *
	 * @param mixed $data
	 * @param string $fromEncoding
	 * @param string $toEncoding
	 * 
	 * @return mixed
	 */
	public static function convertEncoding($data, $fromEncoding, $toEncoding)
	{
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$data[$key] = static::convertEncoding($value, $fromEncoding, $toEncoding);
			}
		} else {
			$data = iconv($fromEncoding, $toEncoding, $data);
		}

		return $data;
	}

	/**
	 * Вычисляет необходимость прерывания скрипта в долгих операциях
	 * 
	 * @param integer $start_time
	 * 
	 * @return bool
	 */
	public static function isNeedBreak($start_time)
	{
		$max_time = ini_get('max_execution_time');
		
		if ($max_time > 0) {
			return time() >= ($start_time + $max_time - 5);
		}

		return false;
	}
}