<?php

namespace Musicbozz;
class GameMode {

	private function __construct() {}
	public static function factory($mode) {
		$reflectionClass = new \ReflectionClass("Musicbozz\GameMode_".$mode);
		print "fvrgb";
		if (!$reflectionClass->isSubclassOf("Musicbozz\GameMode_Workflow")) throw new \Exception("class ".$mode." is not GameMode_Workflow", -1);
		if (!$reflectionClass->IsInstantiable()) throw new \Exception("class ".$mode." is not instantiable", -1);
		return $reflectionClass->newInstance();
	}
}

interface GameMode_Workflow {
	public function isBroadcastPlayerHaveAnswer();
	public function isBroadcastPlayerAnswer();
	public function isBroadcastPlayerQuestionScore();
	public function isBroadcastPlayerTotalScore();

	public function getScoreForCorrectAnswer($position);
	public function getScoreForBadAnswer();
}

final class GameMode_Standard implements GameMode_Workflow {
	public function isBroadcastPlayerHaveAnswer() { return true; }
	public function isBroadcastPlayerAnswer() { return false; }
	public function isBroadcastPlayerQuestionScore() { return true; }
	public function isBroadcastPlayerTotalScore() { return true; }

	public function getScoreForCorrectAnswer($position){
		switch($position) {
			case 1: return 50;
			case 2: return 40;
			case 3: return 30;
			case 4: return 20;
			default: return 0;
		}		
	}
	public function getScoreForBadAnswer() {
		return -20;
	}
}

final class GameMode_Normal implements GameMode_Workflow {

	public function isBroadcastPlayerHaveAnswer() { return true; }
	public function isBroadcastPlayerAnswer() { return true; }
	public function isBroadcastPlayerQuestionScore() { return true; }
	public function isBroadcastPlayerTotalScore() { return true; }

	public function getScoreForCorrectAnswer($position) {
		return 200;
	}

	public function getScoreForBadAnswer() {
		return -200;
	}
}

final class GameMode_Speed implements GameMode_Workflow {

	public function isBroadcastPlayerHaveAnswer() { return true; }
	public function isBroadcastPlayerAnswer() { return false; }
	public function isBroadcastPlayerQuestionScore() { return true; }
	public function isBroadcastPlayerTotalScore() { return true; }

	public function getScoreForCorrectAnswer($position) {
		switch($position) {
			case 1: return 500;
			case 2: return 300;
			case 3: return 200;
			case 4: return 100;
			default: return 0;
		}
	}

	public function getScoreForBadAnswer() {
		return 0;
	}
}