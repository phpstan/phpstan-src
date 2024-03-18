<?php // lint >= 7.4

namespace ImmediatelyCalledArrowFunction;

class ImmediatelyCalledCallback
{

	/**
	 * @throws \InvalidArgumentException
	 */
	public function doFoo(array $a): void
	{
		array_map(fn () => throw new \InvalidArgumentException(), $a);
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function doFoo2(array $a): void
	{
		$cb = fn () => throw new \InvalidArgumentException();
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function doFoo3(array $a): void
	{
		$f = fn () => throw new \InvalidArgumentException();
		$f();
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function doFoo4(array $a): void
	{
		(fn () => throw new \InvalidArgumentException())();
	}

}
