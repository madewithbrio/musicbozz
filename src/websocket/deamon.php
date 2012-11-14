<?php
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$loader = require __DIR__ . '/../../vendor/autoload.php';
$loader->add('Musicbozz',  __DIR__.'/../logic/');
$loader->add('Sapo',  __DIR__.'/../logic/');
$loader->register();

use Musicbozz\Service;
use Musicbozz\WampServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

$server = IoServer::factory(
    new WsServer(
        new WampServer(
            new Service
        )
    ), 9000
);
$server->run();