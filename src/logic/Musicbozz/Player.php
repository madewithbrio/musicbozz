<?php

namespace Musicbozz;
use \Ratchet\ConnectionInterface as Conn;
use \Ratchet\Wamp\WampConnection;

class Player extends WampConnection {
	private $name;
	private $username;
	private $others;
	private $score;
	private $master;

	public function __construct (Conn $conn, $name = "player", $others = array(), $score = 0) {
		parent::__construct($conn);
		$this->name = $name;
		$this->others = $others;
		$this->score = $score;
		$this->master = false;
	}

	public function getSessionId() {
		return $this->WAMP->sessionId;
	}

	public function setName($name) { $this->name = $name; }
	public function addScore($score) { $this->score += $score; }

	public function getName() { return $this->name; }
	public function getScore() { return $this->score; }
	public function getOthers() { return $this->others; }

	public function setMaster($master) { $this->master = $master; }
	public function setOthers($others) { $this->others = $others; }
	public function isMaster() { return $this->master; }
	
	public function toWs() {
var_dump($this->isMaster());
		return array(
			'name' 	=> $this->getName(),
			'score' => $this->getScore(),
			'master' => $this->isMaster(),
			'others' => $this->getOthers(),
			'id' 	=> $this->getSessionId()
			);
	}
}
