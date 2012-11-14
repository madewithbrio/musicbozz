<?php

namespace Musicbozz;
use \Ratchet\ConnectionInterface;
use \Ratchet\Wamp\Topic;

class GameRoom extends Topic
{

	private $question;

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
			$this->question = Question::factory(Question_Type::getRandom());
		}
		return $this->question;
	}

	public function getNewQuestion(){
		$this->question = null;
		return $this->getQuestion();
	}
}