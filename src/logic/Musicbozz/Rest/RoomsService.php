<?php

namespace Musicbozz\Rest;

use Sapo\Rest\ServiceImplementation;
use Musicbozz\Question;
use Musicbozz\Question_Type;

class RoomsService extends ServiceImplementation {
	public function getItem() {
		$onlyWithPlayers = filter_input(INPUT_GET, 'onlyWithPlayers', FILTER_VALIDATE_BOOLEAN, array('flags' => FILTER_NULL_ON_FAILURE));
		$withoutPlayers = filter_input(INPUT_GET, 'withoutPlayers', FILTER_VALIDATE_BOOLEAN, array('flags' => FILTER_NULL_ON_FAILURE));
		$filterOpen = filter_input(INPUT_GET, 'onlyOpen', FILTER_VALIDATE_BOOLEAN, array('flags' => FILTER_NULL_ON_FAILURE));
		$random = filter_input(INPUT_GET, 'random', FILTER_VALIDATE_BOOLEAN, array('flags' => FILTER_NULL_ON_FAILURE));
		
		$publicRoomIds = array();
		for ($i = 1; $i <= 99; $i++) {
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
			if ($onlyWithPlayers && sizeof($room['players']) === 0) continue;
			if ($withoutPlayers && sizeof($room['players']) > 0) continue;
			if ($filterOpen && !$room['isOpen']) continue;
			$rooms[] = $room;
		}
		
		if ($random) {
			$idx = rand(0, sizeof($rooms)-1);
			$rooms = array($rooms[$idx]);
		}

		// martelado do luis
		foreach ($rooms as &$_room) {
			$default = array_fill(0,4,null);
			$_room['players'] = array_slice( array_merge($_room['players'], $default), 0,4);
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