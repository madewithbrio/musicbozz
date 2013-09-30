<?php

namespace Sapo;
use \Exception;

class ConditionTable
{
	private $conditionsTable = array();
	private $instance;

	public function __construct($instance) {
		$this->instance = $instance;
	}

	public function getControllerInstance() {
		return $this->instance;
	}
	
	public function add($condition, $callMethod)
	{
		if(key_exists($callMethod, $this->conditionsTable))
		{
			throw new Exception('Duplicate condition exception.', -1);
		}
		if (!method_exists($this->instance, $callMethod))
		{
			throw new Exception("CallMethod ($callMethod) not defined.", -1);
		}

		$this->conditionsTable[(string)$callMethod] = $condition;
	}

	public function run()
	{
		if(count($this->conditionsTable) < 1)
		{
			throw new Exception('No conditions to evaluate exception.', -1);
		}
		
		foreach($this->conditionsTable as $method => $condition)
		{
			if($condition)
			{
				return call_user_func(array($this->instance, $method));
			}
		}

		throw new Exception("No condition evaluated to true Exception", -1);
	}
}