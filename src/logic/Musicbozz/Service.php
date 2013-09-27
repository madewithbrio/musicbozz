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
            case 'setPlayer':
                $this->setPlayer($player, $id, $gameRoom, $params);
                break;

            case 'listPlayers':
                $this->listPlayers($player, $id, $gameRoom, $params);
                break;

            case 'getNewQuestion':
                $this->getNewQuestion($player, $id, $gameRoom, $params);
                break;

            case 'timeEnded':
                $this->timeEnded($player, $id, $gameRoom, $params);
                break;

            case 'setAnswer':
                $this->setAnswer($player, $id, $gameRoom, $params);
                break;

            case 'setReadyToPlay':
                $this->setReadyToPlay($player, $id, $gameRoom);
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

    private function setPlayer(ConnectionInterface $player, $id, $gameRoom, array $params) {
        if (!empty($params)) {
            $config = $params[0];
            $oldName = $player->getName();
            $player->setName($config['name']);
            $player->setOthers($config);

            $player->callResult($id, array('msg' => "Name changed"));

            $playersList = $this->getPlayers($player, $gameRoom);
            $gameRoom->broadcast(array('action' => 'playerConfigChange', 
                                       'data'   => $playersList));
        }
    }

    private function listPlayers(ConnectionInterface $player, $id, $gameRoom, array $params) {
        $result = $this->getPlayers($player, $gameRoom);
        $player->callResult($id, $result);
    }

    private function getNewQuestion(ConnectionInterface $player, $id, $gameRoom, array $params) {
        if ($gameRoom->isOver()) {
            $event=array();
            $event['action'] = 'gameOver';
            $event['players'] = $this->getPlayers($player, $gameRoom);
        } else {
            $question = $gameRoom->getNewQuestion();
            $event=array();
            $event['action'] = 'newQuestion';
            $event['data'] = $question->toWs();
        }
        $gameRoom->broadcast($event);
        if (!empty($id)) {
            $player->callResult($id, array());
        }
    }

    private function timeEnded(ConnectionInterface $player, $id, $gameRoom, array $params) {
        $result = $gameRoom->addAnswer($player, null);
        $this->processAnswer($player, $gameRoom, $result);

        if ($gameRoom->isAllPlayersAllreadyResponde()) {
        }
        $player->callResult($id, array());
    }

    private function setAnswer(ConnectionInterface $player, $id, $gameRoom, array $params) {
        try {
            $result = $gameRoom->addAnswer($player, $params[0]['answer'], $params[0]['hash']);
            $this->processAnswer($player, $gameRoom, $result);

            if ($gameRoom->isAllPlayersAllreadyResponde()) {
            }
            $player->callResult($id, array('action' => 'answerResult', 'res' => $result[2], 'position' => $params[0]));
        } catch (Exception $e) {
             $player->callError($id, $gameRoom, $e->getMessage());
        }
    }

    private function setReadyToPlay(ConnectionInterface $player, $id, $gameRoom) {
        $gameRoom->incPlayersReady();
        if ($gameRoom->isAllPlayersReady()) {
            $event=array();
            $event['action'] = 'allPlayersReady';
            $gameRoom->broadcast($event);  
        }
    }

    private function processAnswer(ConnectionInterface $player, $gameRoom, $result) {
        if (null === $result) return;

        $gameMode = $gameRoom->getGameMode();
        if ($result[2]) { // correct
            $score = $gameMode->getScoreForCorrectAnswer($result[3]);
        } else { // bad
            $score = $gameMode->getScoreForBadAnswer();
        }

        $player->addScore($score);
        if ($gameMode->isBroadcastPlayerHaveAnswer()) {

            $event = array();
            $event['action'] = "playersAnswerResult";
            $event['data'] = array();
            $event['data']['player'] = $player->toWs();
            if ($gameMode->isBroadcastPlayerAnswer()) {
                $event['data']['answer'] = $result[1];
            }
            if ($gameMode->isBroadcastPlayerQuestionScore()) {
                $event['data']['questionScore'] = $score;
            }
            if ($gameMode->isBroadcastPlayerTotalScore()) {
                $event['data']['totalScore'] = $player->getScore();
            }
            $gameRoom->broadcast($event);
        }

        if ($gameRoom->isAllPlayersAllreadyResponde()) {
            return $this->getNewQuestion($player, null, $gameRoom, array());
            /**
            $event = array();
            $event['action'] = "allPlayersAllreadyResponde";
            $event['data'] = array();
            $event['data']['over'] = $gameRoom->isOver();
            **/
            $gameRoom->broadcast($event);
        }
    }

    private function getPlayers(ConnectionInterface $player, $gameRoom) {
        $players = $gameRoom->getIterator();
        $result = array_fill(0,4,array()); $i = 0;
        foreach ($players as $_player) {
            $result[$i++] = $_player->toWs();
        }
        return $result;
    }


}