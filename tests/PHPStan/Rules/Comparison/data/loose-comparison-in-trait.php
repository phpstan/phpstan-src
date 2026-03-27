<?php

namespace LooseComparisonInTrait;

trait FooTrait
{

	public function doFoo()
	{
		// sometimes constant, sometimes not
		if ($this->doBar() == null) {

		}
	}

	public function doFoo2()
	{
		// always false
		if ($this->doBar2() == null) {

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

	public function doBar(): ?int
	{

	}

	/** @return 1 */
	public function doBar2(): int
	{

	}

}
