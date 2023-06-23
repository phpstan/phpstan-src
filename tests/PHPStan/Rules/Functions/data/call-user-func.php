<?php // lint >= 8.0

namespace CallUserFuncRule;

use function call_user_func;

class Foo
{

	public function doFoo(): void
	{
		$f = function (int $i): void {

		};
		call_user_func($f);
		call_user_func($f, 1);
		call_user_func($f, 'foo');
		call_user_func($f, i: 'foo');
		call_user_func(i: 'foo', callback: $f);
		call_user_func($f, i: 1);
		call_user_func(i: 1, callback: $f);
		call_user_func($f, j: 1);
	}

}
