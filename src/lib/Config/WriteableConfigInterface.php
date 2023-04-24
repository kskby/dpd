<?php
namespace Ipol\DPD\Config;

interface WriteableConfigInterface extends ConfigInterface
{
    /**
     * Запись значения опции
     * 
     * @param string $option Название опции
     * @param mixed  $value  Значение опции
     * 
     * @return self
     */
    public function set($option, $value);
}