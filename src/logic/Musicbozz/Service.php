<?php

namespace Musicbozz;
use \Ratchet\Wamp\WampServerInterface as WampServerInterface;
use \Ratchet\ConnectionInterface;
class Service implements WampServerInterface {


    public function onPublish(ConnectionInterface $player, $gameRoom, $event, array $exclude, array $eligible) {
//        $gameRoom->broadcast($event);
    }

    public function onCall(ConnectionInterface $player, $id, $gameRoom, array $params) {
        $action = array_shift($params);
        switch($action) {
            case 'setPlayerName':
                $this->setPlayerName($player, $id, $gameRoom, $params);
                break;

            case 'listPlayers':
                $this->listPlayers($player, $id, $gameRoom, $params);
                break;

            case 'newQuestion':
                $this->getNewQuestion($player, $id, $gameRoom, $params);
                break;

            default:
                $player->callError($id, $gameRoom, 'RPC not supported yet');
                break;
        }
    }

    // join to game
    public function onSubscribe(ConnectionInterface $player, $gameRoom) {

    }

    public function onUnSubscribe(ConnectionInterface $player, $gameRoom) {

    }

    public function onOpen(ConnectionInterface $player) {}
    public function onClose(ConnectionInterface $player) {}
    public function onError(ConnectionInterface $player, \Exception $e) {}

    private function setPlayerName(ConnectionInterface $player, $id, $gameRoom, array $params) {
        if (!empty($params)) {
            $newName = $params[0];
            $oldName = $player->getName();
            $player->setName($newName);
            $player->callResult($id, array('msg' => "Name changed"));
            $gameRoom->broadcast(array('action' => 'playerNameChange', 
                                       'data' => sprintf("%s have change name to %s", $oldName, $newName)));
        }
    }

    private function listPlayers(ConnectionInterface $player, $id, $gameRoom, array $params) {
        $players = $gameRoom->getIterator();
        $result = array();
        foreach ($players as $_player) {
            $playerItem['name'] = $_player->getName();
            $playerItem['score'] = $_player->getScore();
            $playerItem['isMe'] = $_player->getSessionId() == $player->getSessionId();
            $result[] = $playerItem;
        }
        $player->callResult($id, $result);
    }

    private function getNewQuestion(ConnectionInterface $player, $id, $gameRoom, array $params) {
        $question = $gameRoom->getNewQuestion();
        var_dump($question);
        $data=array();
        $data['action'] = 'newQuestion';
        $data['data'] = $question->toWs();
        $gameRoom->broadcast($data);
        $player->callResult($id, array());
    }
}