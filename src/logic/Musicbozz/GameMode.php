<?php

namespace Musicbozz;
class GameMode {

	private function __construct() {}
	public static function factory($mode) {
		$reflectionClass = new ReflectionClass("GameMode_".$mode);
		if (!$reflectionClass->isSubclassOf("GameMode_Workflow")) throw new Exception("class ".$mode." is not GameMode_Workflow", -1);
		if (!$reflectionClass->IsInstantiable()) throw new Exception("class ".$mode." is not instantiable", -1);
		return $reflectionClass->newInstance();
	}
}

interface GameMode_Workflow {
	public function isBoardcastPlayerHaveAnswer();
	public function isBoardcastPlayerAnswer();
	public function getScoreForCorrectAnswer($position);
	public function getScoreForBadAnswer();
}

final class GameMode_Normal implements GameMode_Workflow {

	public function isBoardcastPlayerHaveAnswer() { return true; }
	public function isBoardcastPlayerAnswer() { return true; }
	public function getScoreForCorrectAnswer($position) {
		return 200;
	}

	public function getScoreForBadAnswer() {
		return -200;
	}
}

final class GameMode_Speed implements GameMode_Workflow {

	public function isBoardcastPlayerHaveAnswer() { return true; }
	public function isBoardcastPlayerAnswer() { return false; }
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