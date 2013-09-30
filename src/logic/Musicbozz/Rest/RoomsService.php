<?php

namespace Musicbozz\Rest;

use Sapo\Rest\ServiceImplementation;
use Musicbozz\Question;
use Musicbozz\Question_Type;

class RoomsService extends ServiceImplementation {
	public function getItem() {
		$rooms = \Sapo\Redis::getInstance()->hmget('rooms', array(1,2,3,4,5,6,7,8,9,10));
		var_dump($rooms);
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