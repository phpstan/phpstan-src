<?php

namespace CallUserFuncArrayPhp8;

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

	function doNamed() {
		assertType('int', call_user_func_array('CallUserFuncArrayPhp8\generic', ['t'=> 1]));
		assertType('array{int, int, int}', call_user_func_array('CallUserFuncArrayPhp8\generic', ['t'=> [1, 2, 3]]));

		assertType('array{int, int, int}', call_user_func_array('CallUserFuncArrayPhp8\generic3', ['t'=> [1, 2, 3]]));
		assertType('string', call_user_func_array('CallUserFuncArrayPhp8\generic3', ['c'=> 'lala']));
		assertType('string', call_user_func_array(args: ['c'=> 'lala'], callback: 'CallUserFuncArrayPhp8\generic3'));

		assertType('int', call_user_func_array('CallUserFuncArrayPhp8\fun3', ['a'=> [1, 2, 3]]));
		assertType('int', call_user_func_array('CallUserFuncArrayPhp8\fun3', ['b'=> [1, 2, 3]]));
		assertType('int', call_user_func_array('CallUserFuncArrayPhp8\fun3', ['c'=> [1, 2, 3]]));
		assertType('int', call_user_func_array('CallUserFuncArrayPhp8\fun3', ['a'=> [1, 2, 3], 'c'=> 'c']));
	}
}
