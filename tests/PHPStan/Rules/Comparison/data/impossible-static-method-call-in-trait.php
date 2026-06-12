<?php // lint >= 8.0

namespace ImpossibleStaticMethodCallInTrait;

class TypeChecker
{
	/** @phpstan-assert-if-true string $value */
	public static function isString(mixed $value): bool
	{
		return is_string($value);
	}
}

trait FooTrait
{

	public function doFoo()
	{
		// sometimes constant, sometimes not
		if (TypeChecker::isString($this->doBar())) {

		}
	}

	public function doFoo2()
	{
		// always false
		if (TypeChecker::isString($this->doBar2())) {

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
