<?php

declare(strict_types = 1);

namespace UnnecessaryNullCoalesceSideEffects;

use LogicException;

const NULL_CONSTANT = null;

/** @return null */
function returnsNull(bool $value)
{
	if ($value === false) {
		throw new LogicException('nope');
	}

	return null;
}

/**
 * @phpstan-pure
 * @return null
 */
function pureReturnsNull()
{
	return null;
}

class Foo
{

	public const NULL_CONSTANT = null;

	/** @var string|null */
	public $stringOrNull = null;

	/** @var null */
	public $alwaysNull = null;

	/** @return null */
	public function returnsNull()
	{
		return null;
	}

	/** @return null */
	public static function staticReturnsNull()
	{
		return null;
	}

	/** @return null */
	public function __invoke()
	{
		return null;
	}

}

function funcCallOnRightSide(Foo $foo, ?string $name): ?string
{
	return $foo->stringOrNull ?? returnsNull($name !== null);
}

function methodCallOnRightSide(Foo $foo): ?string
{
	return $foo->stringOrNull ?? $foo->returnsNull();
}

function staticCallOnRightSide(Foo $foo): ?string
{
	return $foo->stringOrNull ?? Foo::staticReturnsNull();
}

function invokeOnRightSide(Foo $foo): ?string
{
	return $foo->stringOrNull ?? $foo();
}

function closureCallOnRightSide(Foo $foo): ?string
{
	$closure = static function () {
		echo 'side effect';

		return null;
	};

	return $foo->stringOrNull ?? $closure();
}

function assignOnRightSide(Foo $foo): ?string
{
	$result = $foo->stringOrNull ?? $x = null;
	echo $x;

	return $result;
}

function assignOpOnRightSide(Foo $foo, ?string $name): ?string
{
	$x = $name;
	$x ??= $foo->returnsNull();

	return $x;
}

function assignOpPureOnRightSide(?string $name): ?string
{
	$x = $name;
	$x ??= null;

	return $x;
}

function pureFuncCallOnRightSide(Foo $foo): ?string
{
	return $foo->stringOrNull ?? pureReturnsNull();
}

function constantOnRightSide(Foo $foo): ?string
{
	return $foo->stringOrNull ?? NULL_CONSTANT;
}

function classConstantOnRightSide(Foo $foo): ?string
{
	return $foo->stringOrNull ?? Foo::NULL_CONSTANT;
}

function nullVariableOnRightSide(Foo $foo): ?string
{
	$null = null;

	return $foo->stringOrNull ?? $null;
}

function nullPropertyOnRightSide(Foo $foo, Foo $bar): ?string
{
	return $foo->stringOrNull ?? $bar->alwaysNull;
}
