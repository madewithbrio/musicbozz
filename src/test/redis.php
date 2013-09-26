<?php
ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$loader = require __DIR__ . '/../../vendor/autoload.php';
$loader->add('Musicbozz',  __DIR__.'/../logic/');
$loader->add('Sapo',  __DIR__.'/../logic/');
$loader->register();

/**
        var_dump(Sapo\Redis::getInstance()->hset('rooms', 'fvdf', 'afvwev'));
**/
var_dump(Sapo\Redis::getInstance()->hgetall('rooms'));