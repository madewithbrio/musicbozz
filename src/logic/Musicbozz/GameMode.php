<?php

namespace Musicbozz;
class GameMode {

	private function __construct() {}
	public static function factory($mode) {
		$reflectionClass = new \ReflectionClass("Musicbozz\GameMode_".$mode);
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

	public function getScoreForCorrectAnswer($position, $timespend);
	public function getScoreForBadAnswer();
}


final class GameMode_Normal implements GameMode_Workflow {

	public function isBroadcastPlayerHaveAnswer() { return true; }
	public function isBroadcastPlayerAnswer() { return true; }
	public function isBroadcastPlayerQuestionScore() { return true; }
	public function isBroadcastPlayerTotalScore() { return true; }

	public function getScoreForCorrectAnswer($position, $timespend) {
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

	public function getScoreForCorrectAnswer($position, $timespend) {
		switch($position) {
			case 1: return 500;
			case 2: return 300;
			case 3: return 200;
			case 4: return 100;
			default: return 0;
		}
	}

	public function getScoreForBadAnswer() {
		return -100;
	}
}


final class GameMode_Standard implements GameMode_Workflow {

	public function isBroadcastPlayerHaveAnswer() { return true; }
	public function isBroadcastPlayerAnswer() { return true; }
	public function isBroadcastPlayerQuestionScore() { return true; }
	public function isBroadcastPlayerTotalScore() { return true; }

	public function getScoreForCorrectAnswer($position, $timespend) {
		$score = round((500/30000) * (30000 - ($timespend*1000)),0);
		return $score < 0 ? 0 : $score;
	}

	public function getScoreForBadAnswer() {
		return -100;
	}
}