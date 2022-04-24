<?php // lint >= 8.0

namespace IncompatibleConditionalReturnType;

class Foo
{

	/**
	 * @return ($p is int ? int : string)
	 */
	public function doFoo($p): int|string
	{

	}

}


class Bar
{

	/**
	 * @return ($p is int ? int : string)
	 */
	public function doFoo($p): int
	{

	}

}
