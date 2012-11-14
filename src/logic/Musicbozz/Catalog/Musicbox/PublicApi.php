<?php
namespace Musicbozz\Catalog\Musicbox;

final class PublicApi extends AbstractBaseClient {
	
	public function __construct()
	{
		$this->actor = "https://services.sapo.pt/Music/OnDemand/PublicApi";
		$this->wsdl = __DIR__ . '/Contracts/Music_OnDemand_PublicApi.wsdl'; //@todo

		parent::__construct();
	}
}