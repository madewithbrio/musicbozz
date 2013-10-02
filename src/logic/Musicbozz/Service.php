<?php

namespace Musicbozz;
use \Ratchet\Wamp\WampServerInterface as WampServerInterface;
use \Ratchet\ConnectionInterface;
use \Exception;

class Service implements WampServerInterface {

    public function onPublish(ConnectionInterface $player, $gameRoom, $event, array $exclude, array $eligible) {
//        $gameRoom->broadcast($event);
    }

    public function onCall(ConnectionInterface $player, $id, $gameRoom, array $params) {
        $action = array_shift($params);
        switch($action) {
            case 'setPlayer':
                $gameRoom->setPlayerConfig($player, $id, $params[0]);
                break;

            case 'listPlayers':
                $gameRoom->listPlayers($player, $id);
                break;

            case 'getNewQuestion':
                $gameRoom->getNewQuestion($player, $id);
                break;

            case 'setAnswer':
            case 'timeEnded':
                $gameRoom->setAnswer($player, $id, $params[0]);
                break;

            case 'setReadyToPlay':
                $gameRoom->setReadyToPlay($player, $id, $params[0]);
                break;

            case 'setRematch':
                $gameRoom->setRematch($player, $id);
                break;

            case 'forcePlay':
                $gameRoom->forcePlay($player, $id, $params[0]);
                break;
                
            default:
                $player->callError($id, $gameRoom, 'RPC not supported yet');
                break;
        }
    }

    // join to game
    public function onSubscribe(ConnectionInterface $player, $gameRoom) {
        printf (" > new join at %s \n", $gameRoom->getId());
    }

    public function onUnSubscribe(ConnectionInterface $player, $gameRoom) {
        printf (" > leave %s \n", $gameRoom->getId());
    }

    public function onOpen(ConnectionInterface $player) {}
    public function onClose(ConnectionInterface $player) {}
    public function onError(ConnectionInterface $player, \Exception $e) {}

    
}