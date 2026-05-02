<?php declare(strict_types = 1);

namespace Bug14563;

class Foo
{

	/** @phpstan-pure */
	public function pure(): int
	{
		return 1;
	}

	/** @phpstan-impure */
	public function impure(): int
	{
		return random_int(0, 1);
	}

	public function noAnnotation(): int
	{
		return 1;
	}

}

class ChildImpureOverridesPure extends Foo
{

	/** @phpstan-impure */
	public function pure(): int
	{
		return random_int(0, 1);
	}

}

class ChildNoAnnotationOverridesPure extends Foo
{

	public function pure(): int
	{
		return 2;
	}

}

class ChildPureOverridesPure extends Foo
{

	/** @phpstan-pure */
	public function pure(): int
	{
		return 2;
	}

}

class ChildPureOverridesImpure extends Foo
{

	/** @phpstan-pure */
	public function impure(): int
	{
		return 1;
	}

}

class ChildImpureOverridesNoAnnotation extends Foo
{

	/** @phpstan-impure */
	public function noAnnotation(): int
	{
		return random_int(0, 1);
	}

}

interface PureInterface
{

	/** @phpstan-pure */
	public function pureMethod(): int;

}

class ImpureImplementation implements PureInterface
{

	/** @phpstan-impure */
	public function pureMethod(): int
	{
		return random_int(0, 1);
	}

}

class PureImplementation implements PureInterface
{

	/** @phpstan-pure */
	public function pureMethod(): int
	{
		return 1;
	}

}

/** @phpstan-all-methods-pure */
class AllMethodsPureParent
{

	public function method(): int
	{
		return 1;
	}

}

class ImpureChildOfAllMethodsPure extends AllMethodsPureParent
{

	/** @phpstan-impure */
	public function method(): int
	{
		return random_int(0, 1);
	}

}

trait PureTrait
{

	/** @phpstan-pure */
	abstract public function pureTraitMethod(): int;

}

class ImpureTraitUser
{

	use PureTrait;

	/** @phpstan-impure */
	public function pureTraitMethod(): int
	{
		return random_int(0, 1);
	}

}

class ChildImpureOverridesPureExtended extends Foo
{

	/** @phpstan-impure */
	public function pure(): int
	{
		return random_int(0, 1);
	}

}

class GrandchildImpureOverridesPure extends ChildPureOverridesPure
{

	/** @phpstan-impure */
	public function pure(): int
	{
		return random_int(0, 1);
	}

}

/** @phpstan-all-methods-impure */
class AllMethodsImpureChildOfPure extends Foo
{

	public function pure(): int
	{
		return random_int(0, 1);
	}

}

interface PureInterfaceA
{

	/** @phpstan-pure */
	public function sharedMethod(): int;

}

interface PureInterfaceB
{

	/** @phpstan-pure */
	public function sharedMethod(): int;

}

class ImpureMultipleInterfaces implements PureInterfaceA, PureInterfaceB
{

	/** @phpstan-impure */
	public function sharedMethod(): int
	{
		return random_int(0, 1);
	}

}
