<?php

namespace CallUserFuncPhp8;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @param T $t
 * @return T
 */
function generic($t) {
	return $t;
}

/**
 * @template T
 * @param T $t
 * @return T
 */
function generic3($t = '', int $b = 100, string $c = '') {
	return $t;
}


function fun3($a = '', $b = '', $c = ''): int {
	return 1;
}

class Foo {

	/**
	 * @param string $params,...
	 */
	function doVariadics(...$params) {
		// because of named arguments support in php8 we have a different return type as in php7
		// see https://phpstan.org/r/58c30346-9568-47ca-82e5-53b2fffda7d0
		assertType('array<int|string, string>', call_user_func('CallUserFuncPhp8\generic', $params));
	}

	function doNamed() {
		assertType('1', call_user_func('CallUserFuncPhp8\generic', t: 1));
		assertType('array{1, 2, 3}', call_user_func('CallUserFuncPhp8\generic', t: [1, 2, 3]));

		assertType('array{1, 2, 3}', call_user_func('CallUserFuncPhp8\generic3', t: [1, 2, 3]));
		assertType('\'\'', call_user_func('CallUserFuncPhp8\generic3', b: 150));
		assertType('\'\'', call_user_func('CallUserFuncPhp8\generic3', c: 'lala'));
		assertType('\'\'', call_user_func(c: 'lala', callback: 'CallUserFuncPhp8\generic3'));

		assertType('int', call_user_func('CallUserFuncPhp8\fun3', a: [1, 2, 3]));
		assertType('int', call_user_func('CallUserFuncPhp8\fun3', b: [1, 2, 3]));
		assertType('int', call_user_func('CallUserFuncPhp8\fun3', c: [1, 2, 3]));
		assertType('int', call_user_func('CallUserFuncPhp8\fun3', a: [1, 2, 3], c: 'c'));
	}
}
