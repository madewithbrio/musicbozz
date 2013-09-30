<?php

namespace Sapo\Rest;
use \Exception;
class Server {

	private $conditionsTable = array();
	
	public function add($condition, $instanceService)
	{
		$reflectionClass = new \ReflectionClass($instanceService);
		if (!$reflectionClass->isSubclassOf('\Sapo\Rest\ServiceImplementation'))
		{
			throw new Exception('instanceService not implements Sapo\Rest\ServiceImplementation.', -1);
		}

		if(key_exists($reflectionClass->getName(), $this->conditionsTable))
		{
			throw new Exception('Duplicate condition exception.', -1);
		}
		

		$this->conditionsTable[$reflectionClass->getName()] = array('instance' => $instanceService, 'condition' => $condition);
	}

	public function run()
	{
		if(count($this->conditionsTable) < 1)
		{
			throw new Exception('No conditions to evaluate exception.', -1);
		}
		
		foreach($this->conditionsTable as $instanceService)
		{
			if($instanceService['condition'])
			{
				return call_user_func(array($instanceService['instance'], 'handle'));
			}
		}

		throw new Exception("No condition evaluated to true Exception", -1);
	}
}