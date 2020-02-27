<?php
require __DIR__ .'/../src/autoload.php';

$options = require __DIR__ .'/config.php';
$config  = new \Ipol\DPD\Config\Config($options);

$shipment = new \Ipol\DPD\Shipment($config);
$shipment->setSender('Россия', 'Москва', 'г. Москва');
$shipment->setReceiver('Россия', 'Краснодарский', 'г. Краснодар');

$shipment->setSelfPickup(false);
$shipment->setItems([
    [
        'NAME'       => 'Canon EOS 5D',
        'QUANTITY'   => 1,
        'PRICE'      => 180,
        'VAT_RATE'   => 0,
        'WEIGHT'     => 1000,
        'DIMENSIONS' => [
            'LENGTH' => 200,
            'WIDTH'  => 100,
            'HEIGHT' => 200,
        ]
    ],

    [
        'NAME'       => 'iPhone',
        'QUANTITY'   => 2,
        'PRICE'      => 399,
        'VAT_RATE'   => 18,
        'WEIGHT'     => 20,
        'DIMENSIONS' => [
            'LENGTH' => 143.6,
            'WIDTH'  => 70.9,
            'HEIGHT' => 7.7,
        ]
    ],
], 978);

$shipment->setPaymentMethod(1, 'cod');

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
        var inited = false;

        $(function() {
            'use strict';

            if (inited) {
                var data = <?= json_encode([
                    'tariffs'   => $tariffs,
                    'terminals' => array_values($terminals)
                ]) ?>;

                $('#dpd-map').dpdMap('reload', data);
            } else {
                $('#dpd-map')
                    .dpdMap({}, <?= json_encode([
                        'tariffs'   => $tariffs,
                        'terminals' => array_values($terminals)
                    ]) ?>)

                    .on('dpd.map.terminal.select', function(e, terminal, widget) {
                        console.log(terminal);

                        alert(terminal.CODE)
                    })
                ;

                inited = true;
            }
        })
    </script>

</head>
<body>
    <div id="dpd-map"></div>
</body>
</html>
