<?php

namespace ParameterOutTests;

use function PHPStan\Testing\assertType;

/** @param-out int $outParam */
function callWithOut($inParam, &$outParam, &$anotherOut): int
{
	return 1;
}

class FooClass {
	function callWithOut($inParam, &$outParam, &$anotherOut): int
	{
		return 1;
	}

	static function staticCallWithOut($inParam, &$outParam, &$anotherOut): int
	{
		return 1;
	}
}

function doFoo() {
	callWithOut(12, $outParam, $anotherOut);
	assertType('string', $outParam);
	assertType('mixed', $anotherOut);

	FooClass::staticCallWithOut(12, $staticOut, $anotherOut);
	assertType('bool', $staticOut);
	assertType('mixed', $anotherOut);

	$foo = new FooClass();
	$foo->callWithOut(12, $methodOut, $anotherOut);
	assertType('int', $methodOut);
	assertType('mixed', $anotherOut);
}

