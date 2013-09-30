<?php

namespace Musicbozz\Rest;

use Sapo\Rest\ServiceImplementation;
use Musicbozz\Question;
use Musicbozz\Question_Type;

class RoomService extends ServiceImplementation {
	public function getItem() {
		if (preg_match('@/room/(.+)@', $this->getPath(), $match)) {
			$room = \Sapo\Redis::getInstance()->hget('rooms', 'room/' . $match[1]);
			if (!empty($room)) $room = unserialize($room);
			$this->sendResponse($room, 'room');		
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