<?php
require __DIR__ .'/../src/autoload.php';

$options = require __DIR__ .'/config.php';
$config  = new \Ipol\DPD\Config\Config($options);

$shipment = new \Ipol\DPD\Shipment($config);
$shipment->setSender('Россия', 'Москва', 'г. Москва');
$shipment->setReceiver('Россия', 'Тульская область', 'г. Тула');

$shipment->setSelfDelivery(true);
$shipment->setSelfPickup(true);

$shipment->setItems([
    [
        'NAME'       => 'Товар 1',
        'QUANTITY'   => 1,
        'PRICE'      => 1000,
        'VAT_RATE'   => 18,
        'WEIGHT'     => 1000,
        'DIMENSIONS' => [
            'LENGTH' => 200,
            'WIDTH'  => 100,
            'HEIGHT' => 50,
        ]
    ],

    [
        'NAME'       => 'Товар 2',
        'QUANTITY'   => 1,
        'PRICE'      => 1000,
        'VAT_RATE'   => 18,
        'WEIGHT'     => 1000,
        'DIMENSIONS' => [
            'LENGTH' => 350,
            'WIDTH'  => 70,
            'HEIGHT' => 200,
        ]
    ],

    [
        'NAME'       => 'Товар 3',
        'QUANTITY'   => 1,
        'PRICE'      => 1000,
        'VAT_RATE'   => 18,
        'WEIGHT'     => 1000,
        'DIMENSIONS' => [
            'LENGTH' => 220,
            'WIDTH'  => 100,
            'HEIGHT' => 70,
        ]
    ],
], 3000);

$order = \Ipol\DPD\DB\Connection::getInstance($config)->getTable('order')->makeModel();
$order->setShipment($shipment);

$order->orderId = 1;
$order->currency = 'RUB';

$order->serviceCode = 'PCL';

$order->senderName = 'Наименование отправителя';
$order->senderFio = 'ФИО отправителя';
$order->senderPhone = 'Телефон отправителя';
$order->senderTerminalCode = '009M';

$order->receiverName = 'Наименование получателя';
$order->receiverFio = 'ФИО получателя';
$order->receiverPhone = 'Телефон получателя';
$order->receiverTerminalCode = 'M11';

$order->pickupDate = '2018-11-14';
$order->pickupTimePeriod = '9-18';

$result = $order->dpd()->create();

print_r($result);