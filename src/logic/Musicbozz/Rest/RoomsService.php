<?php

namespace Musicbozz\Rest;

use Sapo\Rest\ServiceImplementation;
use Musicbozz\Question;
use Musicbozz\Question_Type;

class RoomsService extends ServiceImplementation {
	public function getItem() {
		$publicRoomIds = array();
		for ($i = 1; $i <= 10; $i++) {
			$publicRoomIds[] = 'room/'.$i;
		}
		$_rooms = \Sapo\Redis::getInstance()->hmget('rooms', $publicRoomIds);
		$rooms = array_fill(0,9, null);
		for($i = 0, $j = count($_rooms); $i < $j; $i++) {
			if (!empty($_rooms[$i])) $rooms[$i] = unserialize($_rooms[$i]);
		}
		$this->sendResponse($rooms);
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