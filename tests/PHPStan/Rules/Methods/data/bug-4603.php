<?php // lint >= 8.0

namespace Bug4603;

class Foo
{

	/**
	 * @param T $val
	 *
	 * @return T
	 *
	 * @template T
	 */
	function fcn(mixed $val = null)
	{
		return $val;
	}

}
