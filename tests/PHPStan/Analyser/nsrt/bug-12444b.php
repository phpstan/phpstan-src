<?php

declare(strict_types = 1);

namespace Bug12444b;

use function PHPStan\Testing\assertType;

/**
 * @template-contravariant T
 */
interface Contra {}

/**
 * @template T
 * @extends Contra<T>
 */
interface Inv extends Contra {}

/**
 * @template T of object
 * @param class-string<T> $class
 * @return Contra<T>
 */
function contra(string $class): Contra
{
	throw new \Exception();
}

/**
 * @template T of object
 * @param class-string<T> $class
 * @return Inv<T>
 */
function inv(string $class): Inv
{
	throw new \Exception();
}

// Non-variadic: two separate contravariant params
/**
 * @template T
 * @param T $value
 * @param Contra<T> $a
 * @param Contra<T> $b
 * @return T
 */
function testTwoParams(mixed $value, Contra $a, Contra $b): mixed
{
	return $value;
}

// Non-variadic with direct Contra
$r1 = testTwoParams(
	new \RuntimeException(),
	contra(\Throwable::class),
	contra(\Exception::class),
);
assertType('RuntimeException', $r1);

// Non-variadic with Inv (extending Contra)
$r2 = testTwoParams(
	new \RuntimeException(),
	inv(\Throwable::class),
	inv(\Exception::class),
);
assertType('RuntimeException', $r2);

// Mixed variance: function with both covariant and contravariant template params
/**
 * @template-covariant Out
 * @template-contravariant In
 */
interface Func
{
}

/**
 * @template Out
 * @template In
 * @extends Func<Out, In>
 */
interface InvFunc extends Func {}

/**
 * @template T
 * @param Func<T, T> $fn
 * @param T $value
 * @return T
 */
function applyFunc(Func $fn, mixed $value): mixed
{
	return $value;
}

/**
 * @param Func<\Exception, \Throwable> $fn
 */
function testMixedVariance(Func $fn): void
{
	$r = applyFunc($fn, new \RuntimeException());
	assertType('Exception', $r);
}

/**
 * @param InvFunc<\Exception, \Throwable> $fn
 */
function testMixedVarianceWithInv(InvFunc $fn): void
{
	$r = applyFunc($fn, new \RuntimeException());
	assertType('Exception', $r);
}

// Method on a class (vs function)
class Container
{
	/**
	 * @template T
	 * @param T $value
	 * @param Contra<T> ...$contras
	 * @return T
	 */
	public function test(mixed $value, Contra ...$contras): mixed
	{
		return $value;
	}

	/**
	 * @template T
	 * @param T $value
	 * @param Contra<T> ...$contras
	 * @return T
	 */
	public static function testStatic(mixed $value, Contra ...$contras): mixed
	{
		return $value;
	}
}

function testMethod(): void
{
	$c = new Container();
	// Method with Inv args
	$r = $c->test(
		new \RuntimeException(),
		inv(\Throwable::class),
		inv(\Exception::class),
	);
	assertType('RuntimeException', $r);

	// Static method with Inv args
	$r2 = Container::testStatic(
		new \RuntimeException(),
		inv(\Throwable::class),
		inv(\Exception::class),
	);
	assertType('RuntimeException', $r2);
}
