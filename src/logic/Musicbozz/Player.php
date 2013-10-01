<?php

namespace Musicbozz;
use \Ratchet\ConnectionInterface as Conn;
use \Ratchet\Wamp\WampConnection;

class Player extends WampConnection {
	private $name;
	private $playerId;
	private $others;
	private $score;
	private $master;

	public function __construct (Conn $conn, $name = "player", $others = array(), $score = 0) {
		parent::__construct($conn);
		$this->name = $name;
		$this->others = $others;
		$this->score = $score;
		$this->master = false;
		$this->playerId = null;
	}

	public function getPlayerId() {
		return !empty($this->playerId) ? $this->playerId : $this->WAMP->sessionId;
	}
	public function setPlayerId($userId) {
		$this->playerId = $userId;
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
		return array(
			'name' 	=> $this->getName(),
			'score' => $this->getScore(),
			'master' => $this->isMaster(),
			'others' => $this->getOthers(),
			'id' 	=> $this->getPlayerId()
			);
	}

	public function toPersistence() {
		return array(
			'name' 	=> $this->getName(),
			'others' => $this->getOthers(),
			'id' 	=> $this->getPlayerId()
		);
	}
}
