<?php // lint >= 8.1

namespace DeadCatchFirstClassCallables;

class Foo
{

	public function doFoo(): void
	{
		try {
			$this->doBar();
		} catch (\InvalidArgumentException $e) {

		}
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function doBar(): void
	{
		throw new \InvalidArgumentException();
	}

	public function doBaz(): void
	{
		try {
			$this->doBar(...);
		} catch (\InvalidArgumentException $e) {

		}
	}

}
