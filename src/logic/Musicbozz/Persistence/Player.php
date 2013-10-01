<?php

namespace Musicbozz\Persistence;
use \Sapo\Redis;

class Player {
	const HASHKEY = 'players';

	public static function save($key, $object) {
		Redis::getInstance()->hset(self::HASHKEY, $key, serialize($object));
	}

	public static function get($key) {
		$serializeObj = Redis::getInstance()->hget(self::HASHKEY, $key);
		return unserialize($serializeObj);
	}

	public static function delete($key) {
		Redis::getInstance()->hdel(self::HASHKEY, $key);
	}
}