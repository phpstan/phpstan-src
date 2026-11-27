<?php // lint >= 8.0

namespace Bug12444;

use Exception;
use OutOfBoundsException;
use RuntimeException;
use Throwable;

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
    return new class implements Covariant {};
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
    return new class implements Contravariant {};
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
    return new class implements Invariant {};
}

/**
 * @template T
 * @param T $value
 * @param Covariant<T> ...$covariants
 */
function testCovariant(mixed $value, Covariant ...$covariants): void {}

/**
 * @template T
 * @param T $value
 * @param Contravariant<T> ...$contravariants
 */
function testContravariant(mixed $value, Contravariant ...$contravariants): void {}

// this works as expected
testCovariant(
    new Exception(),
    covariant(RuntimeException::class),
    covariant(OutOfBoundsException::class),
);

// this works as expected
testCovariant(
    new Exception(),
    invariant(RuntimeException::class),
    invariant(OutOfBoundsException::class),
);

// this works as expected
testContravariant(
    new RuntimeException(),
    contravariant(Throwable::class),
    contravariant(Exception::class),
);

// this does not work :(
testContravariant(
    new RuntimeException(),
    invariant(Throwable::class),
    invariant(Exception::class),
);
