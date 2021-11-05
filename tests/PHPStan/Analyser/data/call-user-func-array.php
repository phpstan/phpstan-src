<?php

namespace CallUserFuncArray;

use function PHPStan\Testing\assertType;

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
	function doFoo() {
		assertType('*NEVER*', call_user_func_array('single-arg-only'));

		assertType('int|false', call_user_func_array('CallUserFuncArray\fun', []));

		assertType('int|false', call_user_func_array('CallUserFuncArray\fun3', [1 ,2 ,3]));


		$ret = call_user_func_array(['CallUserFuncArray\c', 'm'], [1 ,2 ,3]);
		assertType('string|false', $ret);

		/*

		$ret = call_user_func_array([new CallUserFuncArray\c(), 'm'], [1 ,2 ,3]);
		assertType('string|false', $ret);
		*/

	}
}
