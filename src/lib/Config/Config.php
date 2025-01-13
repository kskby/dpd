<?php
namespace Ipol\DPD\Config;

/**
 * Класс конфиг по умолчанию
 */
class Config implements ConfigInterface
{
    protected array $options = [
        /** Директория для хранения файлов */
        'UPLOAD_DIR' => __DIR__ .'/../../../data/upload/',

        /** Директория с данными */
        'DATA_DIR' => __DIR__ .'/../../../data/',

        /** Данные для подключения к БД */
        'DB' => [
            'DSN'      => 'sqlite:'. __DIR__ .'/../../../data/db/data.sq3',
            'USERNAME' => '',
            'PASSWORD' => '',
            'DRIVER'   => null,
            'PDO'      => null,
        ],

        /** Данные авторизации */
        'KLIENT_NUMBER'      => '',
        'KLIENT_KEY'         => '',
        'KLIENT_CURRENCY'    => '',

        /** Данные авторизации при использовании нескольких аккаунтов (Казахстан, опционально) */
        'KLIENT_NUMBER_KZ'   => '',
        'KLIENT_KEY_KZ'      => '',
        'KLIENT_CURRENCY_KZ' => '',

        /** Данные авторизации при использовании нескольких аккаунтов (Беларусь, опционально) */
        'KLIENT_NUMBER_BY'   => '',
        'KLIENT_KEY_BY'      => '',
        'KLIENT_CURRENCY_BY' => '',

        'CURRENCY' => [
            'RU' => '',
            'BY' => '',
            'KZ' => '',
            'AM' => '',
            // 'UA' => '',
            'KG' => '',
        ],

        /** Страна по умолчанию, возможные значения пусто, KZ или BY */
        'API_DEF_COUNTRY'    => '',

        /** Тестовые режим */
        'IS_TEST'            => true,

        /** Габариты по умолчанию (указываются в граммах и миллиметрах) */
        'WEIGHT' => 1000,
        'LENGTH' => 200,
        'WIDTH'  => 100,
        'HEIGHT' => 200,

        /** Тарифы исключенные из расчетов */
        'TARIFF_OFF' => [],

        /**
         * Тариф по умолчанию
         * Данный тариф будет выбран автоматически если расчитанная стоимость доставки будет меньше указанной ниже
         */
        'DEFAULT_TARIFF_CODE' => 'PCL',

        /** Максимальная стоимость доставки при которой будет применен тариф по умолчанию */
        'DEFAULT_TARIFF_THRESHOLD' => 500,

        /** Включать страховку в стоимость доставки */
        'DECLARED_VALUE' => true,

        /**
         * Включать комиссию за инкассацию наложенным платежом в стоимость доставки
         * массив вида [PERSONE_TYPE_ID => bool, ...]
         */
        'COMMISSION_NPP_CHECK' => [],

        /**
         * Комиссия от стоимости товаров в заказе (в процентах), %
         * массив вида [PERSONE_TYPE_ID => double, ...]
         */
        'COMMISSION_NPP_PERCENT' => [],

        /**
         * Минимальная сумма комиссии, руб.
         * массив вида [PERSONE_TYPE_ID => double, ...]
         */
        'COMMISSION_NPP_MINSUM' => [],

        /**
         * ID платежных системы, которые означают что оплата будет происходить наложенным платежом
         * массив вида [PERSONE_TYPE_ID => [PAYMENT_SYSTEM_ID, ...], ...]
         */
        'COMMISSION_NPP_PAYMENT' => [],

        /**
         * Если платежную систему определить не удалось, считать ли что оплата будет происходить наложенным платежом по умолчанию?
         * Массив вида [PERSONE_TYPE_ID => bool, ...]
         */
        'COMMISSION_NPP_DEFAULT' => [],

        /**
         * Название источника заявок в DPD
         */
        'SOURCE_NAME' => '',

        'MARKUP' => [
            'VALUE' => 0,
            'TYPE'  => 'FIXED', // PERCENT
        ]
    ];

    /**
     * Конструктор
     *
     * @params array $parms массив опция для переопределения
     */
    public function __construct(array $parms = [])
    {
        $this->init($parms);
    }

    /**
     * Получение значения опции
     *
     * @param string $option Название опции
     * @param mixed $defaultValue Значение по умолчанию, если опция не определена
     * @param null $subKey
     * @return mixed
     */
    public function get($option, $defaultValue = null, $subKey = null): mixed
    {
        if (!isset($this->options[$option])) {
            return $defaultValue;
        }

        if (isset($subKey) && is_array($this->options[$option])) {
            if (isset($this->options[$option][$subKey])) {
                return $this->options[$option][$subKey];
            }

            return $defaultValue;
        }

        return $this->options[$option];
    }

    /**
     * Запись значения опции
     *
     * @param string $option Название опции
     * @param mixed  $value  Значение опции
     *
     * @return self
     */
    public function set(string $option, mixed $value): static
    {
        $this->options[$option] = $value;

        return $this;
    }

    /**
     * Проверяет заданы ли минимальные данные для авторизации
     *
     * @return boolean
     */
    public function isActiveAccount(): bool
    {
        $accountLang = $this->get('API_DEF_COUNTRY');
        $accountLang = $accountLang != 'RU' ? $accountLang : '';

        $clientNumber = $this->get(trim('KLIENT_NUMBER_'. $accountLang, '_'));
		$clientKey    = $this->get(trim('KLIENT_KEY_'. $accountLang, '_'));

        return !(empty($clientNumber) || empty($clientKey));
    }

    /**
     * Вызывается после создания объекта
     *
     * @param array $parms массив опций для переопределения
     *
     * @return void
     */
    protected function init(array $parms = []): void
    {
        $this->options = array_merge($this->options, $parms);
    }
}
