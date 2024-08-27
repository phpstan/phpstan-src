<?php // lint >= 8.1

namespace NoNamedArgumentsCallUserFunc;

use function call_user_func;

/**
 * @no-named-arguments
 */
function foo(int $i): void
{

}

class Foo
{

	/**
	 * @no-named-arguments
	 */
	public function doFoo(int $i): void
	{

	}

}

function (Foo $f): void {
	call_user_func(foo(...), i: 1);
	call_user_func('NoNamedArgumentsCallUserFunc\\foo', i: 1);
	call_user_func([$f, 'doFoo'], i: 1);
	call_user_func($f->doFoo(...), i: 1);
};
