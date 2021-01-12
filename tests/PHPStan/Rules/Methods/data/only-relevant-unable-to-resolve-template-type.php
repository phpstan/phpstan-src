<?php

namespace OnlyRelevantUnableToResolve;

class Foo
{

	/**
	 * @template T
	 * @param T $a
	 * @return T[]
	 */
	public function doFoo($a)
	{

	}

	/**
	 * @template T
	 * @return int[]
	 */
	public function doBar()
	{

	}

	/**
	 * @template T
	 * @template U
	 * @return T
	 */
	public function doBaz()
	{

	}

	public function doLorem()
	{
		$this->doFoo(1);
		$this->doBar();
		$this->doBaz();
	}

}
