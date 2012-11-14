<?php
namespace Musicbozz\Catalog\Musicbox;

abstract class AbstractBaseClient extends \SoapClient 
{
	protected $header 	= array();
	protected $classmap = array();
	protected $actor;
	protected $wsdl;
	protected $ESBCredentials;

	public function __construct()
	{
		parent::__construct($this->wsdl, array('classmap' => $this->classmap, 'trace' => true, 'cache_wsdl' => WSDL_CACHE_NONE, 'features' => SOAP_SINGLE_ELEMENT_ARRAYS));
	}
	
	public function __call($method, $parameters)
	{
		$requestHeader = array();


		try
		{
			$response = $this->__soapCall($method, $parameters, null, $requestHeader);
			return $response;
		}
		catch(SoapFault $e)
		{	
			$text = (!$e->getMessage()) ? $e->faultstring : $e->getMessage();
			throw new SoapFault('SoapFault', $text, $this->actor);
		}
	}
}
