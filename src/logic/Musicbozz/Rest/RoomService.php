<?php

namespace Musicbozz\Rest;

use Musicbozz\Persistence\Room;

class RoomService extends ServiceImplementation {
	public function getItem() {
		if (preg_match('@/room/(.+)@', $this->getPath(), $match)) {
			$room = Room::get('room/' . $match[1]);
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