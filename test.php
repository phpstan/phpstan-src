<?php

use PHPStan\Type\ConstantScalarType;
use function PHPStan\Testing\assertType;

class x {
	public function foo(int $intA, ConstantScalarType $exponent):void {
		$result = null ** $exponent->getValue();
	}

}
