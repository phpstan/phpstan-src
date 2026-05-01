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

}
