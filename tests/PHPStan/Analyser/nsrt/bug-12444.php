<?php

declare(strict_types = 1);

namespace Bug12444;

use function PHPStan\Testing\assertType;

/**
 * @template-covariant T
 */
interface Covariant {}

/**
 * @template T of object
 * @param class-string<T> $class
 * @return Covariant<T>
 */
function covariant(string $class): Covariant
{
	throw new \Exception();
}

/**
 * @template-contravariant T
 */
interface Contravariant {}

/**
 * @template T of object
 * @param class-string<T> $class
 * @return Contravariant<T>
 */
function contravariant(string $class): Contravariant
{
	throw new \Exception();
}

/**
 * @template T
 * @extends Covariant<T>
 * @extends Contravariant<T>
 */
interface Invariant extends Covariant, Contravariant {}

/**
 * @template T of object
 * @param class-string<T> $class
 * @return Invariant<T>
 */
function invariant(string $class): Invariant
{
	throw new \Exception();
}

/**
 * @template T
 * @param T $value
 * @param Covariant<T> ...$covariants
 * @return T
 */
function testCovariant(mixed $value, Covariant ...$covariants): mixed
{
	return $value;
}

/**
 * @template T
 * @param T $value
 * @param Contravariant<T> ...$contravariants
 * @return T
 */
function testContravariant(mixed $value, Contravariant ...$contravariants): mixed
{
	return $value;
}

// Contravariant with direct Contravariant args
$r3 = testContravariant(
	new \RuntimeException(),
	contravariant(\Throwable::class),
	contravariant(\Exception::class),
);
assertType('RuntimeException', $r3);

// Contravariant with Invariant args (extending Contravariant) - this is the reported bug
$r4 = testContravariant(
	new \RuntimeException(),
	invariant(\Throwable::class),
	invariant(\Exception::class),
);
assertType('RuntimeException', $r4);
