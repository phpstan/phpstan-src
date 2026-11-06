<?php // lint >= 8.1

namespace ReadonlyPropertyAssignPhpVersionRange;

class ParentClass
{

	public readonly int $a;

}

class ChildClass extends ParentClass
{

	public function __construct()
	{
		if (PHP_VERSION_ID >= 80400) {
			$this->a = 1;
		}
	}

	public function doFoo(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->a = 1;
		}
	}

}

class Foo
{

	public function __construct(
		public readonly int $a,
	)
	{
	}

	public function __clone()
	{
		if (PHP_VERSION_ID >= 80300) {
			$this->a = 1;
			$this->a = 2;
		}
	}

}
