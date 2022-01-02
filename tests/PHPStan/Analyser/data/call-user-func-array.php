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
	function proxy() {
		$params = [
			'CallUserFuncArray\generic',
			[123]
		];

		assertType('mixed', call_user_func_array(...$params));

		$params = [
			'CallUserFuncArray\generic',
			123,
			456
		];

		// cufa expect max 2 args
		assertType('*NEVER*', call_user_func_array(...$params));
		assertType('mixed', call_user_func(...$params));
	}

	/**
	 * @param string $params,...
	 */
	function doVariadics(...$params) {
		assertType('string', call_user_func_array('CallUserFuncArray\generic', $params));
		assertType('string', call_user_func('CallUserFuncArray\generic', ...$params));
	}

	/**
	 * @param string[] $strings
	 */
	function doArray($strings) {
		assertType('*NEVER*', call_user_func_array('single-arg-only'));

		assertType('bool', call_user_func_array('CallUserFuncArray\generic', [true]));
		assertType('string', call_user_func_array('CallUserFuncArray\generic', ['hello']));
		assertType('string', call_user_func_array('CallUserFuncArray\generic', $strings));

		assertType('int', call_user_func_array('CallUserFuncArray\fun', []));
		assertType('int', call_user_func_array('CallUserFuncArray\fun3', [1 ,2 ,3]));
		assertType('string', call_user_func_array(['CallUserFuncArray\c', 'm'], []));
	}

	/**
	 * @param string[] $strings
	 */
	function doFunc($strings) {
		assertType('bool', call_user_func('CallUserFuncArray\generic', true));
		assertType('string', call_user_func('CallUserFuncArray\generic', 'hello'));
		assertType('array<string>', call_user_func('CallUserFuncArray\generic', $strings));

		assertType('int', call_user_func('CallUserFuncArray\fun'));
		assertType('int', call_user_func('CallUserFuncArray\fun3', 1 ,2 ,3));
		assertType('string', call_user_func(['CallUserFuncArray\c', 'm']));
	}
}
