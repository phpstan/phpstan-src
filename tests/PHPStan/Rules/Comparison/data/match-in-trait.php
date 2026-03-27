<?php // lint >= 8.0

namespace MatchInTrait;

trait FooTrait
{

	public function doFoo()
	{
		// sometimes constant, sometimes not
		match (true) {
			$this->doBar() => 'yes',
			default => 'no',
		};
	}

	public function doFoo2()
	{
		// always false
		match (true) {
			$this->doBar2() => 'yes',
			default => 'no',
		};
	}

}

class Foo
{

	use FooTrait;

	public function doBar(): false
	{

	}

	public function doBar2(): false
	{

	}

}

class FooAnother
{

	use FooTrait;

	public function doBar(): bool
	{

	}

	public function doBar2(): false
	{

	}

}
