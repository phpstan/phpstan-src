<?php declare(strict_types = 1);

namespace ArrayByRefItemPassedToCall;

use function PHPStan\Testing\assertType;

/** @param array{bool} $a */
function takesShape(array $a): void {}

/** @param array{x: bool} $a */
function takesKeyedShape(array $a): void {}

/** @param array<int, array<int, bool>> $a */
function takesNested(array $a): void {}

function takesUntypedArray(array $a): void {}

final class Holder
{

	public bool $property = false;

	public static bool $staticProperty = false;

	/** @param array{bool} $a */
	public function __construct(array $a = [false])
	{
	}

	/** @param array{bool} $a */
	public function method(array $a): void
	{
	}

	/** @param array{bool} $a */
	public static function staticMethod(array $a): void
	{
	}

}

function funcCall(): void
{
	$retry = false;
	takesShape([&$retry]);
	assertType('bool', $retry);
}

function methodCall(Holder $h): void
{
	$retry = false;
	$h->method([&$retry]);
	assertType('bool', $retry);
}

function staticCall(): void
{
	$retry = false;
	Holder::staticMethod([&$retry]);
	assertType('bool', $retry);
}

function instantiation(): void
{
	$retry = false;
	new Holder([&$retry]);
	assertType('bool', $retry);
}

/** @param callable(array{bool}): void $c */
function closureCall(callable $c): void
{
	$retry = false;
	$c([&$retry]);
	assertType('bool', $retry);
}

function stringKey(): void
{
	$retry = false;
	takesKeyedShape(['x' => &$retry]);
	assertType('bool', $retry);
}

function nestedArrayLiteral(): void
{
	$retry = false;
	takesNested([[&$retry]]);
	assertType('bool', $retry);
}

function propertyByRef(Holder $h): void
{
	if (!$h->property) {
		takesShape([&$h->property]);
		assertType('bool', $h->property);
	}
}

function staticPropertyByRef(): void
{
	if (!Holder::$staticProperty) {
		takesShape([&Holder::$staticProperty]);
		assertType('bool', Holder::$staticProperty);
	}
}

function offsetByRef(): void
{
	$arr = ['k' => false];
	takesShape([&$arr['k']]);
	assertType('bool', $arr['k']);
}

function unknownValueType(): void
{
	$retry = false;
	takesUntypedArray([&$retry]);
	assertType('mixed', $retry);
}

function arrayVariablePassedToCall(): void
{
	$retry = false;
	$args = [&$retry];
	assertType('false', $retry);
	takesShape($args);
	assertType('bool', $retry);
}

function localWriteStillPrecise(): void
{
	$retry = false;
	$args = [&$retry];
	$args[0] = true;
	assertType('true', $retry);
}

function byRefParameterNotAffected(): void
{
	$retry = false;
	takesShape([$retry]);
	assertType('false', $retry);
}
