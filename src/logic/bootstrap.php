<?php
ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$loader = require __DIR__ . '/../../vendor/autoload.php';
$loader->add('Musicbozz',  __DIR__.'/../logic/');
$loader->add('Sapo',  __DIR__.'/../logic/');
$loader->register();

$redis_config = array(
				//'host'     => 'vmdev-musicbozz.vmdev.bk.sapo.pt',
   				//'port'     => 8001,
   				'host'  => '127.0.0.1',
   				'port' 	=> 6379,
			);