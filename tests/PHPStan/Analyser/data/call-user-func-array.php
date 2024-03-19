<?php

namespace CallUserFuncArray;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @param T $t
 * @return T
 */
function generic($t) {
	return $t;
}

function fun(): int
{
	return 3;
}

function fun3($i, $x, $y): int
{
	return 3;
}

class c {
	static function m(): string
	{
		return 'hello';
	}
}

class Foo {
	/**
	 * @param string $params,...
	 */
	function doVariadics(...$params) {
		assertType('string', call_user_func_array('CallUserFuncArray\generic', $params));
	}

	/**
	 * @param string[] $strings
	 */
	function doFunc($strings) {
		assertType('bool', call_user_func_array('CallUserFuncArray\generic', [true]));
		assertType('string', call_user_func_array('CallUserFuncArray\generic', ['hello']));
		assertType('string', call_user_func_array('CallUserFuncArray\generic', $strings));
		assertType('array<string>', call_user_func_array('CallUserFuncArray\generic', [$strings]));

		assertType('int', call_user_func_array('CallUserFuncArray\fun', []));
		assertType('int', call_user_func_array('CallUserFuncArray\fun3', [1 ,2 ,3]));
		assertType('string', call_user_func_array(['CallUserFuncArray\c', 'm'], []));
	}
}
