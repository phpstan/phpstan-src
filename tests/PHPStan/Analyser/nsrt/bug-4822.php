<?php

namespace Bug4822;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

class Foo
{

	/**
	 * @throws \Exception
	 */
	function save(): void {

	}

	function doFoo()
	{
		$soapClient = new \SoapClient('https://example.com/?wsdl');

		try {
			$response = $soapClient->test();

			if (is_array($response)) {
				$this->save();
			}
		} catch (\Exception $e) {
			assertVariableCertainty(TrinaryLogic::createMaybe(), $response);
		}
	}

}
