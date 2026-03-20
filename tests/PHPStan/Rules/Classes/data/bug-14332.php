<?php declare(strict_types = 1);

namespace Bug14332;

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

// error - conflicting methods without resolution
class FooWithMultipleConflictingTraits
{
	use MyTrait1, MyTrait2;
}

// ok - resolved with insteadof
class FooWithInsteadof
{
	use MyTrait1, MyTrait2 {
		MyTrait1::doSomething insteadof MyTrait2;
	}
}

// ok - class defines the method itself
class FooWithOwnMethod
{
	use MyTrait1, MyTrait2;

	public function doSomething(): void
	{
	}
}

trait MyTrait3
{
	public function otherMethod(): void
	{
	}
}

// ok - no conflicting methods
class FooWithNoConflict
{
	use MyTrait1, MyTrait3;
}

trait MyTrait4
{
	public function doSomething(): void
	{
	}

	public function anotherMethod(): void
	{
	}
}

trait MyTrait5
{
	public function anotherMethod(): void
	{
	}
}

// error - two conflicting methods from different pairs of traits
class FooWithMultipleConflicts
{
	use MyTrait1, MyTrait4, MyTrait5;
}

// error - partially resolved (only one conflict resolved)
class FooWithPartialResolution
{
	use MyTrait1, MyTrait4, MyTrait5 {
		MyTrait1::doSomething insteadof MyTrait4;
	}
}

// ok - all conflicts resolved
class FooWithFullResolution
{
	use MyTrait1, MyTrait4, MyTrait5 {
		MyTrait1::doSomething insteadof MyTrait4;
		MyTrait4::anotherMethod insteadof MyTrait5;
	}
}

// ok - single trait, no conflict
class FooWithSingleTrait
{
	use MyTrait1;
}