<?php
require __DIR__ .'/../src/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$options = require __DIR__ .'/config.php';
$config  = new \Ipol\DPD\Config\Config($options);
$table   = \Ipol\DPD\DB\Connection::getInstance($config)->getTable('location');
$api     = \Ipol\DPD\API\User\User::getInstanceByConfig($config);

$loader = new \Ipol\DPD\DB\Location\Agent($api, $table);
$loader->loadAll();
$loader->loadCashPay();