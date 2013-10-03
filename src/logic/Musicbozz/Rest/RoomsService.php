<?php

namespace Musicbozz\Rest;

use Sapo\Rest\ServiceImplementation;
use Musicbozz\Question;
use Musicbozz\Question_Type;

class RoomsService extends ServiceImplementation {
	public function getItem() {
		$filterPlayers = filter_input(INPUT_GET, 'onlyWithPlayers', FILTER_VALIDATE_BOOLEAN, array('flags' => FILTER_NULL_ON_FAILURE));
		$filterOpen = filter_input(INPUT_GET, 'onlyOpen', FILTER_VALIDATE_BOOLEAN, array('flags' => FILTER_NULL_ON_FAILURE));
		$publicRoomIds = array();
		for ($i = 1; $i <= 10; $i++) {
			$publicRoomIds[] = 'room/'.$i;
		}
		$_rooms = \Sapo\Redis::getInstance()->hmget('rooms', $publicRoomIds);
		$rooms = array();
		for($i = 0, $j = count($_rooms); $i < $j; $i++) {
			if (!empty($_rooms[$i])){
				$room = unserialize($_rooms[$i]);
				$room['name'] = str_replace('room/', '', $room['id']);
				
			} else {

				$room = array(
					'players' 	=> array(),
					'isOpen' 	=> true,
					'isOver' 	=> false,
					'isPublic' 	=> 1,
					'isPrivate' => 0,
					'isAlone' 	=> false,
					'name' 		=> str_replace('room/', '', $publicRoomIds[$i]),	
					'id' 		=> $publicRoomIds[$i]
				);
			}
			if ($filterPlayers && sizeof($room['players']) === 0) continue;
			if ($filterOpen && !$room['isOpen']) continue;
			$rooms[] = $room;

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