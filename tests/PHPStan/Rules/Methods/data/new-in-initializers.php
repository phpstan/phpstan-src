<?php // lint >= 8.1

namespace MethodNewInInitializers;

class Foo
{

	/**
	 * @param int $i
	 */
	public function doFoo($i = new \stdClass(), object $o = new \stdClass())
	{

	}

}
