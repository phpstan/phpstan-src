<?php declare(strict_types = 1);

namespace NativeTemplateArguments;

use function PHPStan\Testing\assertNativeType;

/**
 * A template argument is a phpdoc notion: natively it is never inferred from an
 * argument and never decided by what the phpdoc flavour resolved it to, so the
 * native type is the generic object with the template's bound.
 */
/** @template T */
class Unbounded
{

	/** @param T $v */
	public function __construct($v)
	{
	}

}

/** @template T of int */
class BoundedInt
{

	/** @param T $v */
	public function __construct($v)
	{
	}

}

/** @template T of object */
class BoundedObject
{

	/** @param T $v */
	public function __construct($v)
	{
	}

}

/** @template T of int */
class BoundedIntFactory
{

	/**
	 * @template TMake of int
	 * @param TMake $v
	 * @return BoundedInt<TMake>
	 */
	public static function make($v): BoundedInt
	{
		return new BoundedInt($v);
	}

}

function instantiation(): void
{
	assertNativeType('NativeTemplateArguments\Unbounded<mixed>', new Unbounded(1));
	assertNativeType('NativeTemplateArguments\BoundedInt<int>', new BoundedInt(1));
	assertNativeType('NativeTemplateArguments\BoundedObject<object>', new BoundedObject(new Unbounded(1)));
}

function sentToADeclaredType(): void
{
	$unbounded = new Unbounded(1);
	takesUnbounded($unbounded);
	assertNativeType('NativeTemplateArguments\Unbounded<mixed>', $unbounded);

	$bounded = new BoundedInt(1);
	takesBoundedInt($bounded);
	assertNativeType('NativeTemplateArguments\BoundedInt<int>', $bounded);
}

function callResult(): void
{
	assertNativeType('NativeTemplateArguments\BoundedInt', BoundedIntFactory::make(1));
}

/** @param Unbounded<string> $u */
function takesUnbounded(Unbounded $u): void
{
}

/** @param BoundedInt<1> $b */
function takesBoundedInt(BoundedInt $b): void
{
}
