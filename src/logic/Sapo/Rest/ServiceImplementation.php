<?php

namespace Sapo\Rest;
use Sapo\ConditionTable;
use Sapo\Helper\Array2XML;

abstract class ServiceImplementation {
	
	public function handle () {
		try {
			$routing = new ConditionTable($this);
			$routing->add($_SERVER["REQUEST_METHOD"] == 'HEAD', 	'existsItem');
			$routing->add($_SERVER["REQUEST_METHOD"] == 'GET', 		'getItem');
			$routing->add($_SERVER["REQUEST_METHOD"] == 'POST', 	'createItem');
			$routing->add($_SERVER["REQUEST_METHOD"] == 'PUT', 		'saveItem');
			$routing->add($_SERVER["REQUEST_METHOD"] == 'DELETE', 	'deleteItem');
			$routing->run();
		} catch (\Exception $e) {
			print $e->getMessage(); die;
			header('HTTP/1.1 500 Internal Server Error');
			$this->sendResponse($e);
		}
	}

	abstract public function existsItem();
	abstract public function getItem();
	abstract public function createItem();
	abstract public function saveItem();
	abstract public function deleteItem();

	public function getPath() { return $_SERVER["PATH_INFO"]; }

	private function toXML($data, $root = 'root') {
		header('Content-Type: application/xml; charset=UTF-8');
		$xml = Array2XML::createXML($root, $data);
		echo $xml->saveXML();
		exit();
	} 

	private function toJSON($data) {
		$jsonp = $_GET['jsonp'];
		if (!empty($jsonp)) {
			header('Content-Type: application/javascript');
			printf('%s(%s);', $jsonp, json_encode($data));
		} else {
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode($data);
		}
		exit();
	}

	public function sendResponse($data, $root = null) {
		$accepts = explode(",", $_SERVER['HTTP_ACCEPT']);
		if (!empty($_GET['output'])) array_unshift($accepts, $_GET['output']);
		foreach ($accepts as $accept) {
			switch ($accept) {
				case 'xml':
				case 'text/xml':
				case 'application/xml':
					$this->toXML($data, $root);
				case 'json':
				case 'text/json':
				case 'application/json':
					$this->toJSON($data);
			}
		}
		// fallback
		$this->toJSON($data);
	}
}