<?php

namespace Musicbozz;
use \Ratchet\ConnectionInterface;
use \Ratchet\Wamp\Topic;
use \Exception;

class GameRoom extends Topic
{
	private $questionNumber = 0;
	private $question;
	private $answers;
	private $playersReadyToPlay = 0;
	private $gameMode;
	private $loop;
	private $logger;

	public function __construct($topicId) {
		parent::__construct($topicId);
		\Sapo\Redis::getInstance()->hdel('rooms', $this->getRoomId());
	}

	public function __destruct() {
		\Sapo\Redis::getInstance()->hdel('rooms', $this->getRoomId());
	}
    

	private function getLogger() {
	    	if (null === $this->logger) {
	    		$this->logger = \Logger::getLogger(sprintf('GameRoom[%s]', $this->getRoomId()));
	    	}
	    	return $this->logger;
    	}

	/**
	 * @ override
	 */
	public function add(ConnectionInterface $player) {
		$this->getLogger()->info("number of players in root: " . $this->count());
		if ($this->count() == 1) {
			$this->setMaster($player);
			$this->questionNumber = 0;
		}

		// room closed
		if (!$this->isOpen()) {
			$this->getLogger()->info("room closed");
			$player->close();
			return;
		}

		parent::add($player);
		$this->getLogger()->info("player have join");
		$this->broadcast(array('action' => 'newPlayer', 'data' => $this->getPlayers()));
		$this->notificationStatus();
	}

	public function remove(ConnectionInterface $player) {
		parent::remove($player);
		foreach ($this as $player) { // set player master
			$this->setMaster($player);
			break;
		}
		if ($this->count() == 0) {
			$this->questionNumber = 0;
		}
		$this->getLogger()->info("player have leave");
		$this->broadcast(array('action' => 'playerLeave', 'data' => $this->getPlayers()));
		$this->notificationStatus();
	}

	private function getPlayers () {
		$result = array_fill(0,4,array()); $i = 0;
		foreach ($this as $player) {
		    $result[$i++] = $player->toWs();
		}
		return $result;
	}


	protected function setMaster(ConnectionInterface $player) {
		$player->setMaster(true);
		$player->event($this->getId(), array('action' => 'setMaster'));
	}

	protected function getRoomId() {
		return preg_replace('@http://localhost/game/(\.+)$@', '$1', $this->getId());
	}

	protected function getStatus() {
		$players = array();
		foreach ($this as $player) {
			$players[] = $player->toWs();
		}
		return array(
			'players' => $players,
			'isOpen'  => $this->isOpen(),
			'isOver'  => $this->isOver(),
			'question' => $this->question
			);
	}

	protected function isPublic() {
		return (preg_match('/^room\/(\d+)$/', $this->getRoomId()));
	}

	protected function isAllPlayersReady() {
		return $this->playersReadyToPlay == $this->count();
	}

	protected function isAllPlayersAlreadyResponde() {
		return sizeof($this->answers) == $this->count();
	}

	protected function isOver() {
		return ($this->questionNumber > 20);
	}


	protected function isOpen() {
		return ($this->questionNumber === 0 &&  $this->count() < 4);
	}

	protected function notificationStatus() {
		print "notify redis\n";
		\Sapo\Redis::getInstance()->hset('rooms', $this->getRoomId(), serialize($this->getStatus()));
	}

	protected function getQuestion() {
		if (null === $this->question) {
			$this->question = Question::factory(Question_Type::getRandom(), ++$this->questionNumber);
		}
		return $this->question;
	}

	protected function addAnswer($playerSessionId, $answer, $hash) {
		if ($hash !== $this->getQuestion()->hash) throw new Exception("Hash not valid", 1);
		
		$position = 1;
		foreach ($this->answers as $_answer) {
			if ($_answer[0] == $playerSessionId) throw new Exception("Already answer", 1);
			if ($_answer[2]) { $position++; }
		}

		$isCorrect 			= $this->getQuestion()->isCorrectAnswer($answer);
		$data 				= array($playerSessionId, $answer, $isCorrect);
		$this->answers[] 	= $data;
		if (!$isCorrect || null === $answer) { $position = 5; }

		$data[] 			= $position;
		return $data;
	}

	protected function getGameMode() {
		if (null === $this->gameMode) {
			//if ($this->questionNumber < 5) {
				$this->gameMode = GameMode::factory('Standard');
			/**
			} else if ($this->questionNumber < 10) {
				$this->gameMode = GameMode::factory('Normal');
			} else {
				$this->gameMode = GameMode::factory('Speed');
			}
			**/
		}
		return $this->gameMode;
	}

	/** @Triggers **/
	protected function onGameOver() {

	}

	protected function onAllAlreadyResponde() {
		#$this->getLoop()->addTimer(2000, $this->getNewQuestion());
		$this->broadcast(array('action' => 'allPlayersAllreadyResponde'));
	}

	/** @Public Interface for WS **/
	public function getNewQuestion($player, $id){
		$this->getLogger()->info("new question");
		if ($this->count() == 0) {
			$this->getLogger()->info("room empty");
			$this->notificationStatus();
			return;
		}

		if ($this->isOver()) {
			$this->broadcast(array('action' => 'gameOver'));
		} else {
			// reset question 
			$this->question = null;
			$this->answers = array();
			$this->playersReadyToPlay = 0;
		 	// load and increment this will close room if open
		 	try {
		 		$this->broadcast(array('action' => 'newQuestion', 'data' => $this->getQuestion()->toWs()));
		 	} catch (\Exception $e) {
		 		if (!empty($id) && !empty($player)) {
		 			$player->callError($id, $this->getRoomId(), $e->getMessage());
		 			return;
		 		}
		 	}
		}
		if (!empty($id) && !empty($player)) {
 			$player->callResult($id, array());
 		}
	}

	public function setAnswer(ConnectionInterface $player, $id, $params) {
		$this->getLogger()->info("set answer");
		try {
			$result = $this->addAnswer($player->getSessionId(), $params['answer'], $params['hash']);
			$gameMode = $this->getGameMode();

			if (!empty($result)) {
		        if ($result[2]) { // correct
		            $score = $gameMode->getScoreForCorrectAnswer($result[3]);
		        } else { // bad
		            $score = $gameMode->getScoreForBadAnswer();
		        }
		        $player->addScore($score);
			}

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
	            $this->broadcast($event);
			}

			$player->callResult($id, array('action' => 'answerResult', 
										 'res' => $result[2], 
										 'position' => $params['answer']));
			if ($this->isAllPlayersAlreadyResponde()) {
				if ($this->isOver()) {
					$this->onGameOver();
				} else {
					$this->onAllAlreadyResponde();
				}
			}

		}
		catch (\Exception $e) {
			$player->callError($id, $this->getRoomId(), $e->getMessage());
		}
	}

	public function listPlayers(ConnectionInterface $player, $id) {
        $player->callResult($id, $this->getPlayers());	
	}

	public function setReadyToPlay(ConnectionInterface $player, $id, array $params) {
		$this->getLogger()->info("player set ready");
        try {
        	if ($params['hash'] !== $this->getQuestion()->hash) throw new Exception("Hash not valid", 1);
            ++$this->playersReadyToPlay;
            if ($this->isAllPlayersReady()) {
                $this->broadcast(array('action' => 'allPlayersReady'));
            }
        } catch (Exception $e) {
             $player->callError($id, $this->getRoomId(), $e->getMessage());
        }
    }

    public function setPlayerConfig(ConnectionInterface $player, $id, $config) {
    	$this->getLogger()->info("player set configuration");
        if (!empty($config)) {
            $oldName = $player->getName();
            $player->setName($config['name']);
            $player->setOthers($config);

            $player->callResult($id, array('msg' => "Name changed"));

            $playersList = $this->getPlayers();
            $this->broadcast(array('action' => 'playerConfigChange', 
                                       'data'   => $playersList));
        }
    }

}
