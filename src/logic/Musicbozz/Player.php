<?php

namespace Musicbozz;
use \Ratchet\ConnectionInterface as Conn;
use \Ratchet\Wamp\WampConnection;

class Player extends WampConnection {
	private $name;
	private $username;
	private $avatar;
	private $score;
	private $master;

	public function __construct (Conn $conn, $name = "player", $avatar = null, $score = 0) {
		parent::__construct($conn);
		$this->name = $name;
		$this->avatar = $avatar;
		$this->score = $score;
	}

	public function getSessionId() {
		return $this->WAMP->sessionId;
	}

	public function setName($name) { $this->name = $name; }
	public function addScore($score) { $this->score += $score; }

	public function getName() { return $this->name; }
	public function getScore() { return $this->score; }
	public function getAvatar() { return $this->avatar; }

	public function setMaster($bool) { $this->master = $master; }
	public function setAvatar($avatar) { $this->avatar = $avatar; }
	public function isMaster() { return $this->master; }
	
	public function toWs() {
		return array(
			'name' 	=> $this->getName(),
			'score' => $this->getScore(),
			'master' => $this->isMaster(),
			'avatar' => $this->getAvatar(),
			'id' 	=> $this->getSessionId()
			);
	}
}