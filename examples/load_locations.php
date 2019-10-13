<?php
require __DIR__ .'/../src/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 30);

$options = require __DIR__ .'/config.php';
$config  = new \Ipol\DPD\Config\Config($options);
$table   = \Ipol\DPD\DB\Connection::getInstance($config)->getTable('location');
$api     = \Ipol\DPD\API\User\User::getInstanceByConfig($config);

$loader = new \Ipol\DPD\DB\Location\Agent($api, $table);
$step   = isset($_GET['step']) ? $_GET['step'] : 1;
$pos    = isset($_GET['pos'])  ? $_GET['pos']  : null;

if ($step < 2) {
    $ret = $loader->loadAll($pos);

    if ($ret === true) {
        print 'LOAD LOCATIONS STEP 1: FINISH';
        print '<a href="?step=2" id="continue">continue</a><br>';
        print '<script>setTimeout(function(){document.getElementById("continue").click();}, 2000)</script>';
    } else {
        print sprintf('LOAD LOCATIONS STEP 1: %s%%<br>', round($ret[0] / $ret[1] * 100));
        print '<a href="?step=1&pos='. $ret[0] .'" id="continue">continue</a><br>';
        print '<script>setTimeout(function(){document.getElementById("continue").click();}, 2000)</script>';
    }
} elseif ($step < 3) {
    $ret = $loader->loadCashPay($pos);

    if ($ret === true) {
        print 'LOAD LOCATIONS STEP 2: FINISH';
    } else {
        $pos = explode(':', $ret[0]);

        print sprintf('LOAD LOCATIONS STEP 2: %s%%<br>', round(end($pos) / $ret[1] * 100));
        print '<a href="?step=2&pos='. $ret[0] .'" id="continue">continue</a><br>';
        print '<br>'. $ret[1];
        print '<script>setTimeout(function(){document.getElementById("continue").click();}, 2000)</script>';
    }
}