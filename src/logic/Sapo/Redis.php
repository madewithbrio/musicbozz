<?php

namespace Sapo;

class Redis {
	private static $_client;

	public static function getInstance() {
		global $redis_config;
		if (self::$_client === null) {
			self::$_client = new \Predis\Client($redis_config);
		}
		return self::$_client;
	}

}