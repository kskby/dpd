<?php
require __DIR__ .'/../src/autoload.php';

$options = require __DIR__ .'/config.php';
$config  = new \Ipol\DPD\Config\Config($options);

$shipment = new \Ipol\DPD\Shipment($config);
$shipment->setSender('Россия', 'Москва', 'г. Москва');
$shipment->setReceiver('Россия', 'Москва', 'г. Москва');

$shipment->setSelfPickup(false);

$shipment->setItems([
    [
        'NAME' => 'BMC Велосипед Teamelite TE02 Deore/SLX Size: L Yellow (2017)',
        'QUANTITY' => 1,
        'PRICE' => 181200,
        'VAT_RATE' => 'Без НДС',
        'WEIGHT' => 17000,
        'DIMENSIONS' => [
            'LENGTH' => 1700,
            'WIDTH'  => 300,
            'HEIGHT' => 800
        ]
    ]

], 181200);



echo '<pre>';
print 'До двери';
$shipment->setSelfDelivery(false);
$tariff = $shipment->calculator()->calculate();
print_r($tariff);

print 'До терминала';
$shipment->setSelfDelivery(true);
$tariff = $shipment->calculator()->calculate();
print_r($tariff);