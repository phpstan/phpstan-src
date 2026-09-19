<?php declare(strict_types = 1);

namespace InvariantGenericAssert;

interface Animal {}
interface Cat extends Animal {}

/** @template T of object */
final class Box {}

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
	 * @template T
	 * @param Bag<T> $b
	 * @phpstan-assert Bag<string> $b
	 */
	public function assertStringBag(Bag $b): void {}

	/**
	 * @template T of object
	 * @param Box<T> $b
	 * @phpstan-assert Box<T&Cat> $b
	 */
	public static function assertCatStatic(Box $b): void {}

	/**
	 * @template T
	 * @param Bag<T> $b
	 * @phpstan-assert Bag<string> $b
	 */
	public static function assertStringBagStatic(Bag $b): void {}

}

/** @param Box<Animal> $b */
function testFunctionCall(Box $b): void
{
	assertCat($b);
}

/** @param Box<Animal> $b */
function testMethodCall(Box $b, Asserter $a): void
{
	$a->assertCat($b);
}

/** @param Box<Animal> $b */
function testStaticMethodCall(Box $b): void
{
	Asserter::assertCatStatic($b);
}

/** @param Bag<int> $b */
function testDisjointFunctionCall(Bag $b): void
{
	assertStringBag($b);
}

/** @param Bag<int> $b */
function testDisjointMethodCall(Bag $b, Asserter $a): void
{
	$a->assertStringBag($b);
}

/** @param Bag<int> $b */
function testDisjointStaticMethodCall(Bag $b): void
{
	Asserter::assertStringBagStatic($b);
}
