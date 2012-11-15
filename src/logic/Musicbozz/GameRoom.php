<?php

namespace Musicbozz;
use \Ratchet\ConnectionInterface;
use \Ratchet\Wamp\Topic;

class GameRoom extends Topic
{
	private $questionNumber = 0;
	private $question;
	private $answers;
	private $playersReadyToPlay = 0;
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
		$this->playersReadyToPlay = 0;

		++$this->questionNumber;
		return $this->getQuestion();
	}

	public function addAnswer(ConnectionInterface $player, $answer) {
		$isCorrect 			= $this->getQuestion()->isCorrectAnswer($answer);
		$data 				= array($player->getSessionId(), $answer, $isCorrect);
		$this->answers[] 	= $data;
		$position 			= (null === $answer) ? 5 : sizeof($this->answers);
		return array_merge($data, $position);
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

	public function getGameMode() {
		if (null === $this->gameMode) {
			$this->gameMode = GameMode::factory('Standard');
		}
		return $this->gameMode;
	}
}