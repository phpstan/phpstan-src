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
	 * @param T[] $a
	 * @return T
	 */
	public function doBaz($a)
	{
	}

	public function doLorem()
	{
		$this->doFoo(1);
		$this->doBar();
		$this->doBaz(1);
	}

	/**
	 * @template T
	 * @param mixed $a
	 * @return T
	 */
	public function doIpsum($a)
	{

	}

	public function doDolor()
	{
		$this->doIpsum(1);
	}

}
