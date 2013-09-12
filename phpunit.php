<?php

include './vendor/autoload.php';

class_alias('Mockery', 'M');

$manager = new Fuel\Alias\Manager;
$manager->register();
$manager->aliasNamespace('Fuel\Database', '');