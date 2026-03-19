<?php declare(strict_types = 1);

namespace Bug14250;

trait MyTrait
{
	public function doSomething(): void
	{
	}

	public function doSomething(): void
	{
	}
}

class Foo
{
	use MyTrait;
}

trait TraitWithDuplicateConstants
{
	public const CONST1 = 1;
	public const CONST1 = 2;

	public const CONST2 = 2, CONST2 = 1;

	public const CONST3 = 1;
}

class Bar
{
	use TraitWithDuplicateConstants;
}

trait TraitWithDuplicateProperties
{
	/** @var int */
	public $prop1;
	/** @var int */
	public $prop1;

	/** @var int */
	public $prop2, $prop2;

	/** @var int */
	public $prop3;
}

class Baz
{
	use TraitWithDuplicateProperties;
}

trait TraitWithDuplicateMethods
{
	public function func1(): void {}

	public function func1(): int
	{
		return 1;
	}

	public function func2(): int
	{
		return 2;
	}

	public function Func1(): void {}
}

class Qux
{
	use TraitWithDuplicateMethods;
}

trait MyTrait1
{
	public function doSomething(): void
	{
	}
}

trait MyTrait2
{
	public function doSomething(): void
	{
	}
}

class FooWithMultipleConflictingTraits
{
	use MyTrait1, MyTrait2;
}
