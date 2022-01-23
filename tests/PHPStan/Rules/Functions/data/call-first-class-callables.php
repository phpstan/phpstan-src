<?php

namespace CallFirstClassCallables;

class Foo
{

	/**
	 * @param mixed $mixed
	 */
	public function doFoo($mixed)
	{
		$f = $this->doBar(...);
		$f($mixed);

		$g = \Closure::fromCallable([$this, 'doBar']);
		$g($mixed);
	}

	/**
	 * @template T of object
	 * @param T $object
	 * @return T
	 */
	public function doBar($object)
	{
		return $object;
	}

}
