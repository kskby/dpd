<?php
namespace Ipol\DPD\DB\Location;

/**
 * Класс обеспечивающий приведение различных адресов к единой форме
 */
class Normalizer
{
    /**
     * Возвращает нормализованную информацию о нас. пункте
     *
     * @param string $country  Название страны
     * @param string $region   Название региона
     * @param string $locality Название населенного пункта
     *
     * @return array
     */
    public function normilize(string $country, string $region, string $locality): array
    {
        return array_merge(
            $country  = $this->normilizeCountry($country),
            $region   = $this->normilizeRegion($region, $country),
            $locality = $this->normilizeCity($locality, $region)
        );
    }

    /**
     * Возвращает информацию о стране
     *
     * @param string $country  Название страны
     *
     * @return array
     */
    public function normilizeCountry(string $country): array
    {
        return [
            'COUNTRY_NAME' => str_replace('ё', 'е', $country),
            'COUNTRY_CODE' => array_search(mb_strtolower($country, 'UTF-8'), $this->getCountryList()),
        ];
    }

    /**
     * Возвращает информацию о регионе
     *
     * @param string $region
     * @param array $country
     *
     * @return array
     */
    public function normilizeRegion(string $region, array $country): array
    {
        $this->trimAbbr($region, $this->getRegionAbbrList());

        return [
            'REGION_NAME' => str_replace('ё', 'е', $region),
            'REGION_CODE' => array_search(
                mb_strtolower($region, 'UTF-8'),
                $this->getRegionCodeList($country['COUNTRY_CODE'])
            ),
        ];
    }

    /**
     * Возвращает нормализованную информацию о нас. пункте
     *
     * @param string $city
     * @param array $region
     *
     * @return array
     */
    public function normilizeCity(string $city, array $region): array
    {
        $abbr = $this->trimAbbr($city, array_merge(
            $this->getCityAbbrList(),
            $this->getVillageAbbrList()
        ));

        $city = $this->checkAnalog($city, $region);

        return [
            'CITY_NAME' => str_replace('ё', 'е', $city),
            'CITY_ABBR' => $abbr,
            'IS_CITY'   => in_array($abbr, $this->getCityAbbrList()) ? 1 : 0,
        ];
    }

    /**
     * Объединяет города аналоги в один город
     *
     * @param string $city
     * @param array $region
     *
     * @return string
     */
    public function checkAnalog(string $city, array $region): string
    {
        $regionLower = mb_strtolower($region['REGION_NAME'], 'UTF-8');
        $cityLower   = mb_strtolower($city, 'UTF-8');

        foreach ($this->getCityAnalogs() as $analog => $analogs) {
            if (isset($analogs[$cityLower])
                && in_array($regionLower, $analogs[$cityLower])
            ) {
                return $analog;
            }
        }

        return $city;
    }

    /**
     * Удаляет из строки аббревиатуру и возвращает ее
     *
     * @param string $string
     * @param array $abbrList
     *
     * @return bool|string|null
     */
    protected function trimAbbr(string &$string, array $abbrList): bool|string|null
    {
        usort($abbrList, function($a, $b) {
            return mb_strlen($b, 'UTF-8') - mb_strlen($a, 'UTF-8');
        });

        foreach ($abbrList as $abbr) {
            $abbr       = trim($abbr);
            $abbrRegexp = '~\b'. preg_quote($abbr) .'\b~sUui';

            if (preg_match($abbrRegexp, $string)) {
                $string = preg_replace($abbrRegexp, '', $string);

                $string = trim($string, ' .');
                $string = preg_replace('{\s{2,}}', ' ', $string);
                $string = trim($string);

                return $abbr;
            }
        }

        return null;
    }

    /**
     * Возвращает список стран
     *
     * @return array
     */
    protected function getCountryList(): array
    {
        return [
            'RU' => 'россия',
            'KZ' => 'казахстан',
            'BY' => 'беларусь',
        ];
    }

    /**
     * Возвращает аббревиатуру региона
     *
     * @return array
     */
    protected function getRegionAbbrList(): array
    {
        return [
            'автономный округ',
            'область',
            'аобл',
            'обл',
            'АО',
            'республика',
            'респ',
            'край',
            'г',
        ];
    }

    /**
     * Возвращает список кодов регионов
     *
     * @param string $countryCode
     *
     * @return array
     */
    protected function getRegionCodeList(string $countryCode): array
    {
        $file = __DIR__ .'/../../../../data/regions_'. $countryCode .'.php';

        if (file_exists($file)) {
            return include($file);
        }

        return [];
    }

    /**
     * Возвращает аббревиатуру города
     *
     * @return array
     */
    protected function getCityAbbrList(): array
    {
        return [
            'город',
            'г',
        ];
    }

    /**
     * Возвращает аббревиатуру нас. пункта
     *
     * @return array
     */
    protected function getVillageAbbrList(): array
    {
        return [
            'посёлок городского типа',
            'поселок городского типа',
            'пгт',
            'деревня',
            'д',
            'село',
            'с',
            'поселок',
            'посёлок',
            'п',
            'станция',
            'ст',
            'аул',
            'станица',
            'ст-ца',
            'снт',
            'рзд',
            'сл',
            'дп',
            'х',
            'жилрайон',
            'тер',
            'ж/д_ст',
            'тер сдт',
            'нп',
            'у',
            'массив',
            'автодорога',
            'м',
            'сл',
            'городок',
            'дск',
            'платф',
            'починок',
            'промзона',
            'агрогородок'
        ];
    }

    /**
     * Возвращает список городов аналогов
     *
     * @return array
     */
    protected function getCityAnalogs(): array
    {
        return [];

        return [
            // город
            'Москва' => [
                // аналог       // области
                'зеленоград' => ['москва', 'московская'],
                'твepь'      => ['москва', 'московская'],
                'тверь_969'  => ['москва', 'московская'],
                // 'московский' => ['москва', 'московская'],
            ],

            'Санкт-петербург' => [
                'колпино'      => ['ленинградская'],
                'красное cело' => ['ленинградская'],
                'кронштадт'    => ['ленинградская'],
                'ломоносов'    => ['ленинградская'],
                'павловск'     => ['ленинградская'],
                'пушкин'       => ['ленинградская'],
                'сестрорецк'   => ['ленинградская'],
                'петергоф'     => ['ленинградская'],
            ],

            'Севастополь' => [
                'инкерман' => ['крым'],
            ],
        ];
    }
}
