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

$tariffs = [
    'courier' => $shipment->setSelfDelivery(true)->calculator()->calculate(),
    'pickup'  => $shipment->setSelfDelivery(false)->calculator()->calculate(),
];

$terminals = \Ipol\DPD\DB\Connection::getInstance($config)->getTable('terminal')->findModels([
    'where' => 'LOCATION_ID = :location',
    'bind'  => ['location' => $shipment->getReceiver()['CITY_ID']],
]);

$terminals = array_filter($terminals, function($terminal) use ($shipment) {
    return $terminal->checkShipment($shipment);
});

?>

<html>
<head>
    <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU"></script>
    <script src="../../widgets-map/src/js/jquery.min.js"></script>
    <script src="../../widgets-map/src/js/jquery.dpd.map.js?<?= microtime(true) ?>"></script>
    <link rel="stylesheet" type="text/css" href="../../widgets-map/src/css/style.css">

    <script>
        $(function() {
            'use strict';

            $('#dpd-map')
                .dpdMap({}, <?= json_encode([
                    'tariffs'   => $tariffs,
                    'terminals' => $terminals
                ]) ?>)

                .on('dpd.map.terminal.select', function(e, terminal, widget) {
                    console.log(terminal);

                    alert(terminal.CODE)
                })
            ;
        })
    </script>

</head>
<body>
    <div id="dpd-map"></div>
</body>
</html>
