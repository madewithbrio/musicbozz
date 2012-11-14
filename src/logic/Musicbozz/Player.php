<?php

namespace Musicbozz;
use \Ratchet\ConnectionInterface as Conn;
use \Ratchet\Wamp\WampConnection;

class Player extends WampConnection {
	private $name;
	private $score;

	public function __construct (Conn $conn, $name = "player", $score = 0) {
		parent::__construct($conn);
		$this->name = $name;
		$this->score = $score;
	}

	public function getSessionId() {
		return $this->WAMP->sessionId;
	}

	public function setName($name) { $this->name = $name; }
	public function addScore($soce) { $this->score += $score; }

	public function getName() { return $this->name; }
	public function getScore() { return $this->score; }
}