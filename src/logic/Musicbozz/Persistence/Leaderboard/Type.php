<?php
namespace Musicbozz\Persistence\Leaderboard;
use \Exception;
class Type {
	const COMMON = 'leaderboard::common';
	const ALONE = 'leaderboard::alone';
	const ROOMPRIVATE = 'leaderboard::private';
	const ROOMPUBLIC = 'leaderboard::public';
	private $value;

	public function __construct($value) {
		$const = 'static::'.strtoupper($value);
		if (!defined($const)) throw new Exception("leaderboard not defined");
		$this->value = constant($const);
	}

	public static function factory($value) {
		return new static($value);
	}

	public function __toString() {
		return $this->value();
	}

	public function value($value) {
		if (null !== $value) $this->value = $value;
		return $this->value;
	}
}