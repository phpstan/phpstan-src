<?php // lint >= 8.0

namespace ConstantOverrideAttr;

interface FooInterface
{
	const FROM_INTERFACE = 1;
}

class Foo
{
	const FROM_PARENT = 1;
	protected const PROTECTED_FROM_PARENT = 1;
	private const PRIVATE_FROM_PARENT = 1;
}

class Bar extends Foo implements FooInterface
{
	#[\Override]
	const FROM_INTERFACE = 2;

	#[\Override]
	const FROM_PARENT = 2;

	#[\Override]
	protected const PROTECTED_FROM_PARENT = 2;

	#[\Override]
	const PRIVATE_FROM_PARENT = 2;

	#[\Override]
	const NOT_OVERRIDING = 2;

	const NEW_CONSTANT = 2;
}

class Baz extends Foo
{
	const FROM_PARENT = 3;

	#[\Override]
	const PROTECTED_FROM_PARENT = 3, ALSO_NOT_OVERRIDING = 3;
}

interface BarInterface extends FooInterface
{
	#[\Override]
	const FROM_INTERFACE = 4;

	#[\Override]
	const NOT_OVERRIDING = 4;
}

trait FooTrait
{
	#[\Override]
	const FROM_PARENT = 5;

	const PROTECTED_FROM_PARENT = 5;
}

class UsesTraitWithParent extends Foo
{
	use FooTrait;
}

class UsesTraitWithoutParent
{
	use FooTrait;
}
