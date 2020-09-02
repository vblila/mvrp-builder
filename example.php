<?php

require_once __DIR__ . '/RouteBuilder/LocationTimeWindow.php';
require_once __DIR__ . '/RouteBuilder/ProbabilisticRouteBuilder.php';
require_once __DIR__ . '/RouteBuilder/Route.php';
require_once __DIR__ . '/RouteBuilder/RouteCollection.php';

use RouteBuilder\LocationTimeWindow;
use RouteBuilder\ProbabilisticRouteBuilder;

$locationTimeWindows = [
    new LocationTimeWindow(25200, 79200),
    new LocationTimeWindow(25200, 79200),
    new LocationTimeWindow(25200, 79200),
    new LocationTimeWindow(25200, 79200),
    new LocationTimeWindow(25200, 79200),
    new LocationTimeWindow(25200, 79200),
    new LocationTimeWindow(25200, 79200),
    new LocationTimeWindow(25200, 79200),
    new LocationTimeWindow(25200, 79200),
];

$durationSecondsMatrix = [
    [0, 2751, 4644, 3427, 8583, 2689, 3859, 2778, 3991],
    [0, 1302, 4261, 3656, 8774, 3232, 3569, 2728, 3782],
    [0, 4394, 1164, 5064, 10331, 5012, 4200, 4079, 4807],
    [0, 3622, 4937, 1064, 7762, 2993, 4015, 3010, 3722],
    [0, 9131, 10773, 8133, 900, 8625, 9624, 8305, 9270],
    [0, 3064, 4791, 2908, 8084, 1093, 3921, 2773, 3929],
    [0, 3613, 4271, 4185, 9330, 4173, 1121, 3238, 3475],
    [0, 2693, 4077, 3029, 7872, 2948, 3153, 1107, 3291],
    [0, 3985, 4882, 4014, 8943, 4297, 3561, 3523, 1258],
];

$locationPickupWeightsKg = [0, 1, 1, 1, 1, 1, 1, 1, 1];

$routeBuilder = new ProbabilisticRouteBuilder(
    $locationTimeWindows,
    $durationSecondsMatrix,
    $locationPickupWeightsKg,
    8,
    15
);

$routeCollection = $routeBuilder->searchRoutes();

foreach ($routeCollection->getRoutes() as $route) {
    print '[' . join(',', $route->getLocationIndexes()) . ']'
        . ' total weight: '. $route->getTotalWeightKg()
        . ', duration: ' . $route->getDurationSeconds()
        . ', fined:' . $route->getFinedDurationSeconds()
        . PHP_EOL;
}
print 'build time: ' . $routeBuilder->getBuildDurationSeconds() . 's' . PHP_EOL;
print 'fined duration: ' . $routeCollection->getFinedDurationSeconds() . ' s' . PHP_EOL;
print 'has fine: ' . ($routeCollection->hasFine() ? 'yes' : 'no') . PHP_EOL;
print PHP_EOL;
