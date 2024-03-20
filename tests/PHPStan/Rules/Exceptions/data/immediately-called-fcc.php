<?php // lint >= 8.1

namespace ImmediatelyCalledFcc;

class Foo
{

	/**
	 * @throws \InvalidArgumentException
	 */
	public function doFoo(): void
	{
		$f = function () {
			throw new \InvalidArgumentException();
		};
		$g = $f(...);
		$g();
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function doFoo2(): void
	{
		$f = fn () => throw new \InvalidArgumentException();
		$g = $f(...);
		$g();
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function throwsInvalidArgumentException()
	{
		throw new \InvalidArgumentException();
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function doFoo3(): void
	{
		$f = $this->throwsInvalidArgumentException(...);
		$f();
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function doFoo4(): void
	{
		$f = alsoThrowsInvalidArgumentException(...);
		$f();
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function doFoo5(): void
	{
		$f = [$this, 'throwsInvalidArgumentException'];
		$f();
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function doFoo6(): void
	{
		$f = 'ImmediatelyCalledFcc\\alsoThrowsInvalidArgumentException';
		$f();
	}

}

/**
 * @throws \InvalidArgumentException
 */
function alsoThrowsInvalidArgumentException()
{

}
