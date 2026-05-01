<?php

namespace PureUnlessCallableIsImpure;

class Foo
{

	/**
	 * @param array<int> $array
	 */
	public function doFoo(array $array): void
	{
		myFilter($array, fn ($v) => $v > 5);
	}

	/**
	 * @param array<int> $array
	 */
	public function doBar(array $array, callable $callback): void
	{
		// Should NOT be reported - unknown callable might be impure
		myFilter($array, $callback);

		// Should NOT be reported - closure is impure
		myFilter($array, function ($v) {
			echo $v;
			return $v > 0;
		});
	}

}
