<?php

namespace Musicbozz\Persistence;
use \Sapo\Redis;

class SimilarArtist {
	const HASHKEY = 'SimilarArtist';

	public static function save($key, $object) {
		print "Redis:: HSET " . self::HASHKEY ." ".$key."\n";
		Redis::getInstance()->hset(self::HASHKEY, $key, serialize($object));
	}

	public static function get($key) {
		print "Redis:: HGET " . self::HASHKEY ." ".$key."\n";
		$serializeObj = Redis::getInstance()->hget(self::HASHKEY, $key);
		return unserialize($serializeObj);
	}

	public static function delete($key) {
		Redis::getInstance()->hdel(self::HASHKEY, $key);
	}
}
