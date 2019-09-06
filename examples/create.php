<?php
require __DIR__ .'/../src/autoload.php';

$options = require __DIR__ .'/config.php';
$config  = new \Ipol\DPD\Config\Config($options);

$shipment = new \Ipol\DPD\Shipment($config);
$shipment->setSender('Россия', 'Москва', 'г. Москва');
$shipment->setReceiver('Россия', 'Тульская область', 'г. Тула');

/**
 * Отправка от
 * false - от двери
 * true  - от терминала
 */
$shipment->setSelfPickup(false);

/**
 * Отправка до
 * false - от двери
 * true  - от терминала
 */
$shipment->setSelfDelivery(false);

$shipment->setItems([
    [
        'NAME'       => 'Тестовый заказ ТТ',
        'QUANTITY'   => '8',
        'PRICE'      => 2412.00,
        'VAT_RATE'   => 'Без НДС',
        'WEIGHT'     => 1000,
        'DIMENSIONS' => [
            'LENGTH' => 200,
            'WIDTH'  => 100,
            'HEIGHT' => 50,
        ]
    ],
]);

$order = \Ipol\DPD\DB\Connection::getInstance($config)->getTable('order')->makeModel();
$order->setShipment($shipment);

$order->orderId = 1;

$order->serviceCode = 'PCL';

$order->senderName = 'Наименование отправителя';
$order->senderFio = 'ФИО отправителя';
$order->senderPhone = 'Телефон отправителя';

// если отправка от двери как минимум необходимо указать улицу
// в поле улица можно указать полный адрес (улица, дом, строение), но тогда есть вероятность
// что заявка попадет в статус OrderPending - требуется проверка со стороны DPD
$order->senderStreet  = 'Ленина 31';

// так же при отправке от двери можно заполнить поля
// senderStreetAbbr - уббревиатура улицы
// senderHouse - номер дома
// senderKorpus - корпус
// senderStr - строение
// senderVlad - владение
// senderOffice - номер оффиса


$order->receiverName = 'Наименование получателя';
$order->receiverFio = 'ФИО получателя';
$order->receiverPhone = 'Телефон получателя';

// если отправка до двери как минимум необходимо указать улицу
// в поле улица можно указать полный адрес (улица, дом, строение), но тогда есть вероятность
// что заявка попадет в статус OrderPending - требуется проверка со стороны DPD
$order->receiverStreet = 'Ленина 31';

// так же при отправке до двери можно заполнить поля
// receiverStreetAbbr - уббревиатура улицы
// receiverHouse - номер дома
// receiverKorpus - корпус
// receiverStr - строение
// receiverVlad - владение
// receiverOffice - номер оффиса

$order->pickupDate = date('Y-m-d');
$order->pickupTimePeriod = '9-18';
$order->cargoValue = 2412;

$result = $order->dpd()->create();

print_r($result);