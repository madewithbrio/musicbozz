<?php

namespace Musicbozz\Rest;

use Sapo\Rest\ServiceImplementation;
use \Musicbozz\Persistence\Player as PlayerPersistence;
use \Musicbozz\Persistence\Leaderboard;
use \Musicbozz\Persistence\Leaderboard\Type as LeaderboardType;

class LeaderboardService extends ServiceImplementation {
	public function getItem() {
		if (preg_match('@top/(alone|private|public)@', $this->getPath(), $match)) {
			$type = LeaderboardType::factory($match[1]);
			$top = Leaderboard::getTop($type, 10);
			var_dump($top); die;
		}
	}

	public function existsItem() {
		throw new Exception("not implemented");
	}

	public function createItem() {
		throw new Exception("not implemented");
	}

	public function saveItem() {
		throw new Exception("not implemented");
	}

	public function deleteItem() {
		throw new Exception("not implemented");
	}
}