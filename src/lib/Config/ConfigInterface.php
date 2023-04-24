<?php
namespace Ipol\DPD\Config;

/**
 * Интерфейс конфига
 */
interface ConfigInterface
{
    /**
     * Получение значения опции
     * 
     * @param string $option       Название опции
     * @param mixed  $defaultValue Значение по умолчанию, если опция не определена
     * @param mixed  $subKey       Имя ключ, если опция это массив
     * 
     * @return mixed
     */
    public function get($option, $defaultValue = null, $subKey = null);
}