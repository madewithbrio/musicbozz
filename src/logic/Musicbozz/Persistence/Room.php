<?php

namespace Musicbozz\Persistence;
use \Sapo\Redis;

class Room {
	const HASHKEY = 'rooms';

	public static function save($key, $object) {
		Redis::getInstance()->hset(self::HASHKEY, $key, serialize($object));
	}

	public static function get($key) {
		$serializeObj = Redis::getInstance()->hget(self::HASHKEY, $key);
		return unserialize($serializeObj);
	}

	public static function mget($keys) {
		$_rooms = Redis::getInstance()->hmget(self::HASHKEY, $keys);
		return array_map('unserialize', $_rooms);
	}

	public static function delete($key) {
		Redis::getInstance()->hdel(self::HASHKEY, $key);
	}
}