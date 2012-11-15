<?php

namespace Musicbozz;
use \Ratchet\ConnectionInterface;
use \Ratchet\Wamp\Topic;

class GameRoom extends Topic
{
	private $questionNumber = 0;
	private $question;
	private $answers;
	private $gameMode;

	/**
	 * @ override
	 */
	public function add(ConnectionInterface $conn) {
		if ($this->count() >= 4){
			$conn->close();
			return;
		}
		parent::add($conn);
	}


	public function getQuestion() {
		if (null === $this->question) {
			$this->question = Question::factory(Question_Type::getRandom(), $this->questionNumber);
		}
		return $this->question;
	}

	public function getNewQuestion(){
		$this->question = null;
		$this->answers = array();
		++$this->questionNumber;
		return $this->getQuestion();
	}

	public function addAnswer(ConnectionInterface $player, $answer) {
		$this->answers[] = array($player->getSessionId(), $answer);
	}

	public function isAllPlayersAllreadyResponde() {
		return sizeof($this->answers) == $this->count();
	}

	public function getGameMode() {
		if (null === $this->gameMode) {
			$this->gameMode = GameMode::factory('normal');
		}
		return $this->gameMode;
	}
}