<?php

namespace CallUserFunc;

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
			'CallUserFunc\generic',
			123,
			456
		];

		assertType('mixed', call_user_func(...$params));
	}

	/**
	 * @param string $params,...
	 */
	function doVariadics(...$params) {
		assertType('string', call_user_func('CallUserFunc\generic', ...$params));
	}

	/**
	 * @param string[] $strings
	 */
	function doFunc($strings) {
		assertType('true', call_user_func('CallUserFunc\generic', true));
		assertType('\'hello\'', call_user_func('CallUserFunc\generic', 'hello'));
		assertType('array<string>', call_user_func('CallUserFunc\generic', $strings));

		assertType('int', call_user_func('CallUserFunc\fun'));
		assertType('int', call_user_func('CallUserFunc\fun3', 1 ,2 ,3));
		assertType('string', call_user_func(['CallUserFunc\c', 'm']));
	}
}
