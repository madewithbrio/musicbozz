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

	public function __construct($topicId) {
		parent::__construct($topicId);
		\Sapo\Redis::getInstance()->hdel('rooms', $this->getRoomId());
	}

	public function __destruct() {
		\Sapo\Redis::getInstance()->hdel('rooms', $this->getRoomId());
	}

	/**
	 * @ override
	 */
	public function add(ConnectionInterface $player) {
		if ($this->count() == 0) {
			$this->setMaster($player);
			$this->questionNumber = 0;
		}

		// room closed
		if ($this->questionNumber != 0 || $this->count() >= 4) {
			$player->close();
			return;
		}

		parent::add($player);
        $this->notificationStatus();
	}

	public function remove(ConnectionInterface $player) {
		parent::remove($player);
		foreach ($this as $player) { // set player master
			$this->setMaster($player);
			break;
		}
		$this->broadcast(array('action' => 'playerLeave'));
		$this->notificationStatus();
	}

	public function setMaster(ConnectionInterface $player) {
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
			'question' => $this->question
			);
	}

	protected function isPublic() {
		return (preg_match('/^room\/(\d+)$/', $this->getRoomId()));
	}

	protected function notificationStatus() {
		print "notify redis\n";
		\Sapo\Redis::getInstance()->hset('rooms', $this->getRoomId(), serialize($this->getStatus()));
	}


	public function getQuestion() {
		if (null === $this->question) {
			$this->question = Question::factory(Question_Type::getRandom(), ++$this->questionNumber);
		}
		return $this->question;
	}

	public function getNewQuestion(){
		$this->question = null;
		$this->answers = array();
		$this->playersReadyToPlay = 0;

		return $this->getQuestion();
	}

	public function addAnswer(ConnectionInterface $player, $answer, $hash) {
		if ($hash !== $this->getQuestion()->hash) throw new Exception("Hash not valid", 1);
		
		$position = 1;
		foreach ($this->answers as $_answer) {
			if ($_answer[0] == $player->getSessionId()) throw new Exception("Already answer", 1);
			if ($_answer[2]) { $position++; }
		}

		$isCorrect 			= $this->getQuestion()->isCorrectAnswer($answer);
		$data 				= array($player->getSessionId(), $answer, $isCorrect);
		$this->answers[] 	= $data;
		if (!$isCorrect || null === $answer) { $position = 5; }

		$data[] 			= $position;
		return $data;
	}

	public function incPlayersReady() {
		++$this->playersReadyToPlay;
	}

	public function isAllPlayersReady() {
		return $this->playersReadyToPlay == $this->count();
	}

	public function isAllPlayersAllreadyResponde() {
		return sizeof($this->answers) == $this->count();
	}

	public function isOver() {
		return ($this->questionNumber > 20);
	}

	public function getGameMode() {
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
}