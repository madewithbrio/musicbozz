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
	public function add(ConnectionInterface $player) {
		if ($this->count() == 0) {
			$player->setMaster(true);
			$this->questionNumber = 0;
		}

		// room closed
		if ($this->questionNumber != 0 || $this->count() >= 4) {
			$player->close();
			return;
		}
		parent::add($player);
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

	public function addAnswer(ConnectionInterface $player, $answer) {
		foreach ($this->answers as $_answer) {
			if ($_answer[0] == $player->getSessionId()) return;
		}

		$isCorrect 			= $this->getQuestion()->isCorrectAnswer($answer);
		$data 				= array($player->getSessionId(), $answer, $isCorrect);
		$this->answers[] 	= $data;
		$position 			= (null === $answer) ? 5 : sizeof($this->answers);
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

	public function getGameMode() {
		//if (null === $this->gameMode) {
			if ($this->questionNumber < 5) {
				$this->gameMode = GameMode::factory('Standard');
			} else if ($this->questionNumber < 10) {
				$this->gameMode = GameMode::factory('Normal');
			} else {
				$this->gameMode = GameMode::factory('Speed');
			}
		//}
		return $this->gameMode;
	}
}