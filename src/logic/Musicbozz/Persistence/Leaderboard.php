<?php

namespace Musicbozz\Persistence;
use \Sapo\Redis;
use \Exception;
use Musicbozz\Persistence\Leaderboard\Type as LeaderboardType;

class Leaderboard {
	public static function save(LeaderboardType $leaderboard_type, $score, $playerId) {
		$savedScore = self::getScore($leaderboard_type, $playerId);
		if ($score > $savedScore) {
			Redis::getInstance()->zadd((string) $leaderboard_type, $score, $playerId);
		}
	}

	public static function getScore(LeaderboardType $leaderboard_type, $playerId) {
		return Redis::getInstance()->zscore((string) $leaderboard_type, $playerId);
	}

	public static function getTop(LeaderboardType $leaderboard_type, $number) {
		return Redis::getInstance()->zrevrangebyscore((string) $leaderboard_type, '+inf', '-inf', 'limit', 0, $number);
	}
}

