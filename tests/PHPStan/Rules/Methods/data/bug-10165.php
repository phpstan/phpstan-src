<?php

declare(strict_types = 1);

namespace Bug10165;

class CustomSoapClient extends \SoapClient
{
	public function __construct(array $options = [])
	{
		parent::__construct('some.wsdl', $options);
	}
}
