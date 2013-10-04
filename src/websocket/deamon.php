<?php
require_once __DIR__ . '/../logic/bootstrap.php';

use Musicbozz\Service;
use Musicbozz\WampServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

$port = !empty($argv[1]) && preg_match('/^\d{3,}$/', $argv[1]) ? $argv[1] : 9000;
$server = IoServer::factory(
    new WsServer(
        new WampServer(
            new Service
        )
    ), $port);
printf("\n\nStarting deamon in port %s\n", $port);
$server->run();
