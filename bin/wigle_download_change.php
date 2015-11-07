<?php
/**
 * Created by PhpStorm.
 * User: Roman
 * Date: 07.11.2015
 * Time: 11:18
 */

$container = require __DIR__ . '/../app/bootstrap.php';
$manager = $container->getByType('App\Service\WigleDownloadService');

$manager->moveAllWigleWifiFromWifi2WigleAps();
