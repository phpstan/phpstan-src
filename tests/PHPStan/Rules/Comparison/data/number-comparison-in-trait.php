<?php

namespace NumberComparisonInTrait;

trait FooTrait
{

	public function doFoo()
	{
		// sometimes constant, sometimes not
		if ($this->doBar() > 0) {

		}
	}

	public function doFoo2()
	{
		// always constant
		if ($this->doBar2() > 0) {

		}
	}

}

class Foo
{

	use FooTrait;

	/** @return 1 */
	public function doBar(): int
	{

	}

	/** @return 1 */
	public function doBar2(): int
	{

	}

}

class FooAnother
{

	use FooTrait;

	public function doBar(): int
	{

	}

	/** @return 1 */
	public function doBar2(): int
	{

	}

}
