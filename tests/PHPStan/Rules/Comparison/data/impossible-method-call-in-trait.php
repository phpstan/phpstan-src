<?php // lint >= 8.0

namespace ImpossibleMethodCallInTrait;

class TypeChecker
{
	/** @phpstan-assert-if-true string $value */
	public function isString(mixed $value): bool
	{
		return is_string($value);
	}
}

trait FooTrait
{

	public function doFoo()
	{
		$checker = new TypeChecker();
		// sometimes constant, sometimes not
		if ($checker->isString($this->doBar())) {

		}
	}

	public function doFoo2()
	{
		$checker = new TypeChecker();
		// always false
		if ($checker->isString($this->doBar2())) {

		}
	}

}

class Foo
{

	use FooTrait;

	public function doBar(): int
	{

	}

	public function doBar2(): int
	{

	}

}

class FooAnother
{

	use FooTrait;

	/** @return int|string */
	public function doBar()
	{

	}

	public function doBar2(): int
	{

	}

}
