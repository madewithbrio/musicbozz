<?php

namespace Musicbozz;
use \Ratchet\ConnectionInterface;
use \Ratchet\Wamp\Topic;
use \Exception;
use \Musicbozz\Persistence\Room as RoomPersistence;
use \Musicbozz\Persistence\Player as PlayerPersistence;
use \Musicbozz\Persistence\Leaderboard;
use \Musicbozz\Persistence\Leaderboard\Type as LeaderboardType;
use \Sapo\Services\Puny;

class GameRoom extends Topic
{
	const NUMBER_QUESTIONS = 5;
	const SITEURL = 'vmdev-musicbozz.vmdev.bk.sapo.pt';

	private $waiting = true;
	private $questionsHash = array();
	private $question = null;
	private $answers = array();
	private $playersReadyToPlayMusic= 0;
	private $gameMode;
	private $readyToPlay = array();
	private $logger;

	public function __construct($topicId) {
		parent::__construct($topicId);
		RoomPersistence::delete($this->getRoomId());
	}

	public function __destruct() {
		RoomPersistence::delete($this->getRoomId());
	}

	private function getLogger() {
    	if (null === $this->logger) {
    		$this->logger = \Logger::getLogger(__CLASS__);
    	}
    	return $this->logger;
	}

	private function resetRoom() {
		$this->waiting = true;
		$this->questionsHash = array();
		$this->readyToPlay = array();
		$this->resetQuestion();
	}

	private function resetQuestion() {
		$this->question = null;
		$this->playersReadyToPlayMusic= 0;
		$this->answers = array();
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
			$this->resetRoom();
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
			$this->resetRoom();
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
			if (!in_array($player->getPlayerId(), $this->readyToPlay)) continue;
		    $result[$i++] = $player->toWs();
		}
		return $result;
	}

	protected function getPlayersWithRank() {
		$leaderboad_type = LeaderboardType::factory('common'); //$this->getGameRoomType());
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
			'isWaiting'=> $this->isWaiting(),
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
	
	protected function isAllPlayersReadyToStartMusic() {
		return $this->playersReadyToPlayMusic == sizeof($this->readyToPlay);
	}

	protected function isAllPlayersAlreadyResponde() {
		$this->log("answers: " . sizeof($this->answers) . " players: ". $this->count());
		return sizeof($this->answers) == $this->count();
	}

	protected function isOver() {
		return ($this->isLastQuestion() && $this->isWaiting());
	}

	protected function isOpen() {
		return ($this->waiting && $this->count() < 4);
	}

	protected function isWaiting() {
		return $this->waiting;
	}
	
	protected function isLastQuestion() {
		return (sizeof($this->questionsHash) >= self::NUMBER_QUESTIONS);
	}

	protected function notificationStatus() {
		RoomPersistence::save($this->getRoomId(), $this->getStatus());
	}
	
	protected function storeScore() {
		$leaderboad_type = LeaderboardType::factory('common'); //$this->getGameRoomType());
		foreach ($this as $player) {
			PlayerPersistence::save($player->getPlayerId(), $player->toPersistence());
			Leaderboard::save($leaderboad_type, $player->getScore(), $player->getPlayerId());
		}
	}
	
	protected function getQuestion() {
		if (null === $this->question) {
			$this->question = Question::factory(Question_Type::getRandom(), 
												sizeof($this->questionsHash)+1);
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
			//if ($this->isAlone()) {
			$this->gameMode = GameMode::factory('Standard');
			//} else {
			//	$this->gameMode = GameMode::factory('Standard');
			//}
		}
		return $this->gameMode;
	}

	/** @Triggers **/
	protected function onGameOver() {
		$this->log("Game is over");
		$playersList = $this->getPlayersWithRank();
		
		// save score
		$this->storeScore();
		
		$this->readyToPlay = array();
		$this->resetQuestion();
		$this->waiting = true;
		
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

		if ($this->isWaiting()) {
			foreach ($this as $_player) {
				if (!in_array($_player->getPlayerId(), $this->readyToPlay)) {
					$this->log(sprintf("disconnect player %s",$_player->getPlayerId()));
					$_player->close(); // disconect player
				}
			}
		}

		if ($this->isOver()) {
			$this->onGameOver();
			$player->callError($id, $this->getRoomId(), "game allready over");
			return;		
		} else {
			$this->waiting = false;
			// reset question 
			$this->resetQuestion();
			
		 	// load and increment this will close room if open
		 	try {
		 		do {
		 			$question = $this->getQuestion();
		 		} while (!in_array($question->getHash(), $this->questionsHash));
		 		$this->questionsHash[] = $question->getHash();

		 		$this->broadcast(array('action' => 'newQuestion', 'data' => $question->toWs()));
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

	public function setReadyToPlayMusic(ConnectionInterface $player, $id, array $params) {
		$this->log(sprintf("player %s set ready to play", $player->getPlayerId()));
        try {
        	if ($params['hash'] !== $this->getQuestion()->getHash()) 
        		throw new Exception("Hash not valid", 1);
            ++$this->playersReadyToPlayMusic;
			$this->log("players " . $this->playersReadyToPlayMusic);
            if ($this->isAllPlayersReadyToStartMusic()) {
            	$this->getQuestion()->setTimer(microtime(true));
                $this->broadcast(array('action' => 'allPlayersReadyToPlayMusic'));
            }
        } catch (Exception $e) {
             $player->callError($id, $this->getRoomId(), $e->getMessage());
        }
    }

    public function forcePlay(ConnectionInterface $player, $id, array $params) {
		$this->log(sprintf("player %s force play", $player->getPlayerId()));
        try {
        	if (!$player->isMaster()) 
        		throw new Exception("player is not master", 1);
        	if ($params['hash'] !== $this->getQuestion()->getHash()) 
        		throw new Exception("Hash not valid", 1);
			$this->broadcast(array('action' => 'allPlayersReady'));
        } catch (Exception $e) {
             $player->callError($id, $this->getRoomId(), $e->getMessage());
        }
    }
	
	public function setRematch(ConnectionInterface $player, $id) {
		$this->log(sprintf("player %s set rematch", $player->getPlayerId()));
		if (!in_array($player->getPlayerId(), $this->readyToPlay)) {
			$this->questionNumber = 0; // reset question counter, game change state from over to new
			$this->readyToPlay[] = $player->getPlayerId();
			$player->setScore(0);
			$this->broadcast(array('action' => 'readyToPlay', 'data'   => $this->getPlayers()));
			$this->notificationStatus();
			$player->callResult($id, array());
		} else {
			$player->callError($id, $this->getRoomId(), 'you have already set rematch');
		}
	}

    public function setPlayerConfig(ConnectionInterface $player, $id, $config) {
    	$this->log(sprintf("player %s set configuration", $player->getPlayerId()));
        if (!empty($config)) {
        	$oldName = $player->getName();
            $player->setName($config['name']);
			$player->setPlayerId($config['facebookId']);
            $player->setOthers($config);

        	foreach ($this as $_conn) {
        		if ($_conn->getSessionId() === $player->getSessionId()) continue;
        		if ($_conn->getPlayerId() === $player->getPlayerId()) {
        			$this->log(sprintf("player %s already in room", $player->getPlayerId()));
        			$player->callError($id, $this->getRoomId(), "you are already join");
        			$player->close();
        			return;
        		}
        	}
           
		   	if (!in_array($player->getPlayerId(), $this->readyToPlay)) {
		   		$this->readyToPlay[] = $player->getPlayerId();
			}
			
			$result = array();
			if ($player->isMaster()) {
				$url = $this->getShareUrl();
				$result['url'] = (string) $url;
				$this->log("room share url: ". $url);
			}
            $player->callResult($id, $result);

            $this->broadcast(array('action' => 'readyToPlay', 'data'   => $this->getPlayers()));
            $this->notificationStatus();
        }
    }

}
