<?php

include './vendor/autoload.php';

class_alias('Mockery', 'M');

$manager = new FuelPHP\Alias\Manager;
$manager->register();
$manager->aliasNamespace('FuelPHP\Database', '');