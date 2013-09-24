<?php

namespace Sapo;

class Redis {
	private static $_client;

	public static function getInstance() {
		if (self::$_client === null) {
			self::$_client = new \Predis\Client(array(
				'host'     => '127.0.0.1',
   				'port'     => 9999,
			));
		}
		return self::$_client;
	}

}