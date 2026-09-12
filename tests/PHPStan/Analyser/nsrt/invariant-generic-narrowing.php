<?php declare(strict_types = 1);

namespace InvariantGenericNarrowing;

use function PHPStan\Testing\assertType;

interface Animal {}
interface Cat extends Animal {}

/** @template T of object */
final class Box {}

/** @template-covariant T of object */
final class CoBox {}

/** @template T */
final class Bag {}

/**
 * @template T of object
 * @param Box<T> $b
 * @phpstan-assert Box<T&Cat> $b
 */
function assertCat(Box $b): void {}

/**
 * @template T
 * @param Bag<T> $b
 * @phpstan-assert Bag<string> $b
 */
function assertStringBag(Bag $b): void {}

final class Asserter
{

	/**
	 * @template T of object
	 * @param Box<T> $b
	 * @phpstan-assert Box<T&Cat> $b
	 */
	public function assertCat(Box $b): void {}

	/**
	 * @template T of object
	 * @param Box<T> $b
	 * @phpstan-assert Box<T&Cat> $b
	 */
	public static function assertCatStatic(Box $b): void {}

}

/** @param Box<Animal> $b */
function narrowByFunctionAssert(Box $b): void
{
	assertCat($b);
	assertType('InvariantGenericNarrowing\Box<InvariantGenericNarrowing\Cat>', $b);
}

/** @param Box<Animal> $b */
function narrowByMethodAssert(Box $b, Asserter $a): void
{
	$a->assertCat($b);
	assertType('InvariantGenericNarrowing\Box<InvariantGenericNarrowing\Cat>', $b);
}

/** @param Box<Animal> $b */
function narrowByStaticMethodAssert(Box $b): void
{
	Asserter::assertCatStatic($b);
	assertType('InvariantGenericNarrowing\Box<InvariantGenericNarrowing\Cat>', $b);
}

/**
 * @template T of object
 * @param Box<T> $b
 */
function narrowTemplateArgument(Box $b): void
{
	assertCat($b);
	assertType('InvariantGenericNarrowing\Box<InvariantGenericNarrowing\Cat&T of object (function InvariantGenericNarrowing\narrowTemplateArgument(), argument)>', $b);
}

/**
 * @param Box<Animal>&Box<Cat> $b
 * @param CoBox<Animal>&CoBox<Cat> $c
 */
function intersectionInPhpDoc($b, $c): void
{
	assertType('InvariantGenericNarrowing\Box<InvariantGenericNarrowing\Cat>', $b);
	assertType('InvariantGenericNarrowing\CoBox<InvariantGenericNarrowing\Cat>', $c);
}

/**
 * @template T of object
 * @param Box<T> $b
 * @param Box<Cat> $c
 */
function identicalComparison(Box $b, Box $c): void
{
	if ($b === $c) {
		assertType('InvariantGenericNarrowing\Box<InvariantGenericNarrowing\Cat&T of object (function InvariantGenericNarrowing\identicalComparison(), argument)>', $b);
	}
}

/**
 * @param Bag<int> $b
 * @param Bag<int|string> $c
 */
function disjointTypeArgumentsAreStillNever(Bag $b, Bag $c): void
{
	assertStringBag($b);
	assertType('*NEVER*', $b);

	assertStringBag($c);
	assertType('InvariantGenericNarrowing\Bag<string>', $c);
}
