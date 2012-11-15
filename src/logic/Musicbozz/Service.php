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
                printf (" > setPlayerName at %s \n", $gameRoom->getId());
                $this->setPlayerName($player, $id, $gameRoom, $params);
                break;

            case 'listPlayers':
                $this->listPlayers($player, $id, $gameRoom, $params);
                break;

            case 'newQuestion':
                $this->getNewQuestion($player, $id, $gameRoom, $params);
                break;

            case 'timeEnded':
                $this->timeEnded($player, $id, $gameRoom, $params);
                break;

            default:
                $player->callError($id, $gameRoom, 'RPC not supported yet');
                break;
        }
    }

    // join to game
    public function onSubscribe(ConnectionInterface $player, $gameRoom) {
        printf (" > new join at %s \n", $gameRoom->getId());
        $playersList = $this->getPlayers($player, $gameRoom);
        $gameRoom->broadcast(array('action' => 'newPlayer', 'data' => $playersList));
    }

    public function onUnSubscribe(ConnectionInterface $player, $gameRoom) {
        printf (" > leave %s \n", $gameRoom->getId());
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

            $playersList = $this->getPlayers($player, $gameRoom);
            $gameRoom->broadcast(array('action' => 'playerNameChange', 
                                       'data'   => $playersList));
        }
    }

    private function listPlayers(ConnectionInterface $player, $id, $gameRoom, array $params) {
        $result = $this->getPlayers($player, $gameRoom);
        $player->callResult($id, $result);
    }

    private function getNewQuestion(ConnectionInterface $player, $id, $gameRoom, array $params) {
        $question = $gameRoom->getNewQuestion();
        $data=array();
        $data['action'] = 'newQuestion';
        $data['data'] = $question->toWs();
        $gameRoom->broadcast($data);
        $player->callResult($id, array());
    }

    private function timeEnded(ConnectionInterface $player, $id, $gameRoom, array $params) {
        $gameRoom->addAnswer($player, null);
        $player->callResult($id, array());
        if ($gameRoom->isAllPlayersAllreadyResponde()) {
        /**
            $data=array();
            $data['action'] = 'AllPlayersAllreadyResponde';
            $data['data'] = array();
            $gameRoom->broadcast($data); 
        **/
            /** @todo **/
        }
    }

    private function getPlayers(ConnectionInterface $player, $gameRoom) {
        $players = $gameRoom->getIterator();
        $result = array_fill(0,4,array()); $i = 0;
        foreach ($players as $_player) {
            $playerItem['name'] = $_player->getName();
            $playerItem['score'] = $_player->getScore();
            $result[$i++] = $playerItem;
        }
        return $result;
    }
}