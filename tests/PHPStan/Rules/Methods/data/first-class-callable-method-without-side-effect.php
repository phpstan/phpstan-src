<?php // lint >= 8.1

namespace FirstClassCallableMethodWithoutSideEffect;

class Foo
{

	public function doFoo(): void
	{
		$f = $this->doFoo(...);

		$this->doFoo(...);
	}

}

class Bar
{

	function doFoo(): never
	{
		throw new \Exception();
	}

	/**
	 * @throws \Exception
	 */
	function doBar()
	{
		throw new \Exception();
	}

	function doBaz(): void
	{
		$f = $this->doFoo(...);
		$this->doFoo(...);

		$g = $this->doBar(...);
		$this->doBar(...);
	}

}
