<?php

require_once __DIR__ . '/../src/logic/bootstrap.php';

$path = $_SERVER["PATH_INFO"];
$server = new Sapo\Rest\Server;
$server->add(preg_match('@question@', $path), new \Musicbozz\Rest\QuestionService());
$server->add(preg_match('@rooms/public@', $path), new \Musicbozz\Rest\RoomsService());
$server->add(preg_match('@room/.+@', $path), new \Musicbozz\Rest\RoomService());
$server->run();