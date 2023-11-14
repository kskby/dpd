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
	 * @param string $string                    строка для преобразования
	 * @param boolean $capitalizeFirstCharacter первый символ строчный или прописной
	 *
	 * @return string
	 */
	public static function underScoreToCamelCase(string $string, bool $capitalizeFirstCharacter = false): string
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
	 * @param string $string    строка для преобразования
	 * @param boolean $uppercase
	 *
	 * @return string
	 */
	public static function camelCaseToUnderScore(string $string, bool $uppercase = true): string
    {
		// символы разного регистра
		if (strtolower($string) != $string
			&& strtoupper($string) != $string
		) {
			$string = ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', $string)), '_');
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
	 * @param array|string $data
	 * @param string $fromEncoding
	 * @param string $toEncoding
	 *
	 * @return string|array|false
     */
    public static function convertEncoding(array|string $data, string $fromEncoding, string $toEncoding): string|array|false
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {

                if (!is_array($value) && !is_string($value)) {
                    return false;
                }

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
     * @return bool|int
     */
	public static function isNeedBreak(int $start_time): bool|int
    {
		$max_time = ini_get('max_execution_time');
		$max_time = $max_time > 0 ? $max_time : (empty($_REQUEST['REQUEST_URI']) ? false : 60);

		if ($max_time > 0) {
			return time() >= ($start_time + $max_time - 5);
		}

		return $max_time;
	}
}
