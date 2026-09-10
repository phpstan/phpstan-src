<?php

namespace SoapTests;

use SoapFault;

class MySoapClient extends \SoapClient
{

}

class MySoapClient2 extends \SoapClient
{

	/**
	 * @param string|null $wsdl
	 * @param mixed[] $options
	 */
	public function __construct($wsdl, array $options = [])
	{
		parent::__construct($wsdl, $options);
	}
}

class MySoapClient3 extends \SoapClient
{

	/**
	 * @param string|null $wsdl
	 * @param mixed[]|null $options
	 */
	public function __construct($wsdl, array $options = null)
	{
		parent::SoapClient($wsdl, $options);
	}
}

function () {
	$soap = new MySoapClient('some.wsdl', ['soap_version' => SOAP_1_2]);
	$soap2 = new MySoapClient2('some.wsdl', ['soap_version' => SOAP_1_2]);
	$soap3 = new MySoapClient3('some.wsdl', ['soap_version' => SOAP_1_2]);

	return [$soap, $soap2, $soap3];
};

class MySoapHeader extends \SoapHeader
{

	public function __construct(string $username, string $password)
	{
		parent::__construct($username, $password);
	}

}

function () {
	$header = new MySoapHeader('user', 'passw0rd');

	return $header;
};

function (\SoapFault $fault) {
	echo $fault->faultcode;
	echo $fault->faultstring;
};
