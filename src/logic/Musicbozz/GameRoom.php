<?php

namespace Musicbozz;
use \Ratchet\ConnectionInterface;
use \Ratchet\Wamp\Topic;
use \Exception;
use \Musicbozz\Persistence\Player as PlayerPersistence;
use \Musicbozz\Persistence\Leaderboard;
use \Musicbozz\Persistence\Leaderboard\Type as LeaderboardType;
use \Sapo\Services\Puny;

class GameRoom extends Topic
{
	const NUMBER_QUESTIONS = 20;
	const SITEURL = 'vmdev-musicbozz.vmdev.bk.sapo.pt';
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
    		$this->logger = \Logger::getLogger(__CLASS__);
    	}
    	return $this->logger;
	}

	/**
	 * @ override
	 */
	public function add(ConnectionInterface $player) {
		$this->log("number of players in root: " . $this->count());
		
		// room closed
		if (!$this->isOpen()) {
			$this->getLogger()->error("room closed");
			$player->close();
			return;
		}

		parent::add($player);
		if ($this->count() == 1) {
			$this->setMaster($player);
			$this->questionNumber = 0;
		}

		$this->log("player have join");
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
		$this->log("player have leave");
		$this->broadcast(array('action' => 'playerLeave', 'data' => $this->getPlayers()));
		$this->notificationStatus();
	}

	public function log($msg) {
		$this->getLogger()->debug(sprintf("[%s] %s", $this->getRoomId(), $msg));
	}

	protected function getPlayers () {
		$result = array_fill(0,4,array()); $i = 0;
		foreach ($this as $player) {
		    $result[$i++] = $player->toWs();
		}
		return $result;
	}

	protected function getPlayersWithRank() {
		$leaderboad_type = LeaderboardType::factory($this->getGameRoomType());
		$players = $this->getPlayers();
		usort($players, function($p1, $p2) {
			if (!isset($p1['score'])) return 1;
			if (!isset($p2['score'])) return -1;
			if ($p1['score'] == $p2['score']) return 0;
			return ($p1['score'] < $p2['score']) ? 1 : -1;
		});

		foreach ($players as $key => &$player) {
			if (!empty($player)) {
				$player['gamerank'] = $key+1;
				$player['rank'] = Leaderboard::getRank($leaderboad_type, $player['id']);
				$player['topscore'] = Leaderboard::getScore($leaderboad_type, $player['id']);
			}
		}
		
		return $players;
	}

	protected function setMaster(ConnectionInterface $player) {
		$this->log(sprintf("player %s was set as master", $player->getPlayerId()));
		$player->setMaster(true);
		//$player->event($this->getId(), array('action' => 'setMaster'));
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
			
			'isPublic'=> $this->isPublic(),
			'isPrivate' => $this->isPrivate(),
			'isAlone' => $this->isAlone(),
			
			'id' 	  => $this->getRoomId(),
			//'question' => $this->question
			);
	}

	protected function isPublic() {
		return (preg_match('/^room\/(\d+)$/', $this->getRoomId()));
	}

	protected function isPrivate() {
		return (preg_match('/^room\/(.*[a-zA-Z]+.*)$/', $this->getRoomId()));
	}
	
	protected function isAlone() {
		return strpos($this->getRoomId(), 'alone') === 0;
	}
	
	protected function getGameRoomType() {
		if ($this->isAlone()) return 'alone';
		if ($this->isPrivate()) return 'roomprivate';
		return 'roompublic';
	}
	
	protected function getShareUrl() {
		if ($this->isAlone()) return;
		if (preg_match('/^room\/(.+)$/', $this->getRoomId(), $match)) {
			$url = sprintf('http://%s#room=%s', self::SITEURL, $match[1]);
			return Puny::punify($url);
		} 
	}
	
	protected function isAllPlayersReady() {
		return $this->playersReadyToPlay == $this->count();
	}

	protected function isAllPlayersAlreadyResponde() {
		$this->log("answers: " . sizeof($this->answers) . " players: ". $this->count());
		return sizeof($this->answers) == $this->count();
	}

	protected function isOver() {
		return ($this->questionNumber > self::NUMBER_QUESTIONS);
	}

	protected function isLastQuestion() {
		return ($this->questionNumber == self::NUMBER_QUESTIONS);
	}

	protected function isOpen() {
		return ($this->questionNumber === 0 &&  $this->count() < 4);
	}

	protected function notificationStatus() {
		\Sapo\Redis::getInstance()->hset('rooms', $this->getRoomId(), serialize($this->getStatus()));
	}
	
	protected function storeScore() {
		$leaderboad_type = LeaderboardType::factory($this->getGameRoomType());
		foreach ($this as $player) {
			PlayerPersistence::save($player->getPlayerId(), $player->toPersistence());
			Leaderboard::save($leaderboad_type, $player->getScore(), $player->getPlayerId());
		}
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
			if ($this->isAlone()) {
				$this->gameMode = GameMode::factory('Alone');
			} else {
				$this->gameMode = GameMode::factory('Standard');
			}
		}
		return $this->gameMode;
	}

	/** @Triggers **/
	protected function onGameOver() {
		$this->question = null;
		$this->questionNumber = 0;

		// save score
		$this->storeScore();

		$playersList = $this->getPlayersWithRank();
		$this->broadcast(array('action' => 'gameOver','data'   => $playersList));
		$this->notificationStatus();
	}
	
	protected function onAllAlreadyResponde() {
		$playersList = $this->getPlayers();
		$this->broadcast(array('action' => 'allPlayersAlreadyResponde','data'   => $playersList));
	}

	/** @Public Interface for WS **/
	public function getNewQuestion($player, $id){
		$this->log(sprintf("new question asked by %s", $player->getPlayerId()));
		if (!$player->isMaster()) {
			$this->log(sprintf("player %s is not master", $player->getPlayerId()));
			$player->callError($id, $this->getRoomId(), "you are not master in this room");
			return;
		}

		if ($this->isOver()) {
			$this->onGameOver();
			$player->callError($id, $this->getRoomId(), "game allready over");
			return;
			
		} else {
			// reset question 
			$this->question = null;
			$this->answers = array();
			$this->playersReadyToPlay = 0;
		 	// load and increment this will close room if open
		 	try {
		 		$this->broadcast(array('action' => 'newQuestion', 'data' => $this->getQuestion()->toWs()));
		 		$this->notificationStatus();
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
		$this->log(sprintf("set answer for player %s", $player->getPlayerId()));
		try {
			$timespend = microtime(true) - $this->getQuestion()->getTimer();
			$result = $this->addAnswer($player->getPlayerId(), $params['answer'], $params['hash']);
			$gameMode = $this->getGameMode();

			if (!empty($result)) {
		        if ($result[2]) { // correct
		            $score = $gameMode->getScoreForCorrectAnswer($result[3], $timespend);
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
				if ($this->isLastQuestion()) {
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
		$this->log(sprintf("player %s set ready to play", $player->getPlayerId()));
        try {
        	if ($params['hash'] !== $this->getQuestion()->hash) throw new Exception("Hash not valid", 1);
            ++$this->playersReadyToPlay;
            if ($this->isAllPlayersReady()) {
            	$this->getQuestion()->setTimer(microtime(true));
                $this->broadcast(array('action' => 'allPlayersReady'));
            }
        } catch (Exception $e) {
             $player->callError($id, $this->getRoomId(), $e->getMessage());
        }
    }

    public function forcePlay(ConnectionInterface $player, $id, array $params) {
		$this->log(sprintf("player %s force play", $player->getPlayerId()));
        try {
        	if (!$player->isMaster()) throw new Exception("player is not master", 1);
        	if ($params['hash'] !== $this->getQuestion()->hash) throw new Exception("Hash not valid", 1);
			$this->broadcast(array('action' => 'allPlayersReady'));
        } catch (Exception $e) {
             $player->callError($id, $this->getRoomId(), $e->getMessage());
        }
    }

    public function setPlayerConfig(ConnectionInterface $player, $id, $config) {
    	$this->log(sprintf("player %s set configuration", $player->getPlayerId()));
        if (!empty($config)) {
            $oldName = $player->getName();
            $player->setName($config['name']);
			$player->setPlayerId($config['facebookId']);
            $player->setOthers($config);

			$result = array();
			if ($player->isMaster()) $result['url'] = $this->getShareUrl();
            $player->callResult($id, $result);

            $playersList = $this->getPlayers();
            $this->broadcast(array('action' => 'playerConfigChange', 
                                       'data'   => $playersList));
            $this->notificationStatus();
        }
    }

}
